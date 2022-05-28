<?php
namespace Einvoicing\Presets;

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
        return "urn:cen.eu:en16931:2017#compliant#urn:efactura.mfinante.ro:CIUS-RO:1.0.0";
    }
}
