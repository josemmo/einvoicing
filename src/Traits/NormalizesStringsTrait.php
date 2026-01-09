<?php
namespace Einvoicing\Traits;

trait NormalizesStringsTrait {
    /**
     * Normalize a string value
     * Trims whitespace and converts empty strings to null.
     * This ensures Peppol validation compliance as normalize-space() is used in Schematron rules.
     * @param  string|null $value Input value
     * @return string|null        Normalized value or null if empty
     */
    private function normalizeString(?string $value): ?string {
        if ($value === null) {
            return null;
        }
        $trimmed = trim($value);
        return $trimmed === '' ? null : $trimmed;
    }
}
