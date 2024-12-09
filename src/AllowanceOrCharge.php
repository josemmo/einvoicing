<?php
namespace Einvoicing;

use Einvoicing\Traits\VatTrait;

class AllowanceOrCharge {
    protected $reasonCode = null;
    protected $reason = null;
    protected $amount = null;
    protected $baseAmount = null;
    protected $factorMultiplier = null;
    protected $isPercentage = false;

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
     * Get base amount
     * @return float|null Allowance/charge base amount
     */
    public function getBaseAmount(): ?float {
        return $this->baseAmount;
    }


    /**
     * Set base amount
     * @param  float $baseAmount Allowance/charge base amount
     * @return self          This instance
     */
    public function setBaseAmount(float $baseAmount): self {
        $this->baseAmount = $baseAmount;
        return $this;
    }


    /**
     * Get factor multiplier
     * @return float|null Allowance/charge factor multiplier
     */
    public function getFactorMultiplier(): ?float {
        return $this->factorMultiplier;
    }


    /**
     * Set factor multiplier
     * @param  float $factorMultiplier Allowance/charge factor multiplier
     * @return self          This instance
     */
    public function setFactorMultiplier(float $factorMultiplier): self {
        $this->factorMultiplier = $factorMultiplier;
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
     * Get effective amount relative to base amount
     * @return float             Effective amount
     */
    public function getEffectiveAmount(): float {
        if ($this->isPercentage()) {
            return $this->baseAmount * ($this->factorMultiplier / 100);
        }

        return $this->getAmount();
    }
}
