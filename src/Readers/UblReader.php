<?php
namespace Einvoicing\Readers;

use DateTime;
use Einvoicing\AllowanceOrCharge;
use Einvoicing\Attachment;
use Einvoicing\Attribute;
use Einvoicing\Delivery;
use Einvoicing\Identifier;
use Einvoicing\Invoice;
use Einvoicing\InvoiceLine;
use Einvoicing\InvoiceReference;
use Einvoicing\Party;
use Einvoicing\Payments\Card;
use Einvoicing\Payments\Mandate;
use Einvoicing\Payments\Payment;
use Einvoicing\Payments\Transfer;
use Einvoicing\Traits\VatTrait;
use Einvoicing\Writers\UblWriter;
use InvalidArgumentException;
use UXML\UXML;
use function array_filter;
use function array_map;

class UblReader extends AbstractReader {
    /**
     * @inheritdoc
     * @throws InvalidArgumentException if failed to parse XML
     */
    public function import(string $document): Invoice {
        $invoice = new Invoice();

        // Load XML document
        $xml = UXML::fromString($document);
        $cac = UblWriter::NS_CAC;
        $cbc = UblWriter::NS_CBC;

        // BT-24: Specification identifier
        $specificationNode = $xml->get("{{$cbc}}CustomizationID");
        if ($specificationNode !== null) {
            $specification = $specificationNode->asText();
            $invoice->setSpecification($specification);

            // Try to create from preset
            $presetClassname = $this->getPresetFromSpecification($specification);
            if ($presetClassname !== null) {
                $invoice = new Invoice($presetClassname);
            }
        }

        // Index tax exemption reasons
        /** @var array<string, array{code: string|null, reason: string|null}> */
        $taxExemptions = [];
        foreach ($xml->getAll("{{$cac}}TaxTotal/{{$cac}}TaxSubtotal/{{$cac}}TaxCategory") as $node) {
            $exemptionReasonCodeNode = $node->get("{{$cbc}}TaxExemptionReasonCode");
            $exemptionReasonNode = $node->get("{{$cbc}}TaxExemptionReason");
            if ($exemptionReasonCodeNode === null && $exemptionReasonNode === null) continue;

            // Get tax subtotal key
            $categoryNode = $node->get("{{$cbc}}ID");
            if ($categoryNode === null) {
                throw new InvalidArgumentException('Missing <cbc:ID /> node from tax item');
            }
            $rateNode = $node->get("{{$cbc}}Percent");
            $rateKey = ($rateNode === null) ? '' : floatval($rateNode->asText());
            $key = "{$categoryNode->asText()}:{$rateKey}";

            // Save reasons
            $taxExemptions[$key] = [
                "code" => ($exemptionReasonCodeNode === null) ? null : $exemptionReasonCodeNode->asText(),
                "reason" => ($exemptionReasonNode === null) ? null : $exemptionReasonNode->asText(),
            ];
        }

        // BT-23: Business process type
        $businessProcessNode = $xml->get("{{$cbc}}ProfileID");
        if ($businessProcessNode !== null) {
            $invoice->setBusinessProcess($businessProcessNode->asText());
        }

        // BT-1: Invoice number
        $numberNode = $xml->get("{{$cbc}}ID");
        if ($numberNode !== null) {
            $invoice->setNumber($numberNode->asText());
        }

        // BT-2: Issue date
        $issueDateNode = $xml->get("{{$cbc}}IssueDate");
        if ($issueDateNode !== null) {
            $invoice->setIssueDate(new DateTime($issueDateNode->asText()));
        }

        // BT-9: Due date
        $dueDateNode = $xml->get("{{$cbc}}DueDate");
        if ($dueDateNode !== null) {
            $invoice->setDueDate(new DateTime($dueDateNode->asText()));
        }

        // BT-3: Invoice type code
        $typeNode = $xml->get("{{$cbc}}InvoiceTypeCode");
        if ($typeNode !== null) {
            $invoice->setType((int) $typeNode->asText());
        }

        // BT-22: Notes
        foreach ($xml->getAll("{{$cbc}}Note") as $noteNode) {
            $invoice->addNote($noteNode->asText());
        }

        // BT-7: Tax point date
        $taxPointDateNode = $xml->get("{{$cbc}}TaxPointDate");
        if ($taxPointDateNode !== null) {
            $invoice->setTaxPointDate(new DateTime($taxPointDateNode->asText()));
        }

        // BT-5: Invoice currency code
        $currencyNode = $xml->get("{{$cbc}}DocumentCurrencyCode");
        if ($currencyNode !== null) {
            $invoice->setCurrency($currencyNode->asText());
        }

        // BT-6: VAT accounting currency code
        $vatCurrencyNode = $xml->get("{{$cbc}}TaxCurrencyCode");
        if ($vatCurrencyNode !== null) {
            $invoice->setVatCurrency($vatCurrencyNode->asText());
        }

        // BT-19: Buyer accounting reference
        $buyerAccountingReferenceNode = $xml->get("{{$cbc}}AccountingCost");
        if ($buyerAccountingReferenceNode !== null) {
            $invoice->setBuyerAccountingReference($buyerAccountingReferenceNode->asText());
        }

        // BT-10: Buyer reference
        $buyerReferenceNode = $xml->get("{{$cbc}}BuyerReference");
        if ($buyerReferenceNode !== null) {
            $invoice->setBuyerReference($buyerReferenceNode->asText());
        }

        // BG-14: Invoice period
        $this->parsePeriodFields($xml, $invoice);

        // BT-13: Purchase order reference
        $purchaseOrderReferenceNode = $xml->get("{{$cac}}OrderReference/{{$cbc}}ID");
        if ($purchaseOrderReferenceNode !== null) {
            $invoice->setPurchaseOrderReference($purchaseOrderReferenceNode->asText());
        }

        // BT-14: Sales order reference
        $salesOrderReferenceNode = $xml->get("{{$cac}}OrderReference/{{$cbc}}SalesOrderID");
        if ($salesOrderReferenceNode !== null) {
            $invoice->setSalesOrderReference($salesOrderReferenceNode->asText());
        }

        // BG-3: Preceding invoice references
        foreach ($xml->getAll("{{$cac}}BillingReference/{{$cac}}InvoiceDocumentReference") as $node) {
            $invoiceReferenceValueNode = $node->get("{{$cbc}}ID");
            if ($invoiceReferenceValueNode === null) {
                continue;
            }
            $invoiceReference = new InvoiceReference($invoiceReferenceValueNode->asText());
            $invoiceReferenceIssueDateNode = $node->get("{{$cbc}}IssueDate");
            if ($invoiceReferenceIssueDateNode !== null) {
                $invoiceReference->setIssueDate(new DateTime($invoiceReferenceIssueDateNode->asText()));
            }
            $invoice->addPrecedingInvoiceReference($invoiceReference);
        }

        // BT-17: Tender or lot reference
        $tenderOrLotReferenceNode = $xml->get("{{$cac}}OriginatorDocumentReference/{{$cbc}}ID");
        if ($tenderOrLotReferenceNode !== null) {
            $invoice->setTenderOrLotReference($tenderOrLotReferenceNode->asText());
        }

        // BT-12: Contract reference
        $contractReferenceNode = $xml->get("{{$cac}}ContractDocumentReference/{{$cbc}}ID");
        if ($contractReferenceNode !== null) {
            $invoice->setContractReference($contractReferenceNode->asText());
        }

        // BG-24: Attachment nodes
        foreach ($xml->getAll("{{$cac}}AdditionalDocumentReference") as $node) {
            $invoice->addAttachment($this->parseAttachmentNode($node));
        }

        // Seller node
        $sellerNode = $xml->get("{{$cac}}AccountingSupplierParty/{{$cac}}Party");
        if ($sellerNode !== null) {
            $invoice->setSeller($this->parseSellerOrBuyerNode($sellerNode));
        }

        // Buyer node
        $buyerNode = $xml->get("{{$cac}}AccountingCustomerParty/{{$cac}}Party");
        if ($buyerNode !== null) {
            $invoice->setBuyer($this->parseSellerOrBuyerNode($buyerNode));
        }

        // Payee node
        $payeeNode = $xml->get("{{$cac}}PayeeParty");
        if ($payeeNode !== null) {
            $invoice->setPayee($this->parsePayeeNode($payeeNode));
        }

        // Delivery node
        $deliveryNode = $xml->get("{{$cac}}Delivery");
        if ($deliveryNode !== null) {
            $invoice->setDelivery($this->parseDeliveryNode($deliveryNode));
        }

        // Payment nodes
        $payment = $this->parsePaymentNodes($xml);
        $invoice->setPayment($payment);

        // Allowances and charges
        foreach ($xml->getAll("{{$cac}}AllowanceCharge") as $node) {
            $this->addAllowanceOrCharge($invoice, $node, $taxExemptions);
        }

        // BT-111: Total VAT amount in accounting currency
        foreach ($xml->getAll("{{$cac}}TaxTotal") as $taxTotalNode) {
            if ($taxTotalNode->get("{{$cac}}TaxSubtotal") !== null) {
                // The other tax total node, then
                continue;
            }
            $taxAmountNode = $taxTotalNode->get("{{$cbc}}TaxAmount");
            if ($taxAmountNode !== null) {
                $invoice->setCustomVatAmount((float) $taxAmountNode->asText());
            }
        }

        // BT-113: Paid amount
        $paidAmountNode = $xml->get("{{$cac}}LegalMonetaryTotal/{{$cbc}}PrepaidAmount");
        if ($paidAmountNode !== null) {
            $invoice->setPaidAmount((float) $paidAmountNode->asText());
        }

        // BT-114: Rounding amount
        $roundingAmountNode = $xml->get("{{$cac}}LegalMonetaryTotal/{{$cbc}}PayableRoundingAmount");
        if ($roundingAmountNode !== null) {
            $invoice->setRoundingAmount((float) $roundingAmountNode->asText());
        }

        // Invoice lines
        foreach ($xml->getAll("{{$cac}}InvoiceLine") as $node) {
            $invoice->addLine($this->parseInvoiceLine($node, $taxExemptions));
        }

        return $invoice;
    }


