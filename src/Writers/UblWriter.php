<?php
namespace Einvoicing\Writers;

use Einvoicing\AllowanceOrCharge;
use Einvoicing\Exceptions\ExportException;
use Einvoicing\Identifier;
use Einvoicing\Invoice;
use Einvoicing\InvoiceLine;
use Einvoicing\Models\InvoiceTotals;
use Einvoicing\Party;
use Einvoicing\Utils\UxmlElement;

class UblWriter extends AbstractWriter {
    const NS_INVOICE = "urn:oasis:names:specification:ubl:schema:xsd:Invoice-2";
    const NS_CAC = "urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2";
    const NS_CBC = "urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2";

    /**
     * @inheritdoc
     * @throws ExportException if failed to export invoice
     */
    public function export(Invoice $invoice): string {
        $xml = new UxmlElement('Invoice', null, [
            'xmlns' => self::NS_INVOICE,
            'xmlns:cac' => self::NS_CAC,
            'xmlns:cbc' => self::NS_CBC
        ]);

        // BT-24: Specification indentifier
        $specificationIdentifier = $invoice->getSpecification();
        if ($specificationIdentifier === null) {
            throw new ExportException('An Invoice shall have a Specification identifier (BT-24)', 'BR-1');
        }
        $xml->add('cbc:CustomizationID', $specificationIdentifier);

        // BT-23: Business process type
        $businessProcessType = $invoice->getBusinessProcess();
        if ($businessProcessType !== null) {
            $xml->add('cbc:ProfileID', $businessProcessType);
        }

        // BT-1: Invoice number
        $number = $invoice->getNumber();
        if ($number === null) {
            throw new ExportException('An Invoice shall have an Invoice number (BT-1)', 'BR-2');
        }
        $xml->add('cbc:ID', $number);

        // BT-2: Issue date
        $issueDate = $invoice->getIssueDate();
        if ($issueDate === null) {
            throw new ExportException('An Invoice shall have an Invoice issue date (BT-2)', 'BR-3');
        }
        $xml->add('cbc:IssueDate', $issueDate->format('Y-m-d'));

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

        // BT-5: Invoice currency code
        $xml->add('cbc:DocumentCurrencyCode', $invoice->getCurrency());

        // Seller node
        $seller = $invoice->getSeller();
        if ($seller === null) {
            throw new ExportException('Missing seller from invoice');
        }
        $this->addSellerOrBuyerNode($xml, $seller, true);

        // Buyer node
        $buyer = $invoice->getBuyer();
        if ($buyer === null) {
            throw new ExportException('Missing buyer from invoice');
        }
        $this->addSellerOrBuyerNode($xml, $buyer, false);

        // Payee node
        $payee = $invoice->getPayee();
        if ($payee !== null) {
            $this->addPayeeNode($xml, $payee);
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
        if (empty($lines)) {
            throw new ExportException('An Invoice shall have at least one Invoice line (BG-25)', 'BR-16');
        }
        foreach ($lines as $i=>$line) {
            $this->addLineNode($xml, $line, $i+1, $invoice);
        }

        return $xml->asXML();
    }


    /**
     * Add identifier node
     * @param UxmlElement $parent     Parent element
     * @param string      $name       New node name
     * @param Identifier  $identifier Identifier instance
     */
    private function addIdentifierNode(UxmlElement $parent, string $name, Identifier $identifier) {
        $scheme = $identifier->getScheme();
        $attrs = ($scheme === null) ? [] : ['schemeID' => $scheme];
        $parent->add($name, $identifier->getValue(), $attrs);
    }


    /**
     * Add amount node
     * @param UxmlElement $parent   Parent element
     * @param string      $name     New node name
     * @param float       $amount   Amount
     * @param string      $currency Currency code
     */
    private function addAmountNode(UxmlElement $parent, string $name, float $amount, string $currency) {
        $parent->add($name, (string) $amount, ['currencyID' => $currency]);
    }


    /**
     * Add VAT node
     * @param UxmlElement $parent   Parent element
     * @param string      $name     New node name
     * @param string      $category VAT category
     * @param int|null    $rate     VAT rate
     */
    private function addVatNode(UxmlElement $parent, string $name, string $category, ?int $rate) {
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
     * Add seller or buyer node
     * @param  UxmlElement  $parent   Invoice element
     * @param  Party        $party    Party instance
     * @param  boolean      $isSeller Is seller
     * @throws ExportException if failed to generate party node
     */
    private function addSellerOrBuyerNode(UxmlElement $parent, Party $party, bool $isSeller) {
        $xml = $parent->add($isSeller ? 'cac:AccountingSupplierParty' : 'cac:AccountingCustomerParty')->add('cac:Party');

        // Trading name
        $tradingName = $party->getTradingName();
        if ($tradingName !== null) {
            $xml->add('cac:PartyName')->add('cbc:Name', $tradingName);
        }

        // Initial postal address node
        $addressNode = $xml->add('cac:PostalAddress');

        // Street name
        $addressLines = $party->getAddress();
        if (isset($addressLines[0])) {
            $addressNode->add('cbc:StreetName', $addressLines[0]);
        }

        // Additional street name
        if (isset($addressLines[1])) {
            $addressNode->add('cbc:AdditionalStreetName', $addressLines[1]);
        }

        // City name
        $cityName = $party->getCity();
        if ($cityName !== null) {
            $addressNode->add('cbc:CityName', $cityName);
        }

        // Postal code
        $postalCode = $party->getPostalCode();
        if ($postalCode !== null) {
            $addressNode->add('cbc:PostalZone', $postalCode);
        }

        // Subdivision
        $subdivision = $party->getSubdivision();
        if ($subdivision !== null) {
            $addressNode->add('cbc:CountrySubentity', $subdivision);
        }

        // Address line (third address line)
        if (isset($addressLines[2])) {
            $addressNode->add('cac:AddressLine')->add('cbc:Line', $addressLines[2]);
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
        $addressNode->add('cac:Country')->add('cbc:IdentificationCode', $country);

        // VAT number
        $vatNumber = $party->getVatNumber();
        if ($isSeller && $vatNumber === null) {
            throw new ExportException('The Seller VAT identifier (BT-31) shall be present', 'BR-CO-9');
        }
        if ($vatNumber !== null) {
            $taxNode = $xml->add('cac:PartyTaxScheme');
            $taxNode->add('cbc:CompanyID', $vatNumber);
            $taxNode->add('cac:TaxScheme')->add('cbc:ID', 'VAT');
        }

        // Initial legal entity node
        $legalEntityNode = $xml->add('cac:PartyLegalEntity');

        // Legal name
        $legalName = $party->getName();
        if ($legalName === null) {
            throw new ExportException(
                $isSeller ? 'An Invoice shall contain the Seller name (BT-27)' :
                            'An Invoice shall contain the Buyer name (BT-44)',
                $isSeller ? 'BR-6' : 'BR-7'
            );
        }
        $legalEntityNode->add('cbc:RegistrationName', $legalName);

        // Company ID
        $companyId = $party->getCompanyId();
        if ($companyId !== null) {
            $this->addIdentifierNode($legalEntityNode, 'cbc:CompanyID', $companyId);
        }
    }


    /**
     * Add payee node
     * @param  UxmlElement $parent Invoice element
     * @param  Party       $party  Party instance
     * @throws ExportException if failed to generate party node
     */
    private function addPayeeNode(UxmlElement $parent, Party $party) {
        $xml = $parent->add('cac:PayeeParty');

        // Party name
        $name = $party->getName();
        if ($name === null) {
            throw new ExportException('The Payee name (BT-59) shall be provided in the Invoice', 'BR-17');
        }
        $xml->add('cac:PartyName')->add('cbc:Name', $name);

        // Company ID
        $companyId = $party->getCompanyId();
        if ($companyId !== null) {
            $legalEntityNode = $xml->add('cac:PartyLegalEntity');
            $this->addIdentifierNode($legalEntityNode, 'cbc:CompanyID', $companyId);
        }
    }


    /**
     * Add allowance or charge
     * @param  UxmlElement       $parent   Parent element
     * @param  AllowanceOrCharge $item     Allowance or charge instance
     * @param  boolean           $isCharge Is charge (TRUE) or allowance (FALSE)
     * @param  Invoice           $invoice  Invoice instance
     * @param  InvoiceLine|null  $line     Invoice line or NULL in case of at document level
     * @throws ExportException if failed to generate node
     */
    private function addAllowanceOrCharge(
        UxmlElement $parent,
        AllowanceOrCharge $item,
        bool $isCharge,
        Invoice $invoice,
        ?InvoiceLine $line=null
    ) {
        $atDocumentLevel = ($line === null);
        $xml = $parent->add('cac:AllowanceCharge');

        // Charge indicator
        $xml->add('cbc:ChargeIndicator', $isCharge ? 'true' : 'false');

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
            $xml->add('cbc:AllowanceChargeReasonCode', $reasonCode);
        }

        // Reason text
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
     * @param UxmlElement   $parent Parent element
     * @param InvoiceTotals $totals Invoice totals
     */
    private function addTaxTotalNode(UxmlElement $parent, InvoiceTotals $totals) {
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
     * @param UxmlElement   $parent Parent element
     * @param InvoiceTotals $totals Invoice totals
     */
    private function addDocumentTotalsNode(UxmlElement $parent, InvoiceTotals $totals) {
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
     * @param  UxmlElement $parent  Parent XML element
     * @param  InvoiceLine $line    Invoice line
     * @param  int         $index   Invoice line index
     * @param  Invoice     $invoice Invoice instance
     * @throws ExportException if failed to generate node
     */
    private function addLineNode(UxmlElement $parent, InvoiceLine $line, int $index, Invoice $invoice) {
        $xml = $parent->add('cac:InvoiceLine');

        // BT-126: Line ID
        $xml->add('cbc:ID', (string) $index);

        // BT-129: Invoiced quantity
        $xml->add('cbc:InvoicedQuantity', (string) $line->getQuantity(), ['unitCode' => $line->getUnit()]);

        // BT-131: Line net amount
        $netAmount = $line->getNetAmount($invoice->getDecimals('line/netAmount'));
        if ($netAmount === null) {
            throw new ExportException('Each Invoice line shall have an Invoice line net amount (BT-131)', 'BR-24');
        }
        $this->addAmountNode($xml, 'cbc:LineExtensionAmount', $netAmount, $invoice->getCurrency());

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
        if ($name === null) {
            throw new ExportException('Each Invoice line shall contain the Item name (BT-153)', 'BR-25');
        }
        $itemNode->add('cbc:Name', $name);

        // VAT node
        $this->addVatNode($itemNode, 'cac:ClassifiedTaxCategory', $line->getVatCategory(), $line->getVatRate());

        // Initial price node
        $priceNode = $xml->add('cac:Price');

        // Price amount
        // @phan-suppress-next-line PhanTypeMismatchArgumentNullable
        $this->addAmountNode($priceNode, 'cbc:PriceAmount', $line->getPrice(), $invoice->getCurrency());

        // Base quantity
        $baseQuantity = $line->getBaseQuantity();
        if ($baseQuantity > 1) {
            $priceNode->add('cbc:BaseQuantity', (string) $baseQuantity, ['unitCode' => $line->getUnit()]);
        }

        return $xml;
    }
}
