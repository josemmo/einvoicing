<?php
namespace Einvoicing\AllowanceCharge;

trait AllowanceChargeTrait {
    protected $allowances = [];
    protected $charges = [];

    /**
     * Get allowances
     * @return Allowance[] Array of allowances
     */
    public function getAllowances(): array {
        return $this->allowances;
    }


    /**
     * Add allowance
     * @param  Allowance $allowance Allowance instance
     * @return self                 This instance
     */
    public function addAllowance(Allowance $allowance): self {
        $this->allowances[] = $allowance;
        return $this;
    }


    /**
     * Remove allowance
     * @param  int  $index Allowance index
     * @return self        This instance
     * @throws \OutOfBoundsException if allowance index is out of bounds
     */
    public function removeAllowance(int $index): self {
        if ($index < 0 || $index >= count($this->allowances)) {
            throw new \OutOfBoundsException('Could not find allowance by index');
        }
        array_splice($this->allowances, $index, 1);
        return $this;
    }


    /**
     * Clear all allowances
     * @return self This instance
     */
    public function clearAllowances(): self {
        $this->allowances = [];
        return $this;
    }


    /**
     * Get charges
     * @return Charge[] Array of charges
     */
    public function getCharges(): array {
        return $this->charges;
    }


    /**
     * Add charge
     * @param  Charge $charge Charge instance
     * @return self           This instance
     */
    public function addCharge(Charge $charge): self {
        $this->charges[] = $charge;
        return $this;
    }


    /**
     * Remove charge
     * @param  int  $index Charge index
     * @return self        This instance
     * @throws \OutOfBoundsException if charge index is out of bounds
     */
    public function removeCharge(int $index): self {
        if ($index < 0 || $index >= count($this->charges)) {
            throw new \OutOfBoundsException('Could not find charge by index');
        }
        array_splice($this->charges, $index, 1);
        return $this;
    }


    /**
     * Clear all charges
     * @return self This instance
     */
    public function clearCharges(): self {
        $this->charges = [];
        return $this;
    }


    /**
     * Get allowances/charges total amount
     * @param  AllowanceChargeBase[] $items      List of items
     * @param  float                 $baseAmount Base amount for percentage allowances/charges
     * @param  int                   $decimals   Number of decimal places
     * @return float                             Allowances/charges total amount
     */
    protected function getAllowancesChargesAmount(array $items, float $baseAmount, int $decimals): float {
        $amount = 0;
        foreach ($items as $allowance) {
            $itemAmount = $allowance->getAmount();
            if ($allowance->isPercentage()) {
                $itemAmount = $baseAmount * ($itemAmount / 100);
            }
            $amount += round($itemAmount, $decimals);
        }
        return $amount;
    }
}
