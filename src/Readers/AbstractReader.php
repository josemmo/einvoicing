<?php
namespace Einvoicing\Readers;

use Einvoicing\Invoice;
use Einvoicing\Presets\AbstractPreset;
use InvalidArgumentException;
use function is_subclass_of;
use function scandir;
use function str_replace;

abstract class AbstractReader {
    private static $defaultPresets = null;
    protected $additionalPresets = [];

    /**
     * Load default presets
     */
    private static function loadDefaultPresets(): void {
        self::$defaultPresets = [];
        foreach (scandir(__DIR__ . '/../Presets') as $filename) {
            $classname = "\\Einvoicing\\Presets\\" . str_replace('.php', '', $filename);

            // Is this a valid preset?
            if (!is_subclass_of($classname, AbstractPreset::class)) {
                continue;
            }

            /** @var AbstractPreset */
            $preset = new $classname();
            self::$defaultPresets[$preset->getSpecification()] = $classname;
        }
    }


    /**
     * Get preset classname from specification
     * @param  string      $specification Specification identifier
     * @return string|null                Preset classname or NULL if not found
     */
    protected function getPresetFromSpecification(string $specification): ?string {
        if (self::$defaultPresets === null) {
            self::loadDefaultPresets();
        }
        return $this->additionalPresets[$specification] ?? self::$defaultPresets[$specification] ?? null;
    }


    /**
     * Register additional preset
     * @param  string $classname Preset classname
     * @return self              This instance
     * @throws InvalidArgumentException if not a valid preset classname
     */
    public function registerPreset(string $classname): self {
        if (!is_subclass_of($classname, AbstractPreset::class)) {
            throw new InvalidArgumentException("Cannot register $classname as an invoice preset");
        }
        /** @var AbstractPreset */
        $preset = new $classname();
        $this->additionalPresets[$preset->getSpecification()] = $classname;
        return $this;
    }


    /**
     * Import invoice
     * @param  string  $document Document contents
     * @return Invoice           Invoice instance
     */
    abstract public function import(string $document): Invoice;
}
