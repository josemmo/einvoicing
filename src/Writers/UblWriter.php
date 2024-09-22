<?php
namespace Einvoicing\Writers;

use DateTime;
use Einvoicing\AllowanceOrCharge;
use Einvoicing\Attachment;
use Einvoicing\Delivery;
use Einvoicing\Identifier;
use Einvoicing\Invoice;
use Einvoicing\InvoiceLine;
use Einvoicing\Models\InvoiceTotals;
use Einvoicing\Party;
use Einvoicing\Payments\Card;
use Einvoicing\Payments\Mandate;
use Einvoicing\Payments\Payment;
use Einvoicing\Payments\Transfer;
use UXML\UXML;
use function in_array;

class UblWriter extends AbstractWriter {
    const NS_INVOICE = "urn:oasis:names:specification:ubl:schema:xsd:Invoice-2";
    const NS_CREDIT_NOTE = "urn:oasis:names:specification:ubl:schema:xsd:CreditNote-2";
    const NS_CAC = "urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2";
    const NS_CBC = "urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2";

    /**
     * @inheritdoc
     */
    public function export(Invoice $invoice): string {
        $totals = $invoice->getTotals();
        $isCreditNoteProfile = $this->isCreditNoteProfile($invoice);

        // Create root element
        $rootElementName = $isCreditNoteProfile ? 'CreditNote' : 'Invoice';
        $xml = UXML::newInstance($rootElementName, null, [
            'xmlns' => $isCreditNoteProfile ? self::NS_CREDIT_NOTE : self::NS_INVOICE,
            'xmlns:cac' => self::NS_CAC,
            'xmlns:cbc' => self::NS_CBC
        ]);

        // BT-24: Specification identifier
        $specificationIdentifier = $invoice->getSpecification();
        if ($specificationIdentifier !== null) {
            $xml->add('cbc:CustomizationID', $specificationIdentifier);
        }

        // BT-23: Business process type
        $businessProcessType = $invoice->getBusinessProcess();
        if ($businessProcessType !== null) {
            $xml->add('cbc:ProfileID', $businessProcessType);
        }

        // BT-1: Invoice number
        $number = $invoice->getNumber();
        if ($number !== null) {
            $xml->add('cbc:ID', $number);
        }

        // BT-2: Issue date
        $issueDate = $invoice->getIssueDate();
        if ($issueDate !== null) {
            $xml->add('cbc:IssueDate', $issueDate->format('Y-m-d'));
        }

        // BT-9: Due date (for invoice profile)
        $dueDate = $invoice->getDueDate();
        if (!$isCreditNoteProfile && $dueDate !== null) {
            $xml->add('cbc:DueDate', $dueDate->format('Y-m-d'));
        }

        // BT-3: Invoice type code
        $typeCodeName = $isCreditNoteProfile ? "cbc:CreditNoteTypeCode" : "cbc:InvoiceTypeCode";
        $xml->add($typeCodeName, (string) $invoice->getType());

        // BT-22: Notes
        foreach ($invoice->getNotes() as $note) {
            $xml->add('cbc:Note', $note);
        }

        // BT-7: Tax point date
        $taxPointDate = $invoice->getTaxPointDate();
        if ($taxPointDate !== null) {
            $xml->add('cbc:TaxPointDate', $taxPointDate->format('Y-m-d'));
        }

        // BT-5: Invoice currency code
        $xml->add('cbc:DocumentCurrencyCode', $invoice->getCurrency());

        // BT-6: VAT accounting currency code
        $vatCurrency = $invoice->getVatCurrency();
        if ($vatCurrency !== null) {
            $xml->add('cbc:TaxCurrencyCode', $vatCurrency);
        }

        // BT-19: Buyer accounting reference
        $buyerAccountingReference = $invoice->getBuyerAccountingReference();
        if ($buyerAccountingReference !== null) {
            $xml->add('cbc:AccountingCost', $buyerAccountingReference);
        }

        // BT-10: Buyer reference
        $buyerReference = $invoice->getBuyerReference();
        if ($buyerReference !== null) {
            $xml->add('cbc:BuyerReference', $buyerReference);
        }

        // BG-14: Invoice period
        $this->addPeriodNode($xml, $invoice);

        // Order reference node
        $this->addOrderReferenceNode($xml, $invoice);

        // BG-3: Preceding invoice reference
        foreach ($invoice->getPrecedingInvoiceReferences() as $invoiceReference) {
            $invoiceDocumentReferenceNode = $xml->add('cac:BillingReference')->add('cac:InvoiceDocumentReference');
            $invoiceDocumentReferenceNode->add('cbc:ID', $invoiceReference->getValue());
            $invoiceReferenceIssueDate = $invoiceReference->getIssueDate();
            if ($invoiceReferenceIssueDate !== null) {
                $invoiceDocumentReferenceNode->add('cbc:IssueDate', $invoiceReferenceIssueDate->format('Y-m-d'));
            }
        }

        // BT-17: Tender or lot reference (for invoice profile)
        if (!$isCreditNoteProfile) {
            $this->addTenderOrLotReferenceNode($xml, $invoice);
        }

        // BT-12: Contract reference
        $contractReference = $invoice->getContractReference();
        if ($contractReference !== null) {
            $xml->add('cac:ContractDocumentReference')->add('cbc:ID', $contractReference);
        }

        // BG-24: Attachments node
        foreach ($invoice->getAttachments() as $attachment) {
            $this->addAttachmentNode($xml, $attachment);
        }

        // BT-17: Tender or lot reference (for credit note profile)
        if ($isCreditNoteProfile) {
            $this->addTenderOrLotReferenceNode($xml, $invoice);
        }

        // BT-11: Project reference
        $projectReference = $invoice->getProjectReference();
        if ($projectReference !== null) {
            $xml->add('cac:ProjectReference')->add('cbc:ID', $projectReference);
        }

        // Seller node
        $seller = $invoice->getSeller();
        if ($seller !== null) {
            $this->addSellerOrBuyerNode($xml->add('cac:AccountingSupplierParty'), $seller);
        }

        // Buyer node
        $buyer = $invoice->getBuyer();
        if ($buyer !== null) {
            $this->addSellerOrBuyerNode($xml->add('cac:AccountingCustomerParty'), $buyer);
        }

        // Payee node
        $payee = $invoice->getPayee();
        if ($payee !== null) {
            $this->addPayeeNode($xml, $payee);
        }

        // Delivery node
        $delivery = $invoice->getDelivery();
        if ($delivery !== null) {
            $this->addDeliveryNode($xml, $delivery);
        }

        // Payment means nodes
        foreach ($invoice->getPayments() as $payment) {
            $this->addPaymentMeansNode($xml, $payment, $isCreditNoteProfile ? $dueDate : null);
        }

        // BT-20: Payment terms
        $firstPayment = $invoice->getPayment(true); // @phan-suppress-current-line PhanDeprecatedFunction
        $paymentTerms = $invoice->getPaymentTerms() ?? ($firstPayment === null ? null : $firstPayment->getTerms(true)); // @phan-suppress-current-line PhanDeprecatedFunction
        if ($paymentTerms !== null) {
            $xml->add('cac:PaymentTerms')->add('cbc:Note', $paymentTerms);
        }

        // Allowances and charges
        foreach ($invoice->getAllowances() as $item) {
            $this->addAllowanceOrCharge($xml, $item, false, $invoice, $totals, null);
        }
        foreach ($invoice->getCharges() as $item) {
            $this->addAllowanceOrCharge($xml, $item, true, $invoice, $totals, null);
        }

        // Invoice totals
        $this->addTaxTotalNodes($xml, $totals);
        $this->addDocumentTotalsNode($xml, $totals);

        // Invoice lines
        $lines = $invoice->getLines();
        $lastGenId = 0;
        $usedIds = [];
        foreach ($lines as $line) {
            $lineId = $line->getId();
            if ($lineId !== null) {
                $usedIds[] = $lineId;
            }
        }
        foreach ($lines as $line) {
            $this->addLineNode($xml, $line, $invoice, $isCreditNoteProfile, $lastGenId, $usedIds);
        }

        return $xml->asXML();
    }


