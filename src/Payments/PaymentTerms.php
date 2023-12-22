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
     * @param  string|null $id note
     * @return self            PaymentTerms instance
     */
    public function setNote(?string $id): self {
        $this->note = $id;
        return $this;
    }
}
