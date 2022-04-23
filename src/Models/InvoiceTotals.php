<?php
namespace Einvoicing\Models;

use Einvoicing\Invoice;
use Einvoicing\Traits\VatTrait;
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
     * @param  Invoice $inv   Invoice instance
     * @param  boolean $round Whether to round values or not
     * @return self           Totals instance
     */
    static public function fromInvoice(Invoice $inv, bool $round=true): InvoiceTotals {
        $totals = new self();
        $vatMap = [];

        // Set currency code
        $totals->currency = $inv->getCurrency();

        // Process all invoice lines
        foreach ($inv->getLines() as $line) {
            $lineNetAmount = $line->getNetAmount() ?? 0.0;
            $totals->netAmount += $lineNetAmount;
            self::updateVatMap($vatMap, $line, $lineNetAmount);
        }

        // Apply allowance and charge totals
        foreach ($inv->getAllowances() as $item) {
            $allowanceAmount = $item->getEffectiveAmount($totals->netAmount);
            $totals->allowancesAmount += $allowanceAmount;
            self::updateVatMap($vatMap, $item, -$allowanceAmount);
        }
        foreach ($inv->getCharges() as $item) {
            $chargeAmount = $item->getEffectiveAmount($totals->netAmount);
            $totals->chargesAmount += $chargeAmount;
            self::updateVatMap($vatMap, $item, $chargeAmount);
        }

        // Calculate VAT amounts
        foreach ($vatMap as $item) {
            $item->taxAmount = $item->taxableAmount * ($item->rate / 100);
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

        // Round values
        if ($round) {
            $totals->netAmount = round($totals->netAmount, $inv->getDecimals('invoice/netAmount'));
            $totals->allowancesAmount = round($totals->allowancesAmount, $inv->getDecimals('invoice/allowancesChargesAmount'));
            $totals->chargesAmount = round($totals->chargesAmount, $inv->getDecimals('invoice/allowancesChargesAmount'));
            $totals->vatAmount = round($totals->vatAmount, $inv->getDecimals('invoice/vatAmount'));
            $totals->taxExclusiveAmount = round($totals->taxExclusiveAmount, $inv->getDecimals('invoice/taxExclusiveAmount'));
            $totals->taxInclusiveAmount = round($totals->taxInclusiveAmount, $inv->getDecimals('invoice/taxInclusiveAmount'));
            $totals->paidAmount = round($totals->paidAmount, $inv->getDecimals('invoice/paidAmount'));
            $totals->roundingAmount = round($totals->roundingAmount, $inv->getDecimals('invoice/roundingAmount'));
            $totals->payableAmount = round($totals->payableAmount, $inv->getDecimals('invoice/payableAmount'));
            foreach ($totals->vatBreakdown as $item) {
                $item->taxableAmount = round($item->taxableAmount, $inv->getDecimals('invoice/allowancesChargesAmount'));
                $item->taxAmount = round($item->taxAmount, $inv->getDecimals('invoice/taxAmount'));
            }
        }

        return $totals;
    }


    /**
     * Update VAT map
     * @param VatBreakdown[string] &$vatMap          VAT map reference
     * @param VatTrait             $item             Item instance
     * @param float|null           $rate             VAT rate
     * @param float                $addTaxableAmount Taxable amount to add
     */
    static private function updateVatMap(array &$vatMap, $item, float $addTaxableAmount) {
        $category = $item->getVatCategory();
        $rate = $item->getVatRate();
        $key = "$category:$rate";

        // Initialize VAT breakdown
        if (!isset($vatMap[$key])) {
            $vatMap[$key] = new VatBreakdown();
            $vatMap[$key]->category = $category;
            $vatMap[$key]->rate = $rate;
        }

        // Update exemption reason (last item overwrites previous ones)
        $exemptionReasonCode = $item->getVatExemptionReasonCode();
        $exemptionReason = $item->getVatExemptionReason();
        if ($exemptionReasonCode !== null) {
            $vatMap[$key]->exemptionReasonCode = $exemptionReasonCode;
        }
        if ($exemptionReason !== null) {
            $vatMap[$key]->exemptionReason = $exemptionReason;
        }

        // Increase taxable amount
        $vatMap[$key]->taxableAmount += $addTaxableAmount;
    }
}