    /**
     * Is credit note profile
     * @param  Invoice $invoice Invoice invoice
     * @return boolean          Whether document should use invoice or credit note profiles
     */
    private function isCreditNoteProfile(Invoice $invoice): bool {
        $type = $invoice->getType();
        return in_array($type, [
            Invoice::TYPE_CREDIT_NOTE_RELATED_TO_GOODS_OR_SERVICES,
            Invoice::TYPE_CREDIT_NOTE_RELATED_TO_FINANCIAL_ADJUSTMENTS,
            Invoice::TYPE_CREDIT_NOTE,
            Invoice::TYPE_FACTORED_CREDIT_NOTE,
            Invoice::TYPE_FORWARDERS_CREDIT_NOTE
        ]);
    }


    /**
     * Add identifier node
     * @param UXML       $parent     Parent element
     * @param string     $name       New node name
     * @param Identifier $identifier Identifier instance
     * @param string     $schemeAttr Scheme attribute name
     */
    private function addIdentifierNode(UXML $parent, string $name, Identifier $identifier, string $schemeAttr="schemeID") {
        $scheme = $identifier->getScheme();
        $attrs = ($scheme === null) ? [] : ["$schemeAttr" => $scheme];
        $parent->add($name, $identifier->getValue(), $attrs);
    }


