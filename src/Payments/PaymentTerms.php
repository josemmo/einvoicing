<?php

namespace Einvoicing\Payments;

class PaymentTerms {
    protected $note = null;

    /**
     * Get note
     * @return string|null note
     */
    public function getNote(): ?string {
        return $this->note;
    }


    /**
     * Set note
     * @param  string|null $note note
     * @return self              PaymentTerms instance
     */
    public function setNote(?string $note): self {
        $this->note = $note;
        return $this;
    }
}