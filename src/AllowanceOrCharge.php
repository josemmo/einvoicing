<?php
namespace Einvoicing;

use Einvoicing\Traits\VatTrait;

class AllowanceOrCharge {
    protected $reasonCode = null;
    protected $reason = null;
    protected $amount = 0;
    protected $isPercentage = false;
    protected $baseAmount = null;

    use VatTrait;

    /**
     * Get reason code
     * @return string|null Reason code
     */
    public function getReasonCode(): ?string {
        return $this->reasonCode;
    }


    /**
     * Set reason code
     * @param  string|null $reasonCode Reason code
     * @return self                    This instance
     */
    public function setReasonCode(?string $reasonCode): self {
        $this->reasonCode = $reasonCode;
        return $this;
    }


    /**
     * Get reason
     * @return string|null Reason expressed as text
     */
    public function getReason(): ?string {
        return $this->reason;
    }


    /**
     * Set reason
     * @param  string|null $reason Reason expressed as text
     * @return self                This instance
     */
    public function setReason(?string $reason): self {
        $this->reason = $reason;
        return $this;
    }


    /**
     * Get amount
     * @return float Allowance/charge amount
     */
    public function getAmount(): float {
        return $this->amount;
    }


    /**
     * Set amount
     * @param  float $amount Allowance/charge amount
     * @return self          This instance
     */
    public function setAmount(float $amount): self {
        $this->amount = $amount;
        return $this;
    }


    /**
     * Is percentage
     * @return boolean Whether amount is a percentage or not
     */
    public function isPercentage(): bool {
        return $this->isPercentage;
    }


    /**
     * Mark as percentage
     * @return self This instance
     */
    public function markAsPercentage(): self {
        $this->isPercentage = true;
        return $this;
    }

    
    /**
     * Mark as fixed amount (not a percentage)
     * @return self This instance
     */
    public function markAsFixedAmount(): self {
        $this->isPercentage = false;
        return $this;
    }


    /**
     * Get base amount
     * @return float|null Base amount to which the percentage applies (BT-93, BT-100, BT-137, BT-142)
     */
    public function getBaseAmount(): ?float {
        return $this->baseAmount;
    }


    /**
     * Set base amount
     *
     * When set, percentage allowances and charges are calculated from this amount instead of the net amount of the
     * invoice or invoice line they belong to.
     *
     * @param  float|null $baseAmount Base amount to which the percentage applies (BT-93, BT-100, BT-137, BT-142)
     * @return self                   This instance
     */
    public function setBaseAmount(?float $baseAmount): self {
        $this->baseAmount = $baseAmount;
        return $this;
    }


    /**
     * Get effective amount relative to base amount
     * @param  float $baseAmount Base amount, ignored when the instance has its own base amount
     * @return float             Effective amount
     */
    public function getEffectiveAmount(float $baseAmount): float {
        $amount = $this->getAmount();
        if ($this->isPercentage()) {
            $amount = ($this->getBaseAmount() ?? $baseAmount) * ($amount / 100);
        }

        return $amount;
    }
}
