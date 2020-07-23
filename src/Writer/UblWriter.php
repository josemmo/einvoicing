<?php
namespace Einvoicing\Writer;

use Einvoicing\AllowanceCharge\AllowanceChargeBase;
use Einvoicing\AllowanceCharge\Charge;
use Einvoicing\Invoice\Invoice;
use Einvoicing\Invoice\InvoiceTotals;
use Einvoicing\InvoiceLine\InvoiceLine;
use Einvoicing\Party\Party;

class UblWriter extends XmlWriter {
    const NS_INVOICE = "urn:oasis:names:specification:ubl:schema:xsd:Invoice-2";
    const NS_CAC = "urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2";
    const NS_CBC = "urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2";

    /**
     * @inheritdoc
     */
    public function export(Invoice $invoice): string {
        $doc = new \DOMDocument('1.0', 'UTF-8');
        $doc->formatOutput = true;

        // Create root element
        $root = $doc->createElement('Invoice');
        $root->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns', self::NS_INVOICE);
        $root->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:cac', self::NS_CAC);
        $root->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:cbc', self::NS_CBC);
        $doc->appendChild($root);

        // BT-24: Specification indentifier
        $root->appendChild($doc->createElement('cbc:CustomizationID', $invoice->getSpecificationIdentifier()));

        // BT-1: Invoice number
        $number = $invoice->getNumber();
        if ($number === null) {
            throw new ExportException('An Invoice shall have an Invoice number (BT-1)', 'BR-2');
        }
        $root->appendChild($doc->createElement('cbc:ID', $number));

        // BT-2: Issue date
        $issueDate = $invoice->getIssueDate();
        if ($issueDate === null) {
            throw new ExportException('An Invoice shall have an Invoice issue date (BT-2)', 'BR-3');
        }
        $root->appendChild($doc->createElement('cbc:IssueDate', $issueDate->format('Y-m-d')));

        // BT-9: Due date
        $dueDate = $invoice->getDueDate();
        if ($dueDate !== null) {
            $root->appendChild($doc->createElement('cbc:DueDate', $dueDate->format('Y-m-d')));
        }

        // BT-3: Invoice type code
        $root->appendChild($doc->createElement('cbc:InvoiceTypeCode', (string) $invoice->getType()));

        // BT-22: Note
        $note = $invoice->getNote();
        if ($note !== null) {
            $root->appendChild($doc->createElement('cbc:Note', $note));
        }

        // BT-5: Invoice currency code
        $root->appendChild($doc->createElement('cbc:DocumentCurrencyCode', $invoice->getCurrency()));

        // Seller node
        $seller = $invoice->getSeller();
        if ($seller === null) {
            throw new ExportException('An Invoice shall contain the Seller name (BT-27)', 'BR-6');
        }
        $root->appendChild($this->getSellerOrBuyerNode($seller, $doc, true));

        // Buyer node
        $buyer = $invoice->getBuyer();
        if ($buyer === null) {
            throw new ExportException('An Invoice shall contain the Buyer name (BT-44)', 'BR-7');
        }
        $root->appendChild($this->getSellerOrBuyerNode($buyer, $doc, false));

        // Payee node
        $payee = $invoice->getPayee();
        if ($payee !== null) {
            $root->appendChild($this->getPayeeNode($payee, $doc));
        }

        // Allowances and charges
        foreach (array_merge($invoice->getAllowances(), $invoice->getCharges()) as $item) {
            $root->appendChild($this->getAllowanceChargeNode($item, $invoice, null, $doc));
        }

        // Invoice totals
        $totals = $invoice->getTotals();
        $root->appendChild($this->getTaxTotalNode($totals, $doc));
        $root->appendChild($this->getDocumentTotalsNode($totals, $doc));

        // Invoice lines
        $lines = $invoice->getLines();
        if (empty($lines)) {
            throw new ExportException('An Invoice shall have at least one Invoice line (BG-25)', 'BR-16');
        }
        foreach ($lines as $i=>$line) {
            $root->appendChild($this->getLineNode($line, $i+1, $invoice, $doc));
        }

        return $doc->saveXML();
    }