    /**
     * Add period node
     * @param UXML                $parent Parent element
     * @param Invoice|InvoiceLine $source Source instance
     */
    private function addPeriodNode(UXML $parent, $source) {
        $startDate = $source->getPeriodStartDate();
        $endDate = $source->getPeriodEndDate();
        if ($startDate === null && $endDate === null) return;

        $xml = $parent->add('cac:InvoicePeriod');

        // Period start date
        if ($startDate !== null) {
            $xml->add('cbc:StartDate', $startDate->format('Y-m-d'));
        }

        // Period end date
        if ($endDate !== null) {
            $xml->add('cbc:EndDate', $endDate->format('Y-m-d'));
        }
    }


    /**
     * Add order reference node
     * @param UXML    $parent  Parent element
     * @param Invoice $invoice Invoice instance
     */
    private function addOrderReferenceNode(UXML $parent, Invoice $invoice) {
        $purchaseOrderReference = $invoice->getPurchaseOrderReference();
        $salesOrderReference = $invoice->getSalesOrderReference();
        if ($purchaseOrderReference === null && $salesOrderReference === null) return;

        $orderReferenceNode = $parent->add('cac:OrderReference');

        // BT-13: Purchase order reference
        if ($purchaseOrderReference !== null) {
            $orderReferenceNode->add('cbc:ID', $purchaseOrderReference);
        }

        // BT-14: Sales order reference
        if ($salesOrderReference !== null) {
            $orderReferenceNode->add('cbc:SalesOrderID', $salesOrderReference);
        }
    }


    /**
     * Add tender or lot reference node
     * @param UXML    $parent  Parent element
     * @param Invoice $invoice Invoice instance
     */
    private function addTenderOrLotReferenceNode(UXML $parent, Invoice $invoice) {
        $tenderOrLotReference = $invoice->getTenderOrLotReference();
        if ($tenderOrLotReference !== null) {
            $parent->add('cac:OriginatorDocumentReference')->add('cbc:ID', $tenderOrLotReference);
        }
    }


    /**
     * Add amount node
     * @param UXML   $parent   Parent element
     * @param string $name     New node name
     * @param float  $amount   Amount
     * @param string $currency Currency code
     */
    private function addAmountNode(UXML $parent, string $name, float $amount, string $currency) {
        $parent->add($name, (string) $amount, ['currencyID' => $currency]);
    }


    /**
     * Add VAT node
     * @param UXML        $parent              Parent element
     * @param string      $name                New node name
     * @param string      $category            VAT category
     * @param float|null  $rate                VAT rate
     * @param string|null $exemptionReasonCode VAT exemption reason code
     * @param string|null $exemptionReason     VAT exemption reason as text
     */
    private function addVatNode(
        UXML $parent, string $name, string $category, ?float $rate,
        ?string $exemptionReasonCode=null, ?string $exemptionReason=null
    ) {
        $xml = $parent->add($name);

        // VAT category
        $xml->add('cbc:ID', $category);

        // VAT rate
        if ($rate !== null) {
            $xml->add('cbc:Percent', (string) $rate);
        }

        // Exemption reason code
        if ($exemptionReasonCode !== null) {
            $xml->add('cbc:TaxExemptionReasonCode', $exemptionReasonCode);
        }

        // Exemption reason (as text)
        if ($exemptionReason !== null) {
            $xml->add('cbc:TaxExemptionReason', $exemptionReason);
        }

        // Tax scheme
        $xml->add('cac:TaxScheme')->add('cbc:ID', 'VAT');
    }


