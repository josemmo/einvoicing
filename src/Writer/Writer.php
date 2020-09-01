<?php
namespace Einvoicing\Writer;

use Einvoicing\Invoice\Invoice;

interface Writer {
    /**
     * Export invoice
     * @param  Invoice $invoice Invoice instance
     * @return string           Generated invoice contents
     * @throws ExportException if failed to export invoice
     */
    public function export(Invoice $invoice): string;
}