    /**
     * Get seller or buyer node
     * @param  Party        $party    Party instance
     * @param  \DOMDocument $doc      DOM Document
     * @param  boolean      $isSeller Is seller
     * @return \DOMElement            DOM Element
     * @throws ExportException if failed to generate party node
     */
    private function getSellerOrBuyerNode(Party $party, \DOMDocument $doc, bool $isSeller): \DOMElement {
        $xml = $doc->createElement('cac:Party');

        // Trading name
        $tradingName = $party->getTradingName();
        if ($tradingName !== null) {
            $partyNameNode = $doc->createElement('cac:PartyName');
            $partyNameNode->appendChild($doc->createElement('cbc:Name', $tradingName));
            $xml->appendChild($partyNameNode);
        }

        // Initial postal address node
        $addressNode = $doc->createElement('cac:PostalAddress');
        $xml->appendChild($addressNode);

        // Street name
        $addressLines = $party->getAddress();
        if (isset($addressLines[0])) {
            $addressNode->appendChild($doc->createElement('cbc:StreetName', $addressLines[0]));
        }

        // Additional street name
        if (isset($addressLines[1])) {
            $addressNode->appendChild($doc->createElement('cbc:AdditionalStreetName', $addressLines[1]));
        }

        // City name
        $cityName = $party->getCity();
        if ($cityName !== null) {
            $addressNode->appendChild($doc->createElement('cbc:CityName', $cityName));
        }

        // Postal code
        $postalCode = $party->getPostalCode();
        if ($postalCode !== null) {
            $addressNode->appendChild($doc->createElement('cbc:PostalZone', $postalCode));
        }

        // Subdivision
        $subdivision = $party->getSubdivision();
        if ($subdivision !== null) {
            $addressNode->appendChild($doc->createElement('cbc:CountrySubentity', $subdivision));
        }

        // Address line (third address line)
        if (isset($addressLines[2])) {
            $thirdLine = $doc->createElement('cac:AddressLine');
            $thirdLine->appendChild($doc->createElement('cbc:Line', $addressLines[2]));
            $addressNode->appendChild($thirdLine);
        }

        // Country
        $country = $party->getCountry();
        if ($country === null) {
            throw new ExportException(
                $isSeller ? 'The Seller postal address (BG-5) shall contain a Seller country code (BT-40)' :
                            'The Buyer postal address shall contain a Buyer country code (BT-55)',
                $isSeller ? 'BR-9' : 'BR-11'
            );
        }
        $countryNode = $doc->createElement('cac:Country');
        $countryNode->appendChild($doc->createElement('cbc:IdentificationCode', $country));
        $addressNode->appendChild($countryNode);

        // VAT number
        $vatNumber = $party->getVatNumber();
        if ($isSeller && $vatNumber === null) {
            throw new ExportException('The Seller VAT identifier (BT-31) shall be present', 'BR-CO-9');
        }
        if ($vatNumber !== null) {
            $taxNode = $doc->createElement('cac:PartyTaxScheme');
            $taxNode->appendChild($doc->createElement('cbc:CompanyID', $vatNumber));
            $taxSchemeNode = $doc->createElement('cac:TaxScheme');
            $taxSchemeNode->appendChild($doc->createElement('cbc:ID', 'VAT'));
            $taxNode->appendChild($taxSchemeNode);
            $xml->appendChild($taxNode);
        }

        // Initial legal entity node
        $legalEntityNode = $doc->createElement('cac:PartyLegalEntity');
        $xml->appendChild($legalEntityNode);

        // Legal name
        $legalName = $party->getName();
        if ($legalName === null) {
            throw new ExportException(
                $isSeller ? 'An Invoice shall contain the Seller name (BT-27)' :
                            'An Invoice shall contain the Buyer name (BT-44)',
                $isSeller ? 'BR-6' : 'BR-7'
            );
        }
        $legalEntityNode->appendChild($doc->createElement('cbc:RegistrationName', $legalName));

        // Company ID
        $companyId = $party->getCompanyId();
        if ($companyId !== null) {
            $legalEntityNode->appendChild($doc->createElement('cbc:CompanyID', $companyId));
        }

        // Wrap party node and return
        $wrapper = $doc->createElement($isSeller ? 'cac:AccountingSupplierParty' : 'cac:AccountingCustomerParty');
        $wrapper->appendChild($xml);
        return $wrapper;
    }


    /**
     * Get payee node
     * @param  Party        $party Party instance
     * @param  \DOMDocument $doc   DOM Document
     * @return \DOMElement         DOM Element
     * @throws ExportException if failed to generate party node
     */
    private function getPayeeNode(Party $party, \DOMDocument $doc): \DOMElement {
        $xml = $doc->createElement('cac:PayeeParty');

        // Party name
        $name = $party->getName();
        if ($name === null) {
            throw new ExportException('The Payee name (BT-59) shall be provided in the Invoice', 'BR-17');
        }
        $partyNameNode = $doc->createElement('cac:PartyName');
        $partyNameNode->appendChild($doc->createElement('cbc:Name', $name));
        $xml->appendChild($partyNameNode);

        // Company ID
        $companyId = $party->getCompanyId();
        if ($companyId !== null) {
            $legalEntityNode = $doc->createElement('cac:PartyLegalEntity');
            $legalEntityNode->appendChild($doc->createElement('cbc:CompanyID', $companyId));
            $xml->appendChild($legalEntityNode);
        }

        return $xml;
    }