    /**
     * Add postal address node
     * @param  UXML           $parent Parent element
     * @param  string         $name   New node name
     * @param  Delivery|Party $source Source instance
     * @return UXML                   Postal address node
     */
    private function addPostalAddressNode(UXML $parent, string $name, $source) {
        $xml = $parent->add($name);

        // Street name
        $addressLines = $source->getAddress();
        if (isset($addressLines[0])) {
            $xml->add('cbc:StreetName', $addressLines[0]);
        }

        // Additional street name
        if (isset($addressLines[1])) {
            $xml->add('cbc:AdditionalStreetName', $addressLines[1]);
        }

        // City name
        $cityName = $source->getCity();
        if ($cityName !== null) {
            $xml->add('cbc:CityName', $cityName);
        }

        // Postal code
        $postalCode = $source->getPostalCode();
        if ($postalCode !== null) {
            $xml->add('cbc:PostalZone', $postalCode);
        }

        // Subdivision
        $subdivision = $source->getSubdivision();
        if ($subdivision !== null) {
            $xml->add('cbc:CountrySubentity', $subdivision);
        }

        // Address line (third address line)
        if (isset($addressLines[2])) {
            $xml->add('cac:AddressLine')->add('cbc:Line', $addressLines[2]);
        }

        // Country
        $country = $source->getCountry();
        if ($country !== null) {
            $xml->add('cac:Country')->add('cbc:IdentificationCode', $country);
        }

        return $xml;
    }


    /**
     * Add seller or buyer node
     * @param UXML  $parent Invoice element
     * @param Party $party  Party instance
     */
    private function addSellerOrBuyerNode(UXML $parent, Party $party) {
        $xml = $parent->add('cac:Party');

        // Electronic address
        $electronicAddress = $party->getElectronicAddress();
        if ($electronicAddress !== null) {
            $this->addIdentifierNode($xml, 'cbc:EndpointID', $electronicAddress);
        }

        // Additional identifiers
        foreach ($party->getIdentifiers() as $identifier) {
            $identifierNode = $xml->add('cac:PartyIdentification');
            $this->addIdentifierNode($identifierNode, 'cbc:ID', $identifier);
        }

        // Trading name
        $tradingName = $party->getTradingName();
        if ($tradingName !== null) {
            $xml->add('cac:PartyName')->add('cbc:Name', $tradingName);
        }

        // Postal address node
        $this->addPostalAddressNode($xml, 'cac:PostalAddress', $party);

        // VAT number
        $vatNumber = $party->getVatNumber();
        if ($vatNumber !== null) {
            $taxNode = $xml->add('cac:PartyTaxScheme');
            $taxNode->add('cbc:CompanyID', $vatNumber);
            $taxNode->add('cac:TaxScheme')->add('cbc:ID', 'VAT');
        }

        // Tax registration identifier
        $taxRegistrationId = $party->getTaxRegistrationId();
        if ($taxRegistrationId !== null) {
            $taxRegistrationNode = $xml->add('cac:PartyTaxScheme');
            $taxRegistrationNode->add('cbc:CompanyID', $taxRegistrationId->getValue());

            $taxRegistrationSchemeNode = $taxRegistrationNode->add('cac:TaxScheme');
            $taxRegistrationScheme = $taxRegistrationId->getScheme();
            if ($taxRegistrationScheme !== null) {
                $taxRegistrationSchemeNode->add('cbc:ID', $taxRegistrationScheme);
            }
        }

        // Initial legal entity node
        $legalEntityNode = $xml->add('cac:PartyLegalEntity');

        // Legal name
        $legalName = $party->getName();
        if ($legalName !== null) {
            $legalEntityNode->add('cbc:RegistrationName', $legalName);
        }

        // Company ID
        $companyId = $party->getCompanyId();
        if ($companyId !== null) {
            $this->addIdentifierNode($legalEntityNode, 'cbc:CompanyID', $companyId);
        }

        // BT-33: Seller additional legal information
        $legalInformation = $party->getLegalInformation();
        if ($legalInformation !== null) {
            $legalEntityNode->add('cbc:CompanyLegalForm', $legalInformation);
        }

        // Contact point
        if ($party->hasContactInformation()) {
            $contactNode = $xml->add('cac:Contact');
            
            $contactName = $party->getContactName();
            if ($contactName !== null) {
                $contactNode->add('cbc:Name', $contactName);
            }

            $contactPhone = $party->getContactPhone();
            if ($contactPhone !== null) {
                $contactNode->add('cbc:Telephone', $contactPhone);
            }

            $contactEmail = $party->getContactEmail();
            if ($contactEmail !== null) {
                $contactNode->add('cbc:ElectronicMail', $contactEmail);
            }
        }
    }


    /**
     * Add payee node
     * @param UXML  $parent Invoice element
     * @param Party $party  Party instance
     */
    private function addPayeeNode(UXML $parent, Party $party) {
        $xml = $parent->add('cac:PayeeParty');

        // Additional identifiers
        foreach ($party->getIdentifiers() as $identifier) {
            $identifierNode = $xml->add('cac:PartyIdentification');
            $this->addIdentifierNode($identifierNode, 'cbc:ID', $identifier);
        }

        // Party name
        $name = $party->getName();
        if ($name !== null) {
            $xml->add('cac:PartyName')->add('cbc:Name', $name);
        }

        // Company ID
        $companyId = $party->getCompanyId();
        if ($companyId !== null) {
            $legalEntityNode = $xml->add('cac:PartyLegalEntity');
            $this->addIdentifierNode($legalEntityNode, 'cbc:CompanyID', $companyId);
        }
    }


