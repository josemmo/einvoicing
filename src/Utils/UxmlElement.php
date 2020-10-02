<?php
namespace Einvoicing\Utils;

use DOMDocument;
use function strpos;

/**
 * Uncomplicated XML Element
 */
class UxmlElement {
    private $element;

    /**
     * Class constructor
     * @param string           $name  Element tag name
     * @param string|null      $value Element value or NULL for empty
     * @param array            $attrs Element attributes
     * @param DOMDocument|null $doc   Document instance
     */
    public function __construct(string $name, ?string $value=null, array $attrs=[], DOMDocument $doc=null) {
        $targetDoc = ($doc === null) ? new DOMDocument() : $doc;
        $this->element = $targetDoc->createElement($name, $value);

        // Set attributes
        foreach ($attrs as $attrName=>$attrValue) {
            if ($attrName === "xmlns" || strpos($attrName, 'xmlns:') === 0) {
                $this->element->setAttributeNS('http://www.w3.org/2000/xmlns/', $attrName, $attrValue);
            } else {
                $this->element->setAttribute($attrName, $attrValue);
            }
        }
    }


    /**
     * Add child element
     * @param  string      $name  New element tag name
     * @param  string|null $value New element value or NULL for empty
     * @param  array       $attrs New element attributes
     * @return self               New element instance
     */
    public function add(string $name, ?string $value=null, array $attrs=[]): self {
        $child = new self($name, $value, $attrs, $this->element->ownerDocument);
        $this->element->appendChild($child->element);
        return $child;
    }


    /**
     * Export as XML string
     * @param  string  $version  Document version
     * @param  string  $encoding Document encoding
     * @param  boolean $format   Format output
     * @return string            XML string
     */
    public function asXML(string $version="1.0", string $encoding="UTF-8", bool $format=true): string {
        $doc = new DOMDocument($version, $encoding);
        $doc->formatOutput = $format;
        $doc->appendChild($doc->importNode($this->element, true));
        $res = $doc->saveXML();
        unset($doc);
        return $res;
    }


    /**
     * @inheritdoc
     */
    public function __toString(): string {
        return $this->asXML();
    }
}
