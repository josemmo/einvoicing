<?php
namespace Einvoicing\Presets;

/**
 * CIUS-AT-NAT
 * @author Bundesrechenzentrum (BRZ)
 * @link https://www.erechnung.gv.at/files/en16931/CIUS-AT-NAT-XS-1.0.0.pdf
 */
class CiusAtNat extends AbstractPreset {
    /**
     * @inheritdoc
     */
    public function getSpecification(): string {
        return "urn:cen.eu:en16931:2017#compliant#urn:erechnung.gv.at:CIUS-ATNAT:1.0.0";
    }
}
