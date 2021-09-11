<?php
namespace Einvoicing\Traits;

use Einvoicing\Exceptions\ValidationException;
use Einvoicing\Invoice;
use function array_merge;
use function in_array;

// @phan-file-suppress PhanPossiblyNonClassMethodCall

trait InvoiceValidationTrait {
    /**
     * Validate invoice
     * @throws ValidationException if failed to pass validation
     */
    public function validate(): void {
        $rules = $this->getRules();
        foreach ($rules as $ruleId=>$rule) {
            $errorMessage = $rule($this);
            if (!empty($errorMessage)) {
                throw new ValidationException($errorMessage, $ruleId);
            }
        }
    }


    /**
     * Get effective validation rules
     * @return array Map of <string,callable> rules
     * @suppress PhanUndeclaredProperty
     */
    private function getRules(): array {
        $rules = $this->getDefaultRules();
        if ($this->preset !== null) {
            $rules = array_merge($rules, $this->preset->getRules());
        }
        return $rules;
    }


    /**
     * Get EN16931 validation rules
     * @return array Map of <string,callable> rules
     */
    private function getDefaultRules(): array {
        $res = [];

        $res['BR-01'] = static function(Invoice $inv) {
            if ($inv->getSpecification() === null) return "An Invoice shall have a Specification identifier (BT-24)";
        };
        $res['BR-02'] = static function(Invoice $inv) {
            if ($inv->getNumber() === null) return "An Invoice shall have an Invoice number (BT-1)";
        };
        $res['BR-03'] = static function(Invoice $inv) {
            if ($inv->getIssueDate() === null) return "An Invoice shall have an Invoice issue date (BT-2)";
        };
        $res['BR-06'] = static function(Invoice $inv) {
            if ($inv->getSeller() === null) return "Missing Seller from Invoice";
            if ($inv->getSeller()->getName() === null) return "An Invoice shall contain the Seller name (BT-27)";
        };
        $res['BR-07'] = static function(Invoice $inv) {
            if ($inv->getBuyer() === null) return "Missing Buyer from Invoice";
            if ($inv->getBuyer()->getName() === null) return "An Invoice shall contain the Buyer name (BT-44)";
        };
        $res['BR-09'] = static function(Invoice $inv) {
            if ($inv->getSeller()->getCountry() === null) {
                return "The Seller postal address shall contain a Seller country code (BT-40)";
            }
        };
        $res['BR-11'] = static function(Invoice $inv) {
            if ($inv->getBuyer()->getCountry() === null) {
                return "The Buyer postal address shall contain a Buyer country code (BT-55)";
            }
        };
        $res['BR-16'] = static function(Invoice $inv) {
            if (empty($inv->getLines())) return "An Invoice shall have at least one Invoice line (BG-25)";
        };
        $res['BR-17'] = static function(Invoice $inv) {
            if ($inv->getPayee() === null) return;
            if ($inv->getSeller()->getName() === $inv->getPayee()->getName()) return;
            if ($inv->getPayee()->getName() === null) {
                return "The Payee name shall be provided in the Invoice, if the Payee is different from the Seller";
            }
        };
        $res['BR-25'] = static function(Invoice $inv) {
            foreach ($inv->getLines() as $line) {
                if ($line->getName() === null) return "Each Invoice line (BG-25) shall contain the Item name (BT-153)";
            }
        };
        $res['BR-26'] = static function(Invoice $inv) {
            foreach ($inv->getLines() as $line) {
                if ($line->getPrice() === null) {
                    return "Each Invoice line (BG-25) shall contain the Item net price (BT-146)";
                }
            }
        };
        $res['BR-27'] = static function(Invoice $inv) {
            foreach ($inv->getLines() as $line) {
                if ($line->getPrice() < 0) return "The Item net price (BT-146) shall NOT be negative";
            }
        };
        $res['BR-31'] = static function(Invoice $inv) {
            foreach ($inv->getAllowances() as $allowance) {
                if ($allowance->getAmount() === null) {
                    return "Each Document level allowance shall have a Document level allowance amount (BT-92)";
                }
            }
        };
        $res['BR-33'] = static function(Invoice $inv) {
            foreach ($inv->getAllowances() as $allowance) {
                if ($allowance->getReasonCode() === null && $allowance->getReason() === null) {
                    return "Each Document level allowance shall have a Document level allowance reason (BT-97) " .
                        "or a Document level allowance reason code (BT-98)";
                }
            }
        };
        $res['BR-36'] = static function(Invoice $inv) {
            foreach ($inv->getCharges() as $charge) {
                if ($charge->getAmount() === null) {
                    return "Each Document level charge shall have a Document level charge amount (BT-99)";
                }
            }
        };
        $res['BR-38'] = static function(Invoice $inv) {
            foreach ($inv->getCharges() as $charge) {
                if ($charge->getReasonCode() === null && $charge->getReason() === null) {
                    return "Each Document level charge shall have a Document level charge reason (BT-104) " .
                        "or a Document level charge reason code (BT-105)";
                }
            }
        };
        $res['BR-41'] = static function(Invoice $inv) {
            foreach ($inv->getLines() as $line) {
                foreach ($line->getAllowances() as $allowance) {
                    if ($allowance->getAmount() === null) {
                        return "Each Invoice line allowance shall have an Invoice line allowance amount (BT-136)";
                    }
                }
            }
        };
        $res['BR-42'] = static function(Invoice $inv) {
            foreach ($inv->getLines() as $line) {
                foreach ($line->getAllowances() as $allowance) {
                    if ($allowance->getReasonCode() === null && $allowance->getReason() === null) {
                        return "Each Invoice line allowance shall have an Invoice line allowance reason (BT-139) " .
                            "or an Invoice line allowance reason code (BT-140)";
                    }
                }
            }
        };
        $res['BR-43'] = static function(Invoice $inv) {
            foreach ($inv->getLines() as $line) {
                foreach ($line->getCharges() as $charge) {
                    if ($charge->getAmount() === null) {
                        return "Each Invoice line charge shall have an Invoice line charge amount (BT-141)";
                    }
                }
            }
        };
        $res['BR-44'] = static function(Invoice $inv) {
            foreach ($inv->getLines() as $line) {
                foreach ($line->getCharges() as $charge) {
                    if ($charge->getReasonCode() === null && $charge->getReason() === null) {
                        return "Each Invoice line charge shall have an Invoice line charge reason " .
                            "or an invoice line allowance reason code";
                    }
                }
            }
        };
        $res['BR-49'] = static function(Invoice $inv) {
            if ($inv->getPayment() !== null && $inv->getPayment()->getMeansCode() === null) {
                return "A Payment instruction (BG-16) shall specify the Payment means type code (BT-81)";
            }
        };
        $res['BR-50'] = static function(Invoice $inv) {
            if ($inv->getPayment() === null) return;
            foreach ($inv->getPayment()->getTransfers() as $transfer) {
                if ($transfer->getAccountId() === null) {
                    return "A Payment account identifier (BT-84) shall be present if Credit transfer (BG-17) " .
                        "information is provided in the Invoice";
                }
            }
        };
        $res['BR-51'] = static function(Invoice $inv) {
            if ($inv->getPayment() === null) return;
            if ($inv->getPayment()->getCard() === null) return;
            if ($inv->getPayment()->getCard()->getPan() === null) {
                return "The last 4 to 6 digits of the Payment card primary account number (BT-87) " .
                    "shall be present if Payment card information (BG-18) is provided in the Invoice";
            }
        };
        $res['BR-52'] = static function(Invoice $inv) {
            foreach ($inv->getAttachments() as $attachment) {
                if ($attachment->getId() === null) {
                    return "Each Additional supporting document shall contain a Supporting document reference (BT-122)";
                }
            }
        };
        $res['BR-61'] = static function(Invoice $inv) {
            if ($inv->getPayment() === null) return;
            if (!in_array($inv->getPayment()->getMeansCode(), ['30', '58'])) return;
            if (empty($inv->getPayment()->getTransfers())) {
                return "If the Payment means type code (BT-81) means SEPA credit transfer, Local credit transfer or " .
                    "Non-SEPA international credit transfer, the Payment account identifier (BT-84) shall be present";
            }
        };
        $res['BR-64'] = static function(Invoice $inv) {
            foreach ($inv->getLines() as $line) {
                if ($line->getStandardIdentifier() === null) continue;
                if ($line->getStandardIdentifier()->getScheme() === null) {
                    return "The Item standard identifier (BT-157) shall have a Scheme identifier";
                }
            }
        };
        $res['BR-65'] = static function(Invoice $inv) {
            foreach ($inv->getLines() as $line) {
                foreach ($line->getClassificationIdentifiers() as $identifier) {
                    if ($identifier->getScheme() === null) {
                        return "The Item classification identifier (BT-158) shall have a Scheme identifier";
                    }
                }
            }
        };

        return $res;
    }
}
