<?php
namespace Einvoicing\Presets;

use Einvoicing\Invoice;

// @phan-file-suppress PhanPluginInconsistentReturnFunction

/**
 * CIUS-RO e-Factura
 * @author Ministerul Finanțelor
 * @link https://mfinante.gov.ro/web/efactura/informatii-tehnice
 */
class CiusRo extends AbstractPreset {
    /**
     * @inheritdoc
     */
    public function getSpecification(): string {
        return "urn:cen.eu:en16931:2017#compliant#urn:efactura.mfinante.ro:CIUS-RO:1.0.1";
    }


    /**
     * @inheritdoc
     */
    public function getRules(): array {
        $res = [];

        $res['BR-RO-A020'] = static function(Invoice $inv) {
            if (count($inv->getNotes()) > 20) {
                return "The allowed maximum number of occurrences of Invoice note (BG-1) is 20.";
            }
        };

        return $res;
    }
}
