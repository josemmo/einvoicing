<?php
namespace Einvoicing\Models;

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
}
