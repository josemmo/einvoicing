<?php
namespace Einvoicing\Payments;

use Einvoicing\Traits\NormalizesStringsTrait;

class Transfer {
    use NormalizesStringsTrait;
    protected $accountId = null;
    protected $accountName = null;
    protected $provider = null;

    /**
     * Get receiving account ID
     * @return string|null Account ID
     */
    public function getAccountId(): ?string {
        return $this->accountId;
    }


    /**
     * Set receiving account ID
     * @param  string $accountId Account ID
     * @return self              Transfer instance
     */
    public function setAccountId(string $accountId): self {
        $this->accountId = $accountId;
        return $this;
    }


    /**
     * Get receiving account name
     * @return string|null Account name
     */
    public function getAccountName(): ?string {
        return $this->accountName;
    }


    /**
     * Set receiving account name
     * @param  string|null $accountName Account name
     * @return self                     Transfer instance
     */
    public function setAccountName(?string $accountName): self {
        $this->accountName = $this->normalizeString($accountName);
        return $this;
    }


    /**
     * Get service provider ID
     * @return string|null Service provider ID
     */
    public function getProvider(): ?string {
        return $this->provider;
    }


    /**
     * Set service provider ID
     * @param  string|null $provider Service provider ID
     * @return self                  Transfer instance
     */
    public function setProvider(?string $provider): self {
        $this->provider = $this->normalizeString($provider);
        return $this;
    }
}