    /**
     * Add delivery node
     * @param UXML     $parent   Invoice element
     * @param Delivery $delivery Delivery instance
     */
    private function addDeliveryNode(UXML $parent, Delivery $delivery) {
        $xml = $parent->add('cac:Delivery');

        // BT-72: Actual delivery date
        $date = $delivery->getDate();
        if ($date !== null) {
            $xml->add('cbc:ActualDeliveryDate', $date->format('Y-m-d'));
        }

        // Initial delivery location node
        $locationNode = $xml->add('cac:DeliveryLocation');

        // BT-71: Delivery location identifier
        $locationIdentifier = $delivery->getLocationIdentifier();
        if ($locationIdentifier !== null) {
            $this->addIdentifierNode($locationNode, 'cbc:ID', $locationIdentifier);
        }

        // Delivery postal address
        $addressNode = $this->addPostalAddressNode($locationNode, 'cac:Address', $delivery);
        if ($addressNode->isEmpty()) {
            $addressNode->remove();
        }

        // BT-70: Deliver name
        $name = $delivery->getName();
        if ($name !== null) {
            $xml->add('cac:DeliveryParty')->add('cac:PartyName')->add('cbc:Name', $name);
        }

        // Remove location node if empty
        if ($locationNode->isEmpty()) {
            $locationNode->remove();
        }
    }


    /**
     * Add payment means node
     * @param UXML          $parent  Invoice element
     * @param Payment       $payment Payment instance
     * @param DateTime|null $dueDate Invoice due date (for credit note profile)
     */
    private function addPaymentMeansNode(UXML $parent, Payment $payment, ?DateTime $dueDate) {
        $xml = $parent->add('cac:PaymentMeans');

        // BT-81: Payment means code
        // BT-82: Payment means name
        $meansCode = $payment->getMeansCode();
        if ($meansCode !== null) {
            $meansText = $payment->getMeansText();
            $attrs = ($meansText === null) ? [] : ['name' => $meansText];
            $xml->add('cbc:PaymentMeansCode', $meansCode, $attrs);
        }

        // BT-9: Due date (for credit note profile)
        if ($dueDate !== null) {
            $xml->add('cbc:PaymentDueDate', $dueDate->format('Y-m-d'));
        }

        // BT-83: Payment ID
        $paymentId = $payment->getId();
        if ($paymentId !== null) {
            $xml->add('cbc:PaymentID', $paymentId);
        }

        // BG-18: Payment card
        $card = $payment->getCard();
        if ($card !== null) {
            $this->addPaymentCardNode($xml, $card);
        }

        // BG-17: Payment transfers
        foreach ($payment->getTransfers() as $transfer) {
            $this->addPaymentTransferNode($xml, $transfer);
        }

        // BG-19: Payment mandate
        $mandate = $payment->getMandate();
        if ($mandate !== null) {
            $this->addPaymentMandateNode($xml, $mandate);
        }

        // Remove PaymentMeans node if empty
        if ($xml->isEmpty()) {
            $xml->remove();
        }
    }


    /**
     * Add payment card node
     * @param UXML $parent PaymentMeans element
     * @param Card $card   Card instance
     */
    private function addPaymentCardNode(UXML $parent, Card $card) {
        $xml = $parent->add('cac:CardAccount');

        // BT-87: Card PAN
        $pan = $card->getPan();
        if ($pan !== null) {
            $xml->add('cbc:PrimaryAccountNumberID', $pan);
        }

        // Card network
        $network = $card->getNetwork();
        if ($network !== null) {
            $xml->add('cbc:NetworkID', $network);
        }

        // BT-88: Holder name
        $holder = $card->getHolder();
        if ($holder !== null) {
            $xml->add('cbc:HolderName', $holder);
        }
    }


    /**
     * Add payment transfer node
     * @param UXML     $parent   PaymentMeans element
     * @param Transfer $transfer Transfer instance
     */
    private function addPaymentTransferNode(UXML $parent, Transfer $transfer) {
        $xml = $parent->add('cac:PayeeFinancialAccount');

        // BT-84: Receiving account ID
        $accountId = $transfer->getAccountId();
        if ($accountId !== null) {
            $xml->add('cbc:ID', $accountId);
        }

        // BT-85: Receiving account name
        $accountName = $transfer->getAccountName();
        if ($accountName !== null) {
            $xml->add('cbc:Name', $accountName);
        }

        // BT-86: Service provider ID
        $provider = $transfer->getProvider();
        if ($provider !== null) {
            $xml->add('cac:FinancialInstitutionBranch')->add('cbc:ID', $provider);
        }
    }