    /**
     * Get line node
     * @param  InvoiceLine  $line    Invoice line instance
     * @param  int          $index   Invoice line index
     * @param  Invoice      $invoice Invoice instance
     * @param  \DOMDocument $doc     DOM Document
     * @return \DOMElement           DOM Element
     * @throws ExportException if failed to generate party node
     */
    private function getLineNode(InvoiceLine $line, int $index, Invoice $invoice, \DOMDocument $doc): \DOMElement {
        $xml = $doc->createElement('cac:InvoiceLine');

        // BT-126: Line ID
        $xml->appendChild($doc->createElement('cbc:ID', (string) $index));

        // BT-129: Invoiced quantity
        $quantityNode = $doc->createElement('cbc:InvoicedQuantity', (string) $line->getQuantity());
        $quantityNode->setAttribute('unitCode', $line->getUnit());
        $xml->appendChild($quantityNode);

        // BT-131: Line net amount
        $netAmount = $line->getNetAmount($invoice->getDecimals('line/netAmount'));
        if ($netAmount === null) {
            throw new ExportException('Each Invoice line shall have an Invoice line net amount (BT-131)', 'BR-24');
        }
        $xml->appendChild($this->getAmountNode('cbc:LineExtensionAmount', $netAmount, $invoice->getCurrency(), $doc));

        // Allowances and charges
        foreach (array_merge($line->getAllowances(), $line->getCharges()) as $item) {
            $xml->appendChild($this->getAllowanceChargeNode($item, $invoice, $line, $doc));
        }

        // Initial item node
        $itemNode = $doc->createElement('cac:Item');
        $xml->appendChild($itemNode);

        // BT-154: Item description
        $description = $line->getDescription();
        if ($description !== null) {
            $itemNode->appendChild($doc->createElement('cbc:Description', $description));
        }

        // BT-153: Item name
        $name = $line->getName();
        if ($name === null) {
            throw new ExportException('Each Invoice line shall contain the Item name (BT-153)', 'BR-25');
        }
        $itemNode->appendChild($doc->createElement('cbc:Name', $name));

        // VAT node
        $vatNode = $this->getVatNode('cac:ClassifiedTaxCategory', $line->getVatCategory(), $line->getVatRate(), $doc);
        $itemNode->appendChild($vatNode);

        // Initial price node
        $priceNode = $doc->createElement('cac:Price');
        $xml->appendChild($priceNode);

        // Price amount
        // @phan-suppress-next-line PhanTypeMismatchArgumentNullable
        $priceAmountNode = $this->getAmountNode('cbc:PriceAmount', $line->getPrice(), $invoice->getCurrency(), $doc);
        $priceNode->appendChild($priceAmountNode);

        // Base quantity
        $baseQuantity = $line->getBaseQuantity();
        if ($baseQuantity > 1) {
            $baseQuantityNode = $doc->createElement('cbc:BaseQuantity', (string) $baseQuantity);
            $baseQuantityNode->setAttribute('unitCode', $line->getUnit());
            $priceNode->appendChild($baseQuantityNode);
        }

        return $xml;
    }


    /**
     * Get allowance/charge node
     * @param  AllowanceChargeBase $item    Allowance/charge instance
     * @param  Invoice             $invoice Invoice instance
     * @param  InvoiceLine|null    $line    Invoice line instance
     * @param  \DOMDocument        $doc     DOM Document
     * @return \DOMElement                  DOM Element
     * @throws ExportException if failed to generate node
     */
    private function getAllowanceChargeNode(
        AllowanceChargeBase $item,
        Invoice $invoice,
        ?InvoiceLine $line,
        \DOMDocument $doc
    ): \DOMElement {
        $atDocumentLevel = ($line === null);
        $xml = $doc->createElement('cac:AllowanceCharge');

        // Add charge indicator node
        $chargeIndicator = ($item instanceof Charge) ? "true" : "false";
        $xml->appendChild($doc->createElement('cbc:ChargeIndicator', $chargeIndicator));

        // Validate reason code and text
        $reasonCode = $item->getReasonCode();
        $reasonText = $item->getReason();
        if ($reasonCode === null && $reasonText === null) {
            throw new ExportException(
                $atDocumentLevel ?
                    'Each Document level allowance/charge shall have a reason (BT-97) or a reason code (BT-98)' :
                    'Each Invoice line allowance/charge shall have a reason (BT-139) or a reason code (BT-140)',
                $atDocumentLevel ? 'BR-33' : 'BR-42'
            );
        }

        // Reason code
        if ($reasonCode !== null) {
            $xml->appendChild($doc->createElement('cbc:AllowanceChargeReasonCode', $reasonCode));
        }

        // Reason text
        if ($reasonText !== null) {
            $xml->appendChild($doc->createElement('cbc:AllowanceChargeReason', $reasonText));
        }

        // Percentage
        if ($item->isPercentage()) {
            $xml->appendChild($doc->createElement('cbc:MultiplierFactorNumeric', (string) $item->getAmount()));
        }

        // Amount
        $baseAmount = $atDocumentLevel ?
            $invoice->getTotals()->netAmount :
            $line->getNetAmount($invoice->getDecimals('line/netAmount')) ?? 0;
        $amount = $item->getEffectiveAmount($baseAmount, $invoice->getDecimals('line/allowanceChargeAmount'));
        $xml->appendChild($this->getAmountNode('cbc:Amount', $amount, $invoice->getCurrency(), $doc));

        // Base amount
        if ($item->isPercentage()) {
            $xml->appendChild($this->getAmountNode('cbc:BaseAmount', $baseAmount, $invoice->getCurrency(), $doc));
        }

        // Tax category
        if ($atDocumentLevel) {
            $xml->appendChild($this->getVatNode('cac:TaxCategory', $item->getVatCategory(), $item->getVatRate(), $doc));
        }

        return $xml;
    }


