<?php
namespace Einvoicing\Models;

class VatBreakdown {
    /**
     * VAT category code
     * @var string
     */
    public $category;

    /**
     * VAT rate as a percentage
     * @var float|null
     */
    public $rate;

    /**
     * Sum of all taxable amounts
     * @var float
     */
    public $taxableAmount = 0;

    /**
     * Total VAT amount
     * @var float
     */
    public $taxAmount = 0;
}
