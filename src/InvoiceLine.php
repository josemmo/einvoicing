<?php
namespace Einvoicing;

use DomainException;
use Einvoicing\Traits\AllowanceOrChargeTrait;
use Einvoicing\Traits\VatTrait;
use function round;

class InvoiceLine {
    protected $name = null;
    protected $description = null;
    protected $quantity = 1;
    protected $unit = "C62"; // TODO: add constants
    protected $price = null;
    protected $baseQuantity = 1;

    use AllowanceOrChargeTrait;
    use VatTrait;

    /**
     * Get item name
     * @return string|null Item name
     */
    public function getName(): ?string {
        return $this->name;
    }


    /**
     * Set item name
     * @param  string $name Item name
     * @return self         Invoice line instance
     */
    public function setName(string $name): self {
        $this->name = $name;
        return $this;
    }


    /**
     * Get item description
     * @return string|null Item description
     */
    public function getDescription(): ?string {
        return $this->description;
    }


    /**
     * Set item description
     * @param  string|null $description Item description
     * @return self                     Invoice line instance
     */
    public function setDescription(?string $description): self {
        $this->description = $description;
        return $this;
    }


    /**
     * Get quantity
     * @return int|float Quantity
     */
    public function getQuantity() {
        return $this->quantity;
    }


    /**
     * Set quantity
     * @param  int|float $quantity Quantity
     * @return self                Invoice line instance
     */
    public function setQuantity($quantity): self {
        $this->quantity = $quantity;
        return $this;
    }


    /**
     * Get unit code
     * @return string Unit code
     */
    public function getUnit(): string {
        return $this->unit;
    }


    /**
     * Set unit code
     * @param  string $unitCode Unit code
     * @return self             Invoice line instance
     */
    public function setUnit(string $unitCode): self {
        $this->unit = $unitCode;
        return $this;
    }


    /**
     * Get price
     * @return float|null Price
     */
    public function getPrice(): ?float {
        return $this->price;
    }


    /**
     * Set price
     * @param  float          $price        Price
     * @param  int|float|null $baseQuantity Base quantity
     * @return self                         Invoice line instance
     * @throws DomainException if base quantity is not greater than zero
     */
    public function setPrice(float $price, $baseQuantity=null): self {
        $this->price = $price;
        if ($baseQuantity !== null) {
            $this->setBaseQuantity($baseQuantity);
        }
        return $this;
    }


    /**
     * Get base quantity
     * @return int|float Base quantity
     */
    public function getBaseQuantity() {
        return $this->baseQuantity;
    }


    /**
     * Set base quantity
     * @param  int|float $baseQuantity Base quantity
     * @return self                    Invoice line instance
     * @throws DomainException if base quantity is not greater than zero
     */
    public function setBaseQuantity($baseQuantity): self {
        if ($baseQuantity <= 0) {
            throw new DomainException('Base quantity must be greater than zero');
        }
        $this->baseQuantity = $baseQuantity;
        return $this;
    }


    /**
     * Get total net amount (without VAT) before allowances/charges
     * @param  int        $decimals Number of decimal places
     * @return float|null           Net amount before allowances/charges
     */
    public function getNetAmountBeforeAllowancesCharges(int $decimals=Invoice::DEFAULT_DECIMALS): ?float {
        if ($this->price === null) {
            return null;
        }
        return round(($this->price / $this->baseQuantity) * $this->quantity, $decimals);
    }


    /**
     * Get allowances total amount
     * @param  int   $decimals Number of decimal places
     * @return float           Allowances total amount
     */
    public function getAllowancesAmount(int $decimals=Invoice::DEFAULT_DECIMALS): float {
        $allowancesAmount = 0;
        $baseAmount = $this->getNetAmountBeforeAllowancesCharges($decimals) ?? 0;
        foreach ($this->getAllowances() as $item) {
            $allowancesAmount += $item->getEffectiveAmount($baseAmount, $decimals);
        }
        return $allowancesAmount;
    }


    /**
     * Get charges total amount
     * @param  int   $decimals Number of decimal places
     * @return float           Charges total amount
     */
    public function getChargesAmount(int $decimals=Invoice::DEFAULT_DECIMALS): float {
        $chargesAmount = 0;
        $baseAmount = $this->getNetAmountBeforeAllowancesCharges($decimals) ?? 0;
        foreach ($this->getCharges() as $item) {
            $chargesAmount += $item->getEffectiveAmount($baseAmount, $decimals);
        }
        return $chargesAmount;
    }


    /**
     * Get total net amount (without VAT)
     * NOTE: inclusive of line level allowances and charges
     * @param  int        $decimals Number of decimal places
     * @return float|null           Net amount
     */
    public function getNetAmount(int $decimals=Invoice::DEFAULT_DECIMALS): ?float {
        $netAmount = $this->getNetAmountBeforeAllowancesCharges($decimals);
        if ($netAmount === null) {
            return null;
        }
        $netAmount -= $this->getAllowancesAmount($decimals);
        $netAmount += $this->getChargesAmount($decimals);
        return $netAmount;
    }
}