    /**
     * Parse identifier node
     * @param  UXML       $xml        XML node
     * @param  string     $schemeAttr Scheme attribute name
     * @return Identifier             Identifier instance
     */
    private function parseIdentifierNode(UXML $xml, string $schemeAttr="schemeID"): Identifier {
        $value = $xml->asText();
        $scheme = $xml->element()->hasAttribute($schemeAttr) ? $xml->element()->getAttribute($schemeAttr) : null;
        return new Identifier($value, $scheme);
    }


    /**
     * Parse period fields
     * @param UXML                $xml    XML node
     * @param Invoice|InvoiceLine $target Destination instance
     */
    private function parsePeriodFields(UXML $xml, $target) {
        $cac = UblWriter::NS_CAC;
        $cbc = UblWriter::NS_CBC;

        // Period start date
        $startDateNode = $xml->get("{{$cac}}InvoicePeriod/{{$cbc}}StartDate");
        if ($startDateNode !== null) {
            $target->setPeriodStartDate(new DateTime($startDateNode->asText()));
        }

        // Period end date
        $endDateNode = $xml->get("{{$cac}}InvoicePeriod/{{$cbc}}EndDate");
        if ($endDateNode !== null) {
            $target->setPeriodEndDate(new DateTime($endDateNode->asText()));
        }
    }


