<?php
namespace Einvoicing\Presets;

use UXML\UXML;

/**
 * NLCIUS
 * @author Standaardisatieplatform e-factureren
 * @link https://stpe.nl/media/E-Factureren%20-%20Gebruiksinstructie%20voor%20de%20basisfactuur%20v1.0.3.pdf
 */
class Nlcius extends AbstractPreset {
    /**
     * @inheritdoc
     */
    public function getSpecification(): string {
        return "urn:cen.eu:en16931:2017#compliant#urn:fdc:nen.nl:nlcius:v1.0";
    }

    /**
     * @inheritdoc
     */
    public function finalizeUbl(UXML $xml): UXML {
        foreach ($xml->getAll('//cac:TaxScheme/cbc:ID') as $taxIdNode) {
            $taxIdNode->element()->setAttribute('schemeID', 'UN/ECE 5153');
        }
        foreach ($xml->getAll('//cbc:TaxExemptionReasonCode') as $exemptionReasonNode) {
            $exemptionReasonNode->element()->setAttribute('listID', 'CWA 15577');
        }
        return $xml;
    }
}
