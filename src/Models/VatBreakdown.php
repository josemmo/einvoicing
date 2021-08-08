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
     * VAT exemption reason code
     * @var string|null
     */
    public $exemptionReasonCode = null;

    /**
     * VAT exemption reason as text
     * @var string|null
     */
    public $exemptionReason = null;

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