    /**
     * Add payment mandate node
     * @param UXML    $parent  PaymentMeans element
     * @param Mandate $mandate Mandate instance
     */
    private function addPaymentMandateNode(UXML $parent, Mandate $mandate) {
        $xml = $parent->add('cac:PaymentMandate');

        // BT-89: Mandate reference
        $reference = $mandate->getReference();
        if ($reference !== null) {
            $xml->add('cbc:ID', $reference);
        }

        // BT-91: Debited account
        $account = $mandate->getAccount();
        if ($account !== null) {
            $xml->add('cac:PayerFinancialAccount')->add('cbc:ID', $account);
        }
    }


    /**
     * Add allowance or charge
     * @param UXML               $parent   Parent element
     * @param AllowanceOrCharge  $item     Allowance or charge instance
     * @param boolean            $isCharge Is charge (TRUE) or allowance (FALSE)
     * @param Invoice            $invoice  Invoice instance
     * @param InvoiceTotals|null $totals   Invoice totals or NULL in case at line level
     * @param InvoiceLine|null   $line     Invoice line or NULL in case of at document level
     */
    private function addAllowanceOrCharge(
        UXML $parent,
        AllowanceOrCharge $item,
        bool $isCharge,
        Invoice $invoice,
        ?InvoiceTotals $totals,
        ?InvoiceLine $line
    ) {
        $atDocumentLevel = ($line === null);
        $xml = $parent->add('cac:AllowanceCharge');

        // Charge indicator
        $xml->add('cbc:ChargeIndicator', $isCharge ? 'true' : 'false');

        // Reason code
        $reasonCode = $item->getReasonCode();
        if ($reasonCode !== null) {
            $xml->add('cbc:AllowanceChargeReasonCode', $reasonCode);
        }

        // Reason text
        $reasonText = $item->getReason();
        if ($reasonText !== null) {
            $xml->add('cbc:AllowanceChargeReason', $reasonText);
        }

        // Percentage
        if ($item->isPercentage()) {
            $xml->add('cbc:MultiplierFactorNumeric', (string) $item->getAmount());
        }

        // Amount
        $baseAmount = $atDocumentLevel ?
            $totals->netAmount :                                 // @phan-suppress-current-line PhanPossiblyUndeclaredProperty
            $line->getNetAmountBeforeAllowancesCharges() ?? 0.0; // @phan-suppress-current-line PhanPossiblyNonClassMethodCall
        $this->addAmountNode(
            $xml,
            'cbc:Amount',
            $invoice->round($item->getEffectiveAmount($baseAmount), 'line/allowanceChargeAmount'),
            $invoice->getCurrency()
        );

        // Base amount
        if ($item->isPercentage()) {
            $this->addAmountNode(
                $xml,
                'cbc:BaseAmount',
                $invoice->round($baseAmount, 'line/netAmount'),
                $invoice->getCurrency()
            );
        }

        // Tax category
        if ($atDocumentLevel) {
            $this->addVatNode($xml, 'cac:TaxCategory', $item->getVatCategory(), $item->getVatRate());
        }
    }


    /**
     * Add tax total nodes
     * @param UXML          $parent Parent element
     * @param InvoiceTotals $totals Invoice totals
     */
    private function addTaxTotalNodes(UXML $parent, InvoiceTotals $totals) {
        $xml = $parent->add('cac:TaxTotal');

        // Add tax amount
        $this->addAmountNode($xml, 'cbc:TaxAmount', $totals->vatAmount, $totals->currency);

        // Add each tax details
        foreach ($totals->vatBreakdown as $item) {
            $vatBreakdownNode = $xml->add('cac:TaxSubtotal');
            $this->addAmountNode($vatBreakdownNode, 'cbc:TaxableAmount', $item->taxableAmount, $totals->currency);
            $this->addAmountNode($vatBreakdownNode, 'cbc:TaxAmount', $item->taxAmount, $totals->currency);
            $this->addVatNode(
                $vatBreakdownNode,
                'cac:TaxCategory',
                $item->category,
                $item->rate,
                $item->exemptionReasonCode,
                $item->exemptionReason
            );
        }

        // Add tax amount in VAT accounting currency (if any)
        $customVatAmount = $totals->customVatAmount;
        if ($customVatAmount !== null) {
            $this->addAmountNode(
                $parent->add('cac:TaxTotal'),
                'cbc:TaxAmount',
                $customVatAmount,
                $totals->vatCurrency ?? $totals->currency
            );
        }
    }


