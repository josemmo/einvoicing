<?php
namespace Einvoicing\Payments;

use Einvoicing\Traits\NormalizesStringsTrait;

class Mandate {
    use NormalizesStringsTrait;
    protected $reference = null;
    protected $account = null;

    /**
     * Get mandate reference ID
     * @return string|null Mandate reference ID
     */
    public function getReference(): ?string {
        return $this->reference;
    }


    /**
     * Set mandate reference ID
     * @param  string|null $reference Mandate reference ID
     * @return self                   Mandate instance
     */
    public function setReference(?string $reference): self {
        $this->reference = $this->normalizeString($reference);
        return $this;
    }


    /**
     * Get debited account ID
     * @return string|null Debited account ID
     */
    public function getAccount(): ?string {
        return $this->account;
    }


    /**
     * Set debited account ID
     * @param  string|null $account Debited account ID
     * @return self                 Mandate instance
     */
    public function setAccount(?string $account): self {
        $this->account = $this->normalizeString($account);
        return $this;
    }
}