    /**
     * Parse postal address fields
     * @param UXML           $xml    XML node
     * @param Delivery|Party $target Destination instance
     */
    private function parsePostalAddressFields(UXML $xml, $target) {
        $cac = UblWriter::NS_CAC;
        $cbc = UblWriter::NS_CBC;

        // Postal address
        $addressNodes = array_filter([
            $xml->get("{{$cbc}}StreetName"),
            $xml->get("{{$cbc}}AdditionalStreetName"),
            $xml->get("{{$cac}}AddressLine/{{$cbc}}Line")
        ]);
        $addressLines = array_map(function($node) {
            return $node->asText();
        }, $addressNodes);
        // @phan-suppress-next-line PhanThrowTypeAbsentForCall
        $target->setAddress($addressLines);

        // City name
        $cityNode = $xml->get("{{$cbc}}CityName");
        if ($cityNode !== null) {
            $target->setCity($cityNode->asText());
        }

        // Postal code
        $postalCodeNode = $xml->get("{{$cbc}}PostalZone");
        if ($postalCodeNode !== null) {
            $target->setPostalCode($postalCodeNode->asText());
        }

        // Subdivision
        $subdivisionNode = $xml->get("{{$cbc}}CountrySubentity");
        if ($subdivisionNode !== null) {
            $target->setSubdivision($subdivisionNode->asText());
        }

        // Country
        $countryNode = $xml->get("{{$cac}}Country/{{$cbc}}IdentificationCode");
        if ($countryNode !== null) {
            $target->setCountry($countryNode->asText());
        }
    }


