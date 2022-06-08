<?php
namespace Einvoicing\Presets;

use Einvoicing\Invoice;
use UXML\UXML;

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
        $invoice->setRoundingMatrix(['' => 2]);
    }


    /**
     * Finalize UBL document
     * @param  UXML $xml UBL document
     * @return UXML      Modified UBL document
     */
    public function finalizeUbl(UXML $xml): UXML {
        return $xml;
    }
}
