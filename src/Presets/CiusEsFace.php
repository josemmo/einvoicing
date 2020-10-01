<?php
namespace Einvoicing\Presets;

/**
 * CIUS-ES-FACE
 * @author Spanish Government
 * @link https://administracionelectronica.gob.es/ctt/face/descargas
 */
class CiusEsFace extends AbstractPreset {
    /**
     * @inheritdoc
     */
    public function getSpecification(): string {
        return "urn:cen.eu:en16931:2017#compliant#urn:face.gob.es:CIUS-ES-FACE:1.0.0";
    }
}