    /**
     * Parse seller or buyer node
     * @param  UXML  $xml XML node
     * @return Party      Party instance
     */
    private function parseSellerOrBuyerNode(UXML $xml): Party {
        $party = new Party();
        $cac = UblWriter::NS_CAC;
        $cbc = UblWriter::NS_CBC;

        // Electronic address
        $electronicAddressNode = $xml->get("{{$cbc}}EndpointID");
        if ($electronicAddressNode !== null) {
            $party->setElectronicAddress($this->parseIdentifierNode($electronicAddressNode));
        }

        // Additional identifiers
        foreach ($xml->getAll("{{$cac}}PartyIdentification/{{$cbc}}ID") as $identifierNode) {
            $party->addIdentifier($this->parseIdentifierNode($identifierNode));
        }

        // Trading name
        $tradingNameNode = $xml->get("{{$cac}}PartyName/{{$cbc}}Name");
        if ($tradingNameNode !== null) {
            $party->setTradingName($tradingNameNode->asText());
        }

        // Postal address
        $addressNode = $xml->get("{{$cac}}PostalAddress");
        if ($addressNode !== null) {
            $this->parsePostalAddressFields($addressNode, $party);
        }

        // VAT number and tax registration identifier
        foreach ($xml->getAll("{{$cac}}PartyTaxScheme") as $taxNode) {
            $companyIdNode = $taxNode->get("{{$cbc}}CompanyID");
            if ($companyIdNode === null) continue;
            $companyId = $companyIdNode->asText();

            $taxSchemeNode = $taxNode->get("{{$cac}}TaxScheme/{{$cbc}}ID");
            $taxScheme = ($taxSchemeNode === null) ? null : $taxSchemeNode->asText();

            if ($taxScheme === "VAT") {
                $party->setVatNumber($companyId);
            } else {
                $party->setTaxRegistrationId(new Identifier($companyId, $taxScheme));
            }
        }

        // Legal name
        $legalNameNode = $xml->get("{{$cac}}PartyLegalEntity/{{$cbc}}RegistrationName");
        if ($legalNameNode !== null) {
            $party->setName($legalNameNode->asText());
        }

        // Company ID
        $companyIdNode = $xml->get("{{$cac}}PartyLegalEntity/{{$cbc}}CompanyID");
        if ($companyIdNode !== null) {
            $party->setCompanyId($this->parseIdentifierNode($companyIdNode));
        }

        // BT-33: Seller additional legal information
        $companyLegalFormNode = $xml->get("{{$cac}}PartyLegalEntity/{{$cbc}}CompanyLegalForm");
        if ($companyLegalFormNode !== null) {
            $party->setLegalInformation($companyLegalFormNode->asText());
        }

        // Contact name
        $contactNameNode = $xml->get("{{$cac}}Contact/{{$cbc}}Name");
        if ($contactNameNode !== null) {
            $party->setContactName($contactNameNode->asText());
        }

        // Contact phone
        $contactPhoneNode = $xml->get("{{$cac}}Contact/{{$cbc}}Telephone");
        if ($contactPhoneNode !== null) {
            $party->setContactPhone($contactPhoneNode->asText());
        }

        // Contact email
        $contactEmailNode = $xml->get("{{$cac}}Contact/{{$cbc}}ElectronicMail");
        if ($contactEmailNode !== null) {
            $party->setContactEmail($contactEmailNode->asText());
        }

        return $party;
    }


    /**
     * Parse payee node
     * @param  UXML  $xml XML node
     * @return Party      Party instance
     */
    private function parsePayeeNode(UXML $xml): Party {
        $party = new Party();
        $cac = UblWriter::NS_CAC;
        $cbc = UblWriter::NS_CBC;

        // Additional identifiers
        foreach ($xml->getAll("{{$cac}}PartyIdentification/{{$cbc}}ID") as $identifierNode) {
            $party->addIdentifier($this->parseIdentifierNode($identifierNode));
        }

        // Party name
        $nameNode = $xml->get("{{$cac}}PartyName/{{$cbc}}Name");
        if ($nameNode !== null) {
            $party->setName($nameNode->asText());
        }

        // Company ID
        $companyIdNode = $xml->get("{{$cac}}PartyLegalEntity/{{$cbc}}CompanyID");
        if ($companyIdNode !== null) {
            $party->setCompanyId($this->parseIdentifierNode($companyIdNode));
        }

        return $party;
    }


