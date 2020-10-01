<?php
namespace Einvoicing\Presets;

/**
 * CIUS-AT-GOV
 * @author Bundesrechenzentrum (BRZ)
 * @link https://www.erechnung.gv.at/files/en16931/CIUS-AT-GOV-XS-1.1.0.pdf
 */
class CiusAtGov extends CiusAtNat {
    /**
     * @inheritdoc
     */
    public function getSpecification(): string {
        return parent::getSpecification() . "#compliant#urn:erechnung.gv.at:CIUS-AT-GOV:1.1.0";
    }
}
