<?php
namespace Einvoicing\Traits;

use Einvoicing\Invoice;
use Einvoicing\Presets\AbstractPreset;
use InvalidArgumentException;
use function is_subclass_of;
use function scandir;
use function str_replace;

trait InvoiceHelpersTrait {
    private static $presets = null;

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


    /**
     * Find preset classname
     * @param  string      $specification Specification identifier
     * @return string|null                Preset classname or NULL if not found
     */
    static public function findPreset(string $specification): ?string {
        if (self::$presets === null) {
            self::loadPresets();
        }
        return self::$presets[$specification] ?? null;
    }


    /**
     * Load all presets
     */
    static private function loadPresets(): void {
        self::$presets = [];
        foreach (scandir(__DIR__ . '/../Presets') as $filename) {
            $classname = "\\Einvoicing\\Presets\\" . str_replace('.php', '', $filename);

            // Is this a valid preset?
            if (!is_subclass_of($classname, AbstractPreset::class)) {
                continue;
            }

            /** @var AbstractPreset */
            $preset = new $classname();
            self::$presets[$preset->getSpecification()] = $classname;
        }
    }
}