    /**
     * Get tax total node
     * @param  InvoiceTotals $totals Invoice totals
     * @param  \DOMDocument  $doc    DOM Document
     * @return \DOMElement           DOM Element
     */
    private function getTaxTotalNode(InvoiceTotals $totals, \DOMDocument $doc): \DOMElement {
        $xml = $doc->createElement('cac:TaxTotal');

        // Add tax amount
        $taxAmountNode = $this->getAmountNode('cbc:TaxAmount', $totals->vatAmount, $totals->currency, $doc);
        $xml->appendChild($taxAmountNode);

        // Add each tax details
        foreach ($totals->vatBreakdown as $item) {
            $vatBreakdownNode = $doc->createElement('cac:TaxSubtotal');
            $xml->appendChild($vatBreakdownNode);

            // Taxable subamount
            $taxableAmountNode = $this->getAmountNode('cbc:TaxableAmount', $item->taxableAmount, $totals->currency, $doc);
            $vatBreakdownNode->appendChild($taxableAmountNode);

            // Tax subamount
            $taxSubamountNode = $this->getAmountNode('cbc:TaxAmount', $item->taxAmount, $totals->currency, $doc);
            $vatBreakdownNode->appendChild($taxSubamountNode);

            // Tax category node
            $categoryNode = $this->getVatNode('cac:TaxCategory', $item->category, $item->rate, $doc);
            $vatBreakdownNode->appendChild($categoryNode);
        }

        return $xml;
    }


    /**
     * Get document totals node
     * @param  InvoiceTotals $totals Invoice totals
     * @param  \DOMDocument  $doc    DOM Document
     * @return \DOMElement           DOM Element
     */
    private function getDocumentTotalsNode(InvoiceTotals $totals, \DOMDocument $doc): \DOMElement {
        $xml = $doc->createElement('cac:LegalMonetaryTotal');
        
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
            $amountNode = $this->getAmountNode($field, $amount, $totals->currency, $doc);
            $xml->appendChild($amountNode);
        }

        return $xml;
    }


    /**
     * Get VAT node
     * @param  string       $name     Node name
     * @param  string       $category VAT category
     * @param  int|null     $rate     VAT rate
     * @param  \DOMDocument $doc      DOM Document
     * @return \DOMElement            DOM Element
     */
    private function getVatNode(string $name, string $category, ?int $rate, \DOMDocument $doc): \DOMElement {
        $xml = $doc->createElement($name);

        // VAT category
        $xml->appendChild($doc->createElement('cbc:ID', $category));

        // VAT rate
        if ($rate !== null) {
            $xml->appendChild($doc->createElement('cbc:Percent', (string) $rate));
        }

        // Tax scheme
        $taxSchemeNode = $doc->createElement('cac:TaxScheme');
        $taxSchemeNode->appendChild($doc->createElement('cbc:ID', 'VAT'));
        $xml->appendChild($taxSchemeNode);

        return $xml;
    }


    /**
     * Get amount node
     * @param  string       $name     Node name
     * @param  float        $amount   Amount
     * @param  string       $currency Currency code
     * @param  \DOMDocument $doc      DOM Document
     * @return \DOMElement            DOM Element
     */
    private function getAmountNode(string $name, float $amount, string $currency, \DOMDocument $doc): \DOMElement {
        $xml = $doc->createElement($name, (string) $amount);
        $xml->setAttribute('currencyID', $currency);
        return $xml;
    }
}