    /**
     * Parse delivery node
     * @param  UXML     $xml XML node
     * @return Delivery      Delivery instance
     */
    private function parseDeliveryNode(UXML $xml): Delivery {
        $delivery = new Delivery();
        $cac = UblWriter::NS_CAC;
        $cbc = UblWriter::NS_CBC;

        // BT-72: Actual delivery date
        $dateNode = $xml->get("{{$cbc}}ActualDeliveryDate");
        if ($dateNode !== null) {
            $delivery->setDate(new DateTime($dateNode->asText()));
        }

        // BT-71: Delivery location identifier
        $locationIdentifierNode = $xml->get("{{$cac}}DeliveryLocation/{{$cbc}}ID");
        if ($locationIdentifierNode !== null) {
            $delivery->setLocationIdentifier($this->parseIdentifierNode($locationIdentifierNode));
        }

        // Delivery postal address
        $addressNode = $xml->get("{{$cac}}DeliveryLocation/{{$cac}}Address");
        if ($addressNode !== null) {
            $this->parsePostalAddressFields($addressNode, $delivery);
        }

        // BT-70: Deliver name
        $nameNode = $xml->get("{{$cac}}DeliveryParty/{{$cac}}PartyName/{{$cbc}}Name");
        if ($nameNode !== null) {
            $delivery->setName($nameNode->asText());
        }

        return $delivery;
    }


    /**
     * Parse payment nodes
     * @param  UXML         $xml XML node
     * @return Payment|null      Payment instance or NULL if not found
     */
    private function parsePaymentNodes(UXML $xml): ?Payment {
        $cac = UblWriter::NS_CAC;
        $cbc = UblWriter::NS_CBC;

        // Get root nodes
        $meansNode = $xml->get("{{$cac}}PaymentMeans");
        $termsNode = $xml->get("{{$cac}}PaymentTerms/{{$cbc}}Note");
        if ($meansNode === null && $termsNode === null) return null;

        $payment = new Payment();

        // BT-81: Payment means code
        // BT-82: Payment means name
        $meansCodeNode = $xml->get("{{$cac}}PaymentMeans/{{$cbc}}PaymentMeansCode");
        if ($meansCodeNode !== null) {
            $payment->setMeansCode($meansCodeNode->asText());
            if ($meansCodeNode->element()->hasAttribute('name')) {
                $payment->setMeansText($meansCodeNode->element()->getAttribute('name'));
            }
        }

        // BT-83: Payment ID
        $paymentIdNode = $xml->get("{{$cac}}PaymentMeans/{{$cbc}}PaymentID");
        if ($paymentIdNode !== null) {
            $payment->setId($paymentIdNode->asText());
        }

        // BG-18: Payment card
        $cardNode = $xml->get("{{$cac}}PaymentMeans/{{$cac}}CardAccount");
        if ($cardNode !== null) {
            $payment->setCard($this->parsePaymentCardNode($cardNode));
        }

        // BG-17: Payment transfers
        $transferNodes = $xml->getAll("{{$cac}}PaymentMeans/{{$cac}}PayeeFinancialAccount");
        foreach ($transferNodes as $transferNode) {
            $payment->addTransfer($this->parsePaymentTransferNode($transferNode));
        }

        // BG-19: Payment mandate
        $mandateNode = $xml->get("{{$cac}}PaymentMeans/{{$cac}}PaymentMandate");
        if ($mandateNode !== null) {
            $payment->setMandate($this->parsePaymentMandateNode($mandateNode));
        }

        // BT-20: Payment terms
        if ($termsNode !== null) {
            $payment->setTerms($termsNode->asText());
        }

        return $payment;
    }


