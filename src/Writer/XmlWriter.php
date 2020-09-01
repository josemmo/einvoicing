<?php
namespace Einvoicing\Writer;

abstract class XmlWriter extends AbstractWriter {
    /**
     * Get XMLDSig signature node
     * @param  \DOMDocument $doc DOM Document
     * @return \DOMElement       XMLDSig signature element
     * @throws ExportException if failed to generate node
     */
    protected function getSignatureNode(\DOMDocument $doc): \DOMElement {
        $xml = $doc->createElementNS('http://www.w3.org/2000/09/xmldsig#', 'ds:Signature');
        $xml->setAttribute('Id', 'invoiceSignature');

        // Initial signed info node
        $signedInfoNode = $doc->createElement('ds:SignedInfo');
        $xml->appendChild($signedInfoNode);

        // Canonizalization method node
        $c14nNode = $doc->createElement('ds:CanonicalizationMethod');
        $c14nNode->setAttribute('Algorithm', 'http://www.w3.org/TR/2001/REC-xml-c14n-20010315');
        $signedInfoNode->appendChild($c14nNode);

        // Signature method node
        $sigMethodNode = $doc->createElement('ds:SignatureMethod');
        $sigMethodNode->setAttribute('Algorithm', 'http://www.w3.org/2000/09/xmldsig#rsa-' . $this->digestAlgorithm);
        $signedInfoNode->appendChild($sigMethodNode);

        // Rest of nodes
        $xml->appendChild($this->getKeyInfoNode($doc));
        $xml->appendChild($this->getObjectNode($doc));

        // And finally, add the signed info signature
        // $signature = null;
        // openssl_sign($doc->documentElement->C14N(), $signature, $this->privateKey);
        // $signatureNode = $doc->createElement('ds:SignatureValue', $this->toBase64($signature));
        // $xml->appendChild($signatureNode);

        return $xml;
    }


    /**
     * Get KeyInfo node
     * @param  \DOMDocument $doc DOM Document
     * @return \DOMElement       Key info node
     * @throws ExportException if failed to generate node
     */
    private function getKeyInfoNode(\DOMDocument $doc): \DOMElement {
        $xml = $doc->createElement('ds:KeyInfo');

        // Key value
        $keyValueNode = $doc->createElement('ds:KeyValue');
        $xml->appendChild($keyValueNode);

        // Key value contents
        $keyData = openssl_pkey_get_details($this->privateKey);
        if ($keyData['type'] === OPENSSL_KEYTYPE_RSA) {
            $rsaNode = $doc->createElement('ds:RSAKeyValue');
            $keyValueNode->appendChild($rsaNode);
            $rsaNode->appendChild($doc->createElement('ds:Modulus', $this->toBase64($keyData['rsa']['n'])));
            $rsaNode->appendChild($doc->createElement('ds:Exponent', $this->toBase64($keyData['rsa']['e'])));
        } elseif ($keyData['type'] === OPENSSL_KEYTYPE_DSA) {
            $dsaNode = $doc->createElement('ds:DSAKeyValue');
            $keyValueNode->appendChild($dsaNode);
            $dsaNode->appendChild($doc->createElement('ds:P', $this->toBase64($keyData['dsa']['p'])));
            $dsaNode->appendChild($doc->createElement('ds:Q', $this->toBase64($keyData['dsa']['q'])));
            $dsaNode->appendChild($doc->createElement('ds:G', $this->toBase64($keyData['dsa']['g'])));
            $dsaNode->appendChild($doc->createElement('ds:Y', $this->toBase64($keyData['dsa']['pub_key'])));
        } elseif ($keyData['type'] === OPENSSL_KEYTYPE_EC) {
            $ecNode = $doc->createElementNS('http://www.w3.org/2009/xmldsig11#', 'ds11:RSAKeyValue');
            $keyValueNode->appendChild($ecNode);
            $namedCurveNode = $doc->createElement('ds11:NamedCurve');
            $namedCurveNode->setAttribute('URI', $keyData['ec']['curve_oid']);
            $ecNode->appendChild($namedCurveNode);
            $ecNode->appendChild($doc->createElement('ds11:PublicKey', $this->normalizePem($keyData['key'])));
        } else {
            throw new ExportException('Unsupported signature key type');
        }

        // Initial X509 data node
        $xDataNode = $doc->createElement('ds:X509Data');
        $xml->appendChild($xDataNode);

        // X509 certificate node
        $certificate = null;
        openssl_x509_export($this->publicKey, $certificate);
        $xCertNode = $doc->createElement('ds:X509Certificate', $this->normalizePem($certificate));
        $xDataNode->appendChild($xCertNode);

        return $xml;
    }


    /**
     * Get Object node
     * @param  \DOMDocument $doc DOM Document
     * @return \DOMElement       Object node
     * @throws ExportException if failed to generate node
     */
    private function getObjectNode(\DOMDocument $doc): \DOMElement {
        $xml = $doc->createElement('ds:Object');

        // TODO

        return $xml;
    }


    /**
     * To Base64
     * @param  string $data Input data
     * @return string       Base64-encoded data
     */
    private function toBase64(string $data): string {
        return $this->addLineFeeds(base64_encode($data));
    }


    /**
     * Normalize PEM data (base64-encoded)
     * @param  string $data PEM data
     * @return string       Normalized PEM data
     */
    private function normalizePem(string $data): string {
        $output = preg_replace('/--(.+)--/', '', $data);
        $output = preg_replace('/\s+/', '', $output);
        return $this->addLineFeeds($output);
    }


    /**
     * Add line feeds according to RFC 4648
     * @param  string $data Input data
     * @param  string $eol  Line separator character
     * @return string       Output with added lines feeds
     */
    private function addLineFeeds(string $data, string $eol="\n"): string {
        return rtrim(chunk_split($data, 76, $eol), $eol);
    }
}
