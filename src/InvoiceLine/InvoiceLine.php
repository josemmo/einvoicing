<?php
namespace Einvoicing\InvoiceLine;

class InvoiceLine {
    protected $name = null;
    protected $description = null;
    protected $quantity = 1;
    protected $unit = "C62"; // TODO: add constants
    protected $price = null;
    protected $baseQuantity = 1;
    protected $vatCategory = "S"; // TODO: add constants
    protected $vatRate = null;

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
     */
    public function setBaseQuantity($baseQuantity): self {
        $this->baseQuantity = $baseQuantity;
        return $this;
    }


    /**
     * Get VAT category code
     * @return string VAT category code
     */
    public function getVatCategory(): string {
        return $this->vatCategory;
    }


    /**
     * Set VAT category code
     * @param  string $categoryCode VAT category code
     * @return self                 Invoice line instance
     */
    public function setVatCategory(string $categoryCode): self {
        $this->vatCategory = $categoryCode;
        return $this;
    }


    /**
     * Get VAT rate
     * @return int|null VAT rate as a percentage
     */
    public function getVatRate(): ?int {
        return $this->vatRate;
    }


    /**
     * Set VAT rate
     * @param  int $rate VAT rate as a percentage
     * @return self      Invoice line instance
     */
    public function setVatRate(int $rate): self {
        $this->vatRate = $rate;
        return $this;
    }
}