    /**
     * Parse payment card node
     * @param  UXML $xml Payment card node
     * @return Card      Card instance
     */
    private function parsePaymentCardNode(UXML $xml): Card {
        $card = new Card();
        $cbc = UblWriter::NS_CBC;

        // BT-87: Card PAN
        $panNode = $xml->get("{{$cbc}}PrimaryAccountNumberID");
        if ($panNode !== null) {
            $card->setPan($panNode->asText());
        }

        // Card network
        $networkNode = $xml->get("{{$cbc}}NetworkID");
        if ($networkNode !== null) {
            $card->setNetwork($networkNode->asText());
        }

        // BT-88: Holder name
        $holderNode = $xml->get("{{$cbc}}HolderName");
        if ($holderNode !== null) {
            $card->setHolder($holderNode->asText());
        }

        return $card;
    }


    /**
     * Parse payment transfer node
     * @param  UXML     $xml Payment transfer node
     * @return Transfer      Transfer instance
     */
    private function parsePaymentTransferNode(UXML $xml): Transfer {
        $transfer = new Transfer();
        $cac = UblWriter::NS_CAC;
        $cbc = UblWriter::NS_CBC;

        // BT-84: Receiving account ID
        $accountIdNode = $xml->get("{{$cbc}}ID");
        if ($accountIdNode !== null) {
            $transfer->setAccountId($accountIdNode->asText());
        }

        // BT-85: Receiving account name
        $accountNameNode = $xml->get("{{$cbc}}Name");
        if ($accountNameNode !== null) {
            $transfer->setAccountName($accountNameNode->asText());
        }

        // BT-86: Service provider ID
        $providerNode = $xml->get("{{$cac}}FinancialInstitutionBranch/{{$cbc}}ID");
        if ($providerNode !== null) {
            $transfer->setProvider($providerNode->asText());
        }

        return $transfer;
    }


    /**
     * Parse payment mandate node
     * @param  UXML    $xml Payment mandate node
     * @return Mandate      Mandate instance
     */
    private function parsePaymentMandateNode(UXML $xml): Mandate {
        $mandate = new Mandate();
        $cac = UblWriter::NS_CAC;
        $cbc = UblWriter::NS_CBC;

        // BT-89: Mandate reference
        $referenceNode = $xml->get("{{$cbc}}ID");
        if ($referenceNode !== null) {
            $mandate->setReference($referenceNode->asText());
        }

        // BT-91: Debited account
        $accountNode = $xml->get("{{$cac}}PayerFinancialAccount/{{$cbc}}ID");
        if ($accountNode !== null) {
            $mandate->setAccount($accountNode->asText());
        }

        return $mandate;
    }


    /**
     * Set VAT attributes
     * @param VatTrait $target         Target instance
     * @param UXML     $xml            XML node
     * @param array    &$taxExemptions Tax exemption reasons
     */
    private function setVatAttributes($target, UXML $xml, array $taxExemptions) {
        $cbc = UblWriter::NS_CBC;

        // Tax category
        $taxCategoryNode = $xml->get("{{$cbc}}ID");
        if ($taxCategoryNode !== null) {
            $target->setVatCategory($taxCategoryNode->asText());
        }

        // Tax rate
        $taxRateNode = $xml->get("{{$cbc}}Percent");
        if ($taxRateNode !== null) {
            $target->setVatRate((float) $taxRateNode->asText());
        }

        // Tax exemption reasons
        $rateKey = $target->getVatRate() ?? '';
        $key = "{$target->getVatCategory()}:{$rateKey}";
        $target->setVatExemptionReasonCode($taxExemptions[$key]['code'] ?? null);
        $target->setVatExemptionReason($taxExemptions[$key]['reason'] ?? null);
    }


