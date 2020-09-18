<?php
namespace Einvoicing\Models;

use Einvoicing\Invoice;
use function array_values;
use function round;

class InvoiceTotals {
    /**
     * Totals currency code
     * @var string
     */
    public $currency;

    /** 
     * Sum of all invoice line net amounts
     * @var float
     */
    public $netAmount = 0;

    /** 
     * Sum of all allowances on document level
     * @var float
     */
    public $allowancesAmount = 0;

    /** 
     * Sum of all charges on document level
     * @var float
     */
    public $chargesAmount = 0;

    /** 
     * Total VAT amount for the invoice
     * @var float
     */
    public $vatAmount = 0;

    /** 
     * Invoice total amount without VAT
     * @var float
     */
    public $taxExclusiveAmount = 0;

    /** 
     * Invoice total amount with VAT
     * @var float
     */
    public $taxInclusiveAmount = 0;
    
    /** 
     * The sum of amounts which have been paid in advance
     * @var float
     */
    public $paidAmount = 0;

    /** 
     * The amount to be added to the invoice total to round the amount to be paid
     * @var float
     */
    public $roundingAmount = 0;

    /** 
     * Amount due for payment
     * @var float
     */
    public $payableAmount = 0;

    /**
     * Group of business terms providing information about VAT breakdown
     * @var VatBreakdown[]
     */
    public $vatBreakdown = [];

    /**
     * Create instance from invoice
     * @param  Invoice $inv Invoice instance
     * @return self         Totals instance
     */
    static public function fromInvoice(Invoice $inv): InvoiceTotals {
        $totals = new self();
        $vatMap = [];

        // Set currency code
        $totals->currency = $inv->getCurrency();

        // Process all invoice lines
        foreach ($inv->getLines() as $line) {
            $lineNetAmount = $line->getNetAmount($inv->getDecimals('line/netAmount')) ?? 0;
            $totals->netAmount += $lineNetAmount;
            self::updateVatMap($vatMap, $line->getVatCategory(), $line->getVatRate(), $lineNetAmount);
        }

        // Apply allowance and charge totals
        $allowancesChargesDecimals = $inv->getDecimals('invoice/allowancesChargesAmount');
        foreach ($inv->getAllowances() as $item) {
            $allowanceAmount = $item->getEffectiveAmount($totals->netAmount, $allowancesChargesDecimals);
            $totals->allowancesAmount += $allowanceAmount;
            self::updateVatMap($vatMap, $item->getVatCategory(), $item->getVatRate(), -$allowanceAmount);
        }
        foreach ($inv->getCharges() as $item) {
            $chargeAmount = $item->getEffectiveAmount($totals->netAmount, $allowancesChargesDecimals);
            $totals->chargesAmount += $chargeAmount;
            self::updateVatMap($vatMap, $item->getVatCategory(), $item->getVatRate(), $chargeAmount);
        }

        // Calculate VAT amounts
        foreach ($vatMap as $item) {
            $item->taxAmount = $item->taxableAmount * ($item->rate / 100);
            $item->taxAmount = round($item->taxAmount, $inv->getDecimals('invoice/taxAmount'));
            $totals->vatAmount += $item->taxAmount;
        }

        // Calculate rest of properties
        $totals->taxExclusiveAmount = $totals->netAmount - $totals->allowancesAmount + $totals->chargesAmount;
        $totals->taxInclusiveAmount = $totals->taxExclusiveAmount + $totals->vatAmount;
        $totals->paidAmount = $inv->getPaidAmount();
        $totals->roundingAmount = $inv->getRoundingAmount();
        $totals->payableAmount = $totals->taxInclusiveAmount - $totals->paidAmount + $totals->roundingAmount;

        // Attach VAT breakdown
        $totals->vatBreakdown = array_values($vatMap);

        return $totals;
    }


    /**
     * Update VAT map
     * @param array    &$vatMap          VAT map reference
     * @param string   $category         VAT category
     * @param int|null $rate             VAT rate
     * @param float    $addTaxableAmount Taxable amount to add
     */
    static private function updateVatMap(array &$vatMap, string $category, ?int $rate, float $addTaxableAmount) {
        $key = "$category:$rate";
        if (!isset($vatMap[$key])) {
            $vatMap[$key] = new VatBreakdown();
            $vatMap[$key]->category = $category;
            $vatMap[$key]->rate = $rate;
        }
        $vatMap[$key]->taxableAmount += $addTaxableAmount;
    }
}
