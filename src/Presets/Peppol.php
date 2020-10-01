<?php
namespace Einvoicing\Presets;

use Einvoicing\Invoice;

/**
 * PEPPOL BIS Billing 3.0
 * @author OpenPEPPOL
 * @link https://docs.peppol.eu/poacc/billing/3.0/
 */
class Peppol extends AbstractPreset {
    /**
     * @inheritdoc
     */
    public function getSpecification(): string {
        return "urn:cen.eu:en16931:2017#compliant#urn:fdc:peppol.eu:2017:poacc:billing:3.0";
    }


    /**
     * @inheritdoc
     */
    public function setupInvoice(Invoice $invoice) {
        parent::setupInvoice($invoice);
        $invoice->setBusinessProcess('urn:fdc:peppol.eu:2017:poacc:billing:01:1.0');
    }
}
