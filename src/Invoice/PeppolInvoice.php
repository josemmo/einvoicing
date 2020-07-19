<?php
namespace Einvoicing\Invoice;

class PeppolInvoice extends Invoice {
    /**
     * @inheritdoc
     */
    public function getSpecificationIdentifier(): string {
        return "urn:cen.eu:en16931:2017#compliant#urn:fdc:peppol.eu:2017:poacc:billing:3.0";
    }
}
