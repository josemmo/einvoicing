<?php
namespace Einvoicing\Presets;

/**
 * CIUS-IT
 * @author Agenzia delle Entrate (AdE)
 * @link https://www.agenziaentrate.gov.it/portale/documents/20143/296874/Specifiche+Tecniche+Fatturazione+Europea+v1.1_ENG.pdf
 */
class CiusIt extends AbstractPreset {
    /**
     * @inheritdoc
     */
    public function getSpecification(): string {
        return "urn:cen.eu:en16931:2017#compliant#urn:fatturapa.gov.it:CIUS-IT:1.0.0";
    }
}
