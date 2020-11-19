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
     * Get additional validation rules
     * @return array<string,callable> Map of rules
     */
    public function getRules(): array {
        return [];
    }


    /**
     * Setup invoice
     * @param Invoice $invoice Invoice instance
     */
    public function setupInvoice(Invoice $invoice) {
        $invoice->setRoundingMatrix([null => 2]);
    }
}