    /**
     * Add allowance or charge
     * @param Invoice|InvoiceLine $target         Target instance
     * @param UXML                $xml            XML node
     * @param array               &$taxExemptions Tax exemption reasons
     */
    private function addAllowanceOrCharge($target, UXML $xml, array &$taxExemptions) {
        $allowanceOrCharge = new AllowanceOrCharge();
        $cac = UblWriter::NS_CAC;
        $cbc = UblWriter::NS_CBC;

        // Add instance to invoice
        $chargeIndicatorNode = $xml->get("{{$cbc}}ChargeIndicator");
        if ($chargeIndicatorNode !== null && $chargeIndicatorNode->asText() === "true") {
            $target->addCharge($allowanceOrCharge);
        } else {
            $target->addAllowance($allowanceOrCharge);
        }

        // Reason code
        $reasonCodeNode = $xml->get("{{$cbc}}AllowanceChargeReasonCode");
        if ($reasonCodeNode !== null) {
            $allowanceOrCharge->setReasonCode($reasonCodeNode->asText());
        }

        // Reason text
        $reasonTextNode = $xml->get("{{$cbc}}AllowanceChargeReason");
        if ($reasonTextNode !== null) {
            $allowanceOrCharge->setReason($reasonTextNode->asText());
        }

        // Amount
        $factorNode = $xml->get("{{$cbc}}MultiplierFactorNumeric");
        $amountNode = $xml->get("{{$cbc}}Amount");
        if ($factorNode !== null) {
            $percent = (float) $factorNode->asText();
            $allowanceOrCharge->markAsPercentage()->setAmount($percent);
        } elseif ($amountNode !== null) {
            $amount = (float) $amountNode->asText();
            $allowanceOrCharge->setAmount($amount);
        } else {
            throw new InvalidArgumentException('Missing both <cbc:Amount /> and <cbc:MultiplierFactorNumeric />' .
                ' nodes from allowance/charge');
        }

        // VAT attributes
        $vatNode = $xml->get("{{$cac}}TaxCategory");
        if ($vatNode !== null) {
            $this->setVatAttributes($allowanceOrCharge, $vatNode, $taxExemptions);
        }
    }


    /**
     * Parse invoice line
     * @param  UXML        $xml            XML node
     * @param  array       &$taxExemptions Tax exemption reasons
     * @return InvoiceLine                 Invoice line instance
     */
    private function parseInvoiceLine(UXML $xml, array &$taxExemptions): InvoiceLine {
        $line = new InvoiceLine();
        $cac = UblWriter::NS_CAC;
        $cbc = UblWriter::NS_CBC;

        // BT-126: Invoice line identifier
        $lineId = $xml->get("{{$cbc}}ID");
        if ($lineId !== null) {
            $line->setId($lineId->asText());
        }

        // BT-127: Invoice line note
        $noteNode = $xml->get("{{$cbc}}Note");
        if ($noteNode !== null) {
            $line->setNote($noteNode->asText());
        }

        // Quantity
        $quantityNode = $xml->get("{{$cbc}}InvoicedQuantity");
        if ($quantityNode !== null) {
            $line->setQuantity((float) $quantityNode->asText());
            $line->setUnit($quantityNode->element()->getAttribute('unitCode'));
        }

        // BT-133: Buyer accounting reference
        $buyerAccountingReferenceNode = $xml->get("{{$cbc}}AccountingCost");
        if ($buyerAccountingReferenceNode !== null) {
            $line->setBuyerAccountingReference($buyerAccountingReferenceNode->asText());
        }

        // BG-26: Invoice line period
        $this->parsePeriodFields($xml, $line);

        // BT-132: Order line reference
        $orderLineReferenceNode = $xml->get("{{$cac}}OrderLineReference/{{$cbc}}LineID");
        if ($orderLineReferenceNode !== null) {
            $line->setOrderLineReference($orderLineReferenceNode->asText());
        }

        // Allowances and charges
        foreach ($xml->getAll("{{$cac}}AllowanceCharge") as $node) {
            $this->addAllowanceOrCharge($line, $node, $taxExemptions);
        }

        // BT-154: Item description
        $descriptionNode = $xml->get("{{$cac}}Item/{{$cbc}}Description");
        if ($descriptionNode !== null) {
            $line->setDescription($descriptionNode->asText());
        }

        // BT-153: Item name
        $nameNode = $xml->get("{{$cac}}Item/{{$cbc}}Name");
        if ($nameNode !== null) {
            $line->setName($nameNode->asText());
        }

        // BT-156: Buyer identifier
        $buyerIdentifierNode = $xml->get("{{$cac}}Item/{{$cac}}BuyersItemIdentification/{{$cbc}}ID");
        if ($buyerIdentifierNode !== null) {
            $line->setBuyerIdentifier($buyerIdentifierNode->asText());
        }

        // BT-155: Seller identifier
        $sellerIdentifierNode = $xml->get("{{$cac}}Item/{{$cac}}SellersItemIdentification/{{$cbc}}ID");
        if ($sellerIdentifierNode !== null) {
            $line->setSellerIdentifier($sellerIdentifierNode->asText());
        }

        // BT-157: Standard identifier
        $standardIdentifierNode = $xml->get("{{$cac}}Item/{{$cac}}StandardItemIdentification/{{$cbc}}ID");
        if ($standardIdentifierNode !== null) {
            $line->setStandardIdentifier($this->parseIdentifierNode($standardIdentifierNode));
        }

        // BT-159: Item origin country
        $originCountryNode = $xml->get("{{$cac}}Item/{{$cac}}OriginCountry/{{$cbc}}IdentificationCode");
        if ($originCountryNode !== null) {
            $line->setOriginCountry($originCountryNode->asText());
        }

        // BT-158: Item classification identifiers
        $classNodes = $xml->getAll("{{$cac}}Item/{{$cac}}CommodityClassification/{{$cbc}}ItemClassificationCode");
        foreach ($classNodes as $classNode) {
            $line->addClassificationIdentifier($this->parseIdentifierNode($classNode, 'listID'));
        }

        // Price amount
        $priceNode = $xml->get("{{$cac}}Price/{{$cbc}}PriceAmount");
        if ($priceNode !== null) {
            $line->setPrice((float) $priceNode->asText());
        }

        // Base quantity
        $baseQuantityNode = $xml->get("{{$cac}}Price/{{$cbc}}BaseQuantity");
        if ($baseQuantityNode !== null) {
            $line->setBaseQuantity((float) $baseQuantityNode->asText());
        }

        // VAT attributes
        $vatNode = $xml->get("{{$cac}}Item/{{$cac}}ClassifiedTaxCategory");
        if ($vatNode !== null) {
            $this->setVatAttributes($line, $vatNode, $taxExemptions);
        }

        // BG-32: Item attributes
        $attributeNodes = $xml->getAll("{{$cac}}Item/{{$cac}}AdditionalItemProperty");
        foreach ($attributeNodes as $attributeNode) {
            $attributeNameNode = $attributeNode->get("{{$cbc}}Name");
            $attributeValueNode = $attributeNode->get("{{$cbc}}Value");
            if ($attributeNameNode !== null && $attributeValueNode !== null) {
                $line->addAttribute(new Attribute($attributeNameNode->asText(), $attributeValueNode->asText()));
            }
        }

        return $line;
    }

