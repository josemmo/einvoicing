<?php
namespace Einvoicing\Writers;

use Einvoicing\AllowanceOrCharge;
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

class UblWriter extends AbstractWriter {
    const NS_INVOICE = "urn:oasis:names:specification:ubl:schema:xsd:Invoice-2";
    const NS_CAC = "urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2";
    const NS_CBC = "urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2";

    /**
     * @inheritdoc
     */
    public function export(Invoice $invoice): string {
        $xml = UXML::newInstance('Invoice', null, [
            'xmlns' => self::NS_INVOICE,
            'xmlns:cac' => self::NS_CAC,
            'xmlns:cbc' => self::NS_CBC
        ]);

        // BT-24: Specification indentifier
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

        // BT-9: Due date
        $dueDate = $invoice->getDueDate();
        if ($dueDate !== null) {
            $xml->add('cbc:DueDate', $dueDate->format('Y-m-d'));
        }

        // BT-3: Invoice type code
        $xml->add('cbc:InvoiceTypeCode', (string) $invoice->getType());

        // BT-22: Note
        $note = $invoice->getNote();
        if ($note !== null) {
            $xml->add('cbc:Note', $note);
        }

        // BT-7: Tax point date
        $taxPointDate = $invoice->getTaxPointDate();
        if ($taxPointDate !== null) {
            $xml->add('cbc:TaxPointDate', $taxPointDate->format('Y-m-d'));
        }

        // BT-5: Invoice currency code
        $xml->add('cbc:DocumentCurrencyCode', $invoice->getCurrency());

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

        // Payment nodes
        $payment = $invoice->getPayment();
        if ($payment !== null) {
            $this->addPaymentNodes($xml, $payment);
        }

        // Allowances and charges
        foreach ($invoice->getAllowances() as $item) {
            $this->addAllowanceOrCharge($xml, $item, false, $invoice);
        }
        foreach ($invoice->getCharges() as $item) {
            $this->addAllowanceOrCharge($xml, $item, true, $invoice);
        }

        // Invoice totals
        $totals = $invoice->getTotals();
        $this->addTaxTotalNode($xml, $totals);
        $this->addDocumentTotalsNode($xml, $totals);

        // Invoice lines
        $lines = $invoice->getLines();
        foreach ($lines as $i=>$line) {
            $this->addLineNode($xml, $line, $i+1, $invoice);
        }

        return $xml->asXML();
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
     * @param UXML     $parent   Parent element
     * @param string   $name     New node name
     * @param string   $category VAT category
     * @param int|null $rate     VAT rate
     */
    private function addVatNode(UXML $parent, string $name, string $category, ?int $rate) {
        $xml = $parent->add($name);

        // VAT category
        $xml->add('cbc:ID', $category);

        // VAT rate
        if ($rate !== null) {
            $xml->add('cbc:Percent', (string) $rate);
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
     * Add payment nodes
     * @param UXML    $parent  Invoice element
     * @param Payment $payment Payment instance
     */
    private function addPaymentNodes(UXML $parent, Payment $payment) {
        $xml = $parent->add('cac:PaymentMeans');

        // BT-81: Payment means code
        // BT-82: Payment means name
        $meansCode = $payment->getMeansCode();
        if ($meansCode !== null) {
            $meansText = $payment->getMeansText();
            $attrs = ($meansText === null) ? [] : ['name' => $meansText];
            $xml->add('cbc:PaymentMeansCode', $meansCode, $attrs);
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

        // BT-20: Payment terms
        $terms = $payment->getTerms();
        if ($terms !== null) {
            $parent->add('cac:PaymentTerms')->add('cbc:Note', $terms);
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
     * @param UXML              $parent   Parent element
     * @param AllowanceOrCharge $item     Allowance or charge instance
     * @param boolean           $isCharge Is charge (TRUE) or allowance (FALSE)
     * @param Invoice           $invoice  Invoice instance
     * @param InvoiceLine|null  $line     Invoice line or NULL in case of at document level
     */
    private function addAllowanceOrCharge(
        UXML $parent,
        AllowanceOrCharge $item,
        bool $isCharge,
        Invoice $invoice,
        ?InvoiceLine $line=null
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
            $invoice->getTotals()->netAmount :
            $line->getNetAmount($invoice->getDecimals('line/netAmount')) ?? 0;
        $amount = $item->getEffectiveAmount($baseAmount, $invoice->getDecimals('line/allowanceChargeAmount'));
        $this->addAmountNode($xml, 'cbc:Amount', $amount, $invoice->getCurrency());

        // Base amount
        if ($item->isPercentage()) {
            $this->addAmountNode($xml, 'cbc:BaseAmount', $baseAmount, $invoice->getCurrency());
        }

        // Tax category
        if ($atDocumentLevel) {
            $this->addVatNode($xml, 'cac:TaxCategory', $item->getVatCategory(), $item->getVatRate());
        }
    }


    /**
     * Add tax total node
     * @param UXML          $parent Parent element
     * @param InvoiceTotals $totals Invoice totals
     */
    private function addTaxTotalNode(UXML $parent, InvoiceTotals $totals) {
        $xml = $parent->add('cac:TaxTotal');

        // Add tax amount
        $this->addAmountNode($xml, 'cbc:TaxAmount', $totals->vatAmount, $totals->currency);

        // Add each tax details
        foreach ($totals->vatBreakdown as $item) {
            $vatBreakdownNode = $xml->add('cac:TaxSubtotal');
            $this->addAmountNode($vatBreakdownNode, 'cbc:TaxableAmount', $item->taxableAmount, $totals->currency);
            $this->addAmountNode($vatBreakdownNode, 'cbc:TaxAmount', $item->taxAmount, $totals->currency);
            $this->addVatNode($vatBreakdownNode, 'cac:TaxCategory', $item->category, $item->rate);
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
        $totalsMatrix = [
            "cbc:LineExtensionAmount" => $totals->netAmount,
            "cbc:TaxExclusiveAmount" => $totals->taxExclusiveAmount,
            "cbc:TaxInclusiveAmount" => $totals->taxInclusiveAmount
        ];
        if ($totals->allowancesAmount > 0) {
            $totalsMatrix['cbc:AllowanceTotalAmount'] = $totals->allowancesAmount;
        }
        if ($totals->chargesAmount > 0) {
            $totalsMatrix['cbc:ChargeTotalAmount'] = $totals->chargesAmount;
        }
        if ($totals->paidAmount > 0) {
            $totalsMatrix['cbc:PrepaidAmount'] = $totals->paidAmount;
        }
        if ($totals->roundingAmount > 0) {
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
     * @param UXML        $parent  Parent XML element
     * @param InvoiceLine $line    Invoice line
     * @param int         $index   Invoice line index
     * @param Invoice     $invoice Invoice instance
     */
    private function addLineNode(UXML $parent, InvoiceLine $line, int $index, Invoice $invoice) {
        $xml = $parent->add('cac:InvoiceLine');

        // BT-126: Line ID
        $xml->add('cbc:ID', (string) $index);

        // BT-127: Invoice line note
        $note = $line->getNote();
        if ($note !== null) {
            $xml->add('cbc:Note', $note);
        }

        // BT-129: Invoiced quantity
        $xml->add('cbc:InvoicedQuantity', (string) $line->getQuantity(), ['unitCode' => $line->getUnit()]);

        // BT-131: Line net amount
        $netAmount = $line->getNetAmount($invoice->getDecimals('line/netAmount'));
        if ($netAmount !== null) {
            $this->addAmountNode($xml, 'cbc:LineExtensionAmount', $netAmount, $invoice->getCurrency());
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
            $this->addAllowanceOrCharge($xml, $item, false, $invoice, $line);
        }
        foreach ($line->getCharges() as $item) {
            $this->addAllowanceOrCharge($xml, $item, true, $invoice, $line);
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
            $this->addAmountNode($priceNode, 'cbc:PriceAmount', $price, $invoice->getCurrency());
        }

        // Base quantity
        $baseQuantity = $line->getBaseQuantity();
        if ($baseQuantity != 1) {
            $priceNode->add('cbc:BaseQuantity', (string) $baseQuantity, ['unitCode' => $line->getUnit()]);
        }

        return $xml;
    }
}
