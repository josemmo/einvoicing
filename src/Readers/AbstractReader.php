<?php
namespace Einvoicing\Readers;

use Einvoicing\Invoice;

abstract class AbstractReader {
    /**
     * Import invoice
     * @param  string  $document Document contents
     * @return Invoice           Invoice instance
     */
    abstract public function import(string $document): Invoice;
}
