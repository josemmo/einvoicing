<?php
namespace Einvoicing\Traits;

use Einvoicing\AllowanceOrCharge;

trait AllowanceOrChargeTrait {
    protected $allowances = [];
    protected $charges = [];

    /**
     * Get allowances
     * @return AllowanceOrCharge[] Array of allowances
     */
    public function getAllowances(): array {
        return $this->allowances;
    }


    /**
     * Add allowance
     * @param  AllowanceOrCharge $allowance Allowance instance
     * @return self                         This instance
     */
    public function addAllowance(AllowanceOrCharge $allowance): self {
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
     * @return AllowanceOrCharge[] Array of charges
     */
    public function getCharges(): array {
        return $this->charges;
    }


    /**
     * Add charge
     * @param  AllowanceOrCharge $charge Charge instance
     * @return self                      This instance
     */
    public function addCharge(AllowanceOrCharge $charge): self {
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
}