    /**
     * Parse attachment node
     * @param  UXML       $xml XML node
     * @return Attachment      Attachment instance
     */
    private function parseAttachmentNode(UXML $xml): Attachment {
        $attachment = new Attachment();
        $cac = UblWriter::NS_CAC;
        $cbc = UblWriter::NS_CBC;

        // BT-122: Supporting document reference
        $identifierNode = $xml->get("{{$cbc}}ID");
        if ($identifierNode !== null) {
            $attachment->setId($this->parseIdentifierNode($identifierNode));
        }

        // BT-123: Supporting document description
        $descriptionNode = $xml->get("{{$cbc}}DocumentDescription");
        if ($descriptionNode !== null) {
            $attachment->setDescription($descriptionNode->asText());
        }

        // BT-125: Attached document
        $embeddedDocumentNode = $xml->get("{{$cac}}Attachment/{{$cbc}}EmbeddedDocumentBinaryObject");
        if ($embeddedDocumentNode !== null) {
            $embeddedDocumentElement = $embeddedDocumentNode->element();
            // @phan-suppress-next-line PhanPossiblyFalseTypeArgument
            $attachment->setContents(base64_decode($embeddedDocumentNode->asText()));
            if ($embeddedDocumentElement->hasAttribute('mimeCode')) {
                $attachment->setMimeCode($embeddedDocumentElement->getAttribute('mimeCode'));
            }
            if ($embeddedDocumentElement->hasAttribute('filename')) {
                $attachment->setFilename($embeddedDocumentElement->getAttribute('filename'));
            }
        }

        // BT-124: External document location
        $externalDocumentNode = $xml->get("{{$cac}}Attachment/{{$cac}}ExternalReference/{{$cbc}}URI");
        if ($externalDocumentNode !== null) {
            $attachment->setExternalUrl($externalDocumentNode->asText());
        }

        return $attachment;
    }
}
