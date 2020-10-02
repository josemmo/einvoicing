<?php
namespace Einvoicing\Writers;

use Einvoicing\Invoice;

abstract class AbstractWriter {
    /**
     * Export invoice
     * @param  Invoice $invoice Invoice instance
     * @return string           Export contents
     */
    abstract public function export(Invoice $invoice): string;
}