    /**
     * Add document totals node
     * @param UXML          $parent Parent element
     * @param InvoiceTotals $totals Invoice totals
     */
    private function addDocumentTotalsNode(UXML $parent, InvoiceTotals $totals) {
        $xml = $parent->add('cac:LegalMonetaryTotal');

        // Build totals matrix
        $totalsMatrix = [];
        $totalsMatrix['cbc:LineExtensionAmount'] = $totals->netAmount;
        $totalsMatrix['cbc:TaxExclusiveAmount'] = $totals->taxExclusiveAmount;
        $totalsMatrix['cbc:TaxInclusiveAmount'] = $totals->taxInclusiveAmount;
        if ($totals->allowancesAmount > 0) {
            $totalsMatrix['cbc:AllowanceTotalAmount'] = $totals->allowancesAmount;
        }
        if ($totals->chargesAmount > 0) {
            $totalsMatrix['cbc:ChargeTotalAmount'] = $totals->chargesAmount;
        }
        if ($totals->paidAmount > 0) {
            $totalsMatrix['cbc:PrepaidAmount'] = $totals->paidAmount;
        }
        if ($totals->roundingAmount != 0) {
            $totalsMatrix['cbc:PayableRoundingAmount'] = $totals->roundingAmount;
        }
        $totalsMatrix['cbc:PayableAmount'] = $totals->payableAmount;

        // Create and append XML nodes
        foreach ($totalsMatrix as $field=>$amount) {
            $this->addAmountNode($xml, $field, $amount, $totals->currency);
        }
    }


    /**
     * Add invoice line
     * @param UXML        $parent              Parent XML element
     * @param InvoiceLine $line                Invoice line
     * @param Invoice     $invoice             Invoice instance
     * @param boolean     $isCreditNoteProfile Is credit note profile
     * @param int         &$lastGenId          Last used auto-generated ID
     * @param string[]    &$usedIds            Used invoice line IDs
     */
    private function addLineNode(
        UXML $parent,
        InvoiceLine $line,
        Invoice $invoice,
        bool $isCreditNoteProfile,
        int &$lastGenId,
        array &$usedIds
    ) {
        $lineElementName = $isCreditNoteProfile ? "cac:CreditNoteLine" : "cac:InvoiceLine";
        $xml = $parent->add($lineElementName);

        // BT-126: Invoice line identifier
        $lineId = $line->getId();
        if ($lineId === null) {
            do {
                $lineId = (string) ++$lastGenId;
            } while (in_array($lineId, $usedIds));
        }
        $xml->add('cbc:ID', $lineId);

        // BT-127: Invoice line note
        $note = $line->getNote();
        if ($note !== null) {
            $xml->add('cbc:Note', $note);
        }

        // BT-129: Invoiced quantity
        $quantityElementName = $isCreditNoteProfile ? "cbc:CreditedQuantity" : "cbc:InvoicedQuantity";
        $xml->add($quantityElementName, (string) $line->getQuantity(), ['unitCode' => $line->getUnit()]);

        // BT-131: Line net amount
        $netAmount = $line->getNetAmount();
        if ($netAmount !== null) {
            $this->addAmountNode(
                $xml,
                'cbc:LineExtensionAmount',
                $invoice->round($netAmount, 'line/netAmount'),
                $invoice->getCurrency()
            );
        }

        // BT-133: Buyer accounting reference
        $buyerAccountingReference = $line->getBuyerAccountingReference();
        if ($buyerAccountingReference !== null) {
            $xml->add('cbc:AccountingCost', $buyerAccountingReference);
        }

        // BG-26: Invoice line period
        $this->addPeriodNode($xml, $line);

        // BT-132: Order line reference
        $orderLineReference = $line->getOrderLineReference();
        if ($orderLineReference !== null) {
            $xml->add('cac:OrderLineReference')->add('cbc:LineID', $orderLineReference);
        }

        // Allowances and charges
        foreach ($line->getAllowances() as $item) {
            $this->addAllowanceOrCharge($xml, $item, false, $invoice, null, $line);
        }
        foreach ($line->getCharges() as $item) {
            $this->addAllowanceOrCharge($xml, $item, true, $invoice, null, $line);
        }

        // Initial item node
        $itemNode = $xml->add('cac:Item');

        // BT-154: Item description
        $description = $line->getDescription();
        if ($description !== null) {
            $itemNode->add('cbc:Description', $description);
        }

        // BT-153: Item name
        $name = $line->getName();
        if ($name !== null) {
            $itemNode->add('cbc:Name', $name);
        }

        // BT-156: Buyer identifier
        $buyerIdentifier = $line->getBuyerIdentifier();
        if ($buyerIdentifier !== null) {
            $itemNode->add('cac:BuyersItemIdentification')->add('cbc:ID', $buyerIdentifier);
        }

        // BT-155: Seller identifier
        $sellerIdentifier = $line->getSellerIdentifier();
        if ($sellerIdentifier !== null) {
            $itemNode->add('cac:SellersItemIdentification')->add('cbc:ID', $sellerIdentifier);
        }

        // BT-157: Standard identifier
        $standardIdentifier = $line->getStandardIdentifier();
        if ($standardIdentifier !== null) {
            $this->addIdentifierNode($itemNode->add('cac:StandardItemIdentification'), 'cbc:ID', $standardIdentifier);
        }

        // BT-159: Item origin country
        $originCountry = $line->getOriginCountry();
        if ($originCountry !== null) {
            $itemNode->add('cac:OriginCountry')->add('cbc:IdentificationCode', $originCountry);
        }

        // BT-158: Item classification identifiers
        foreach ($line->getClassificationIdentifiers() as $identifier) {
            $classNode = $itemNode->add('cac:CommodityClassification');
            $this->addIdentifierNode($classNode, 'cbc:ItemClassificationCode', $identifier, 'listID');
        }

        // VAT node
        $this->addVatNode($itemNode, 'cac:ClassifiedTaxCategory', $line->getVatCategory(), $line->getVatRate());

        // BG-32: Item attributes
        foreach ($line->getAttributes() as $attribute) {
            $attributeNode = $itemNode->add('cac:AdditionalItemProperty');
            $attributeNode->add('cbc:Name', $attribute->getName());
            $attributeNode->add('cbc:Value', $attribute->getValue());
        }

        // Initial price node
        $priceNode = $xml->add('cac:Price');

        // Price amount
        $price = $line->getPrice();
        if ($price !== null) {
            $this->addAmountNode(
                $priceNode,
                'cbc:PriceAmount',
                $invoice->round($price, 'line/price'),
                $invoice->getCurrency()
            );
        }

        // Base quantity
        $baseQuantity = $line->getBaseQuantity();
        if ($baseQuantity != 1) {
            $priceNode->add('cbc:BaseQuantity', (string) $baseQuantity, ['unitCode' => $line->getUnit()]);
        }

        return $xml;
    }

