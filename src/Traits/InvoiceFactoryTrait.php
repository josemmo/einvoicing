<?php
namespace Einvoicing\Traits;

use Einvoicing\Invoice;
use Einvoicing\Presets\AbstractPreset;
use InvalidArgumentException;
use function is_subclass_of;

trait InvoiceFactoryTrait {
    /**
     * Create invoice from preset
     * @param  string  $classname Preset classname
     * @return Invoice            Invoice instance
     * @throws InvalidArgumentException if not a valid preset classname
     */
    static public function fromPreset(string $classname): Invoice {
        if (!is_subclass_of($classname, AbstractPreset::class)) {
            throw new InvalidArgumentException('The provided classname is not a valid invoice preset');
        }
        /** @var AbstractPreset */
        $preset = new $classname();
        $invoice = new Invoice();
        $invoice->setSpecification($preset->getSpecification());
        $preset->setupInvoice($invoice);
        return $invoice;
    }
}
