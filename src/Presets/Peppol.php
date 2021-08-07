<?php
namespace Einvoicing\Presets;

use Einvoicing\Invoice;

// @phan-file-suppress PhanPossiblyNonClassMethodCall

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
    public function getRules(): array {
        $res = [];

        $res['PEPPOL-EN16931-R003'] = static function(Invoice $inv) {
            if ($inv->getBuyerReference() !== null) return;
            if ($inv->getPurchaseOrderReference() !== null) return;
            return "A buyer reference or purchase order reference MUST be provided.";
        };
        $res['PEPPOL-EN16931-R061'] = static function(Invoice $inv) {
            if ($inv->getPayment() === null) return;
            if ($inv->getPayment()->getMandate() === null) return;
            if ($inv->getPayment()->getMandate()->getReference() === null) {
                return "Mandate reference MUST be provided for direct debit";
            }
        };
        $res['BG-17'] = static function(Invoice $inv) {
            if ($inv->getPayment() !== null && count($inv->getPayment()->getTransfers()) > 1) {
                return "An Invoice shall not have multiple credit transfers";
            }
        };

        return $res;
    }


    /**
     * @inheritdoc
     */
    public function setupInvoice(Invoice $invoice) {
        parent::setupInvoice($invoice);
        $invoice->setBusinessProcess('urn:fdc:peppol.eu:2017:poacc:billing:01:1.0');
    }
}