    /**
     * Add attachment node
     * @param UXML       $parent     Parent element
     * @param Attachment $attachment Attachment instance
     */
    private function addAttachmentNode(UXML $parent, Attachment $attachment) {
        $xml = $parent->add('cac:AdditionalDocumentReference');
        $isInvoiceObjectReference = (!$attachment->hasExternalUrl() && !$attachment->hasContents());

        // BT-122: Supporting document reference
        $identifier = $attachment->getId();
        if ($identifier !== null) {
            $this->addIdentifierNode($xml, 'cbc:ID', $identifier);
        }

        // BT-18: Document type code
        if ($isInvoiceObjectReference) {
            // Code "130" MUST be used to indicate an invoice object reference
            // Not used for other additional documents
            $xml->add('cbc:DocumentTypeCode', '130');
        }

        // BT-123: Supporting document description
        $description = $attachment->getDescription();
        if ($description !== null) {
            $xml->add('cbc:DocumentDescription', $description);
        }

        // Attachment inner node
        if ($isInvoiceObjectReference) {
            return; // Skip inner node in this case
        }
        $attXml = $xml->add('cac:Attachment');

        // BT-125: Attached document
        if ($attachment->hasContents()) {
            $attrs = [];
            $mimeCode = $attachment->getMimeCode();
            $filename = $attachment->getFilename();
            if ($mimeCode !== null) {
                $attrs['mimeCode'] = $mimeCode;
            }
            if ($filename !== null) {
                $attrs['filename'] = $filename;
            }
            $attXml->add('cbc:EmbeddedDocumentBinaryObject', base64_encode($attachment->getContents()), $attrs);
        }

        // BT-124: External document location
        $externalUrl = $attachment->getExternalUrl();
        if ($externalUrl !== null) {
            $attXml->add('cac:ExternalReference')->add('cbc:URI', $externalUrl);
        }
    }
}
