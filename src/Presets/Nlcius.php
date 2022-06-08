<?php
namespace Einvoicing\Presets;

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
}
