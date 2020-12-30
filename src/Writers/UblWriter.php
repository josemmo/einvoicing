<?php
namespace Einvoicing\Writers;

use Einvoicing\AllowanceOrCharge;
use Einvoicing\Identifier;
use Einvoicing\Invoice;
use Einvoicing\InvoiceLine;
use Einvoicing\Models\InvoiceTotals;
use Einvoicing\Party;
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
     */
    private function addIdentifierNode(UXML $parent, string $name, Identifier $identifier) {
        $scheme = $identifier->getScheme();
        $attrs = ($scheme === null) ? [] : ['schemeID' => $scheme];
        $parent->add($name, $identifier->getValue(), $attrs);
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
        if ($country !== null) {
            $addressNode->add('cac:Country')->add('cbc:IdentificationCode', $country);
        }

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
    }


    /**
     * Add payee node
     * @param UXML  $parent Invoice element
     * @param Party $party  Party instance
     */
    private function addPayeeNode(UXML $parent, Party $party) {
        $xml = $parent->add('cac:PayeeParty');

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

        // BT-129: Invoiced quantity
        $xml->add('cbc:InvoicedQuantity', (string) $line->getQuantity(), ['unitCode' => $line->getUnit()]);

        // BT-131: Line net amount
        $netAmount = $line->getNetAmount($invoice->getDecimals('line/netAmount'));
        if ($netAmount !== null) {
            $this->addAmountNode($xml, 'cbc:LineExtensionAmount', $netAmount, $invoice->getCurrency());
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

        // VAT node
        $this->addVatNode($itemNode, 'cac:ClassifiedTaxCategory', $line->getVatCategory(), $line->getVatRate());

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
