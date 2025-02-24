<?php
namespace Einvoicing\Models;

use Einvoicing\Invoice;
use Einvoicing\Traits\VatTrait;
use function array_values;

class InvoiceTotals {
    /**
     * Invoice currency code
     * @var string
     */
    public $currency;

    /**
     * VAT accounting currency code
     * @var string|null
     */
    public $vatCurrency = null;

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
     * Total VAT amount in accounting currency
     * @var float|null
     */
    public $customVatAmount = null;

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

        // Set currency codes
        $totals->currency = $inv->getCurrency();
        $totals->vatCurrency = $inv->getVatCurrency();

        // Process all invoice lines
        foreach ($inv->getLines() as $line) {
            $lineNetAmount = $inv->round($line->getNetAmount() ?? 0.0, 'line/netAmount');
            $totals->netAmount += $lineNetAmount;
            self::updateVatMap($vatMap, $line, $lineNetAmount);
        }
        $totals->netAmount = $inv->round($totals->netAmount, 'invoice/netAmount');

        // Process allowances
        foreach ($inv->getAllowances() as $item) {
            $allowanceAmount = $inv->round($item->getEffectiveAmount($totals->netAmount), 'line/allowanceChargeAmount');
            $totals->allowancesAmount += $allowanceAmount;
            self::updateVatMap($vatMap, $item, -$allowanceAmount);
        }
        $totals->allowancesAmount = $inv->round($totals->allowancesAmount, 'invoice/allowancesChargesAmount');

        // Process charges
        foreach ($inv->getCharges() as $item) {
            $chargeAmount = $inv->round($item->getEffectiveAmount($totals->netAmount), 'line/allowanceChargeAmount');
            $totals->chargesAmount += $chargeAmount;
            self::updateVatMap($vatMap, $item, $chargeAmount);
        }
        $totals->chargesAmount = $inv->round($totals->chargesAmount, 'invoice/allowancesChargesAmount');

        // Calculate VAT amounts
        foreach ($vatMap as $item) {
            $item->taxableAmount = $inv->round($item->taxableAmount, 'invoice/allowancesChargesAmount');
            $item->taxAmount = $inv->round($item->taxableAmount * ($item->rate / 100), 'invoice/vatAmount');
            $totals->vatAmount += $item->taxAmount;
        }
        $totals->vatAmount = $inv->round($totals->vatAmount, 'invoice/vatAmount');

        // Add custom VAT amount
        $totals->customVatAmount = $inv->getCustomVatAmount();
        if ($totals->customVatAmount !== null) {
            $totals->customVatAmount = $inv->round($inv->getCustomVatAmount(), 'invoice/vatAmount');
        }

        // Attach VAT breakdown
        $totals->vatBreakdown = array_values($vatMap);

        // Calculate rest of properties
        $totals->taxExclusiveAmount = $inv->round(
            $totals->netAmount - $totals->allowancesAmount + $totals->chargesAmount,
            'invoice/taxExclusiveAmount'
        );
        $totals->taxInclusiveAmount = $inv->round(
            $totals->taxExclusiveAmount + $totals->vatAmount,
            'invoice/taxInclusiveAmount'
        );
        $totals->paidAmount = $inv->round($inv->getPaidAmount(), 'invoice/paidAmount');
        $totals->roundingAmount = $inv->round($inv->getRoundingAmount(), 'invoice/roundingAmount');
        $totals->payableAmount = $inv->round(
            $totals->taxInclusiveAmount - $totals->paidAmount + $totals->roundingAmount,
            'invoice/payableAmount'
        );

        return $totals;
    }


    /**
     * Update VAT map
     * @param array<string,VatBreakdown> &$vatMap          VAT map reference
     * @param VatTrait                   $item             Item instance
     * @param float|null                 $rate             VAT rate
     * @param float                      $addTaxableAmount Taxable amount to add
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
