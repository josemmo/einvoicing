<?php
namespace Einvoicing\Presets;

use Einvoicing\Invoice;

abstract class AbstractPreset {
    /**
     * Get specification identifier
     * @return string Specification identifier
     */
    abstract public function getSpecification(): string;


    /**
     * Setup invoice
     * @param Invoice $invoice Invoice instance
     */
    public function setupInvoice(Invoice $invoice) {
        $invoice->setRoundingMatrix([null => 2]);
    }
}
