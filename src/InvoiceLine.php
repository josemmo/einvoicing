<?php
namespace Einvocing;

class InvoiceLine {
    /**
     * Get item name
     * @return string|null Item name
     */
    public function getName(): ?string {
        // TODO
    }


    /**
     * Set item name
     * @param  string $name Item name
     * @return self         Invoice line instance
     */
    public function setName(string $name): self {
        // TODO
        return $this;
    }


    /**
     * Get item description
     * @return string|null Item description
     */
    public function getDescription(): ?string {
        // TODO
    }


    /**
     * Set item description
     * @param  string|null $description Item description
     * @return self                     Invoice line instance
     */
    public function setDescription(?string $description): self {
        // TODO
        return $this;
    }


    /**
     * Get quantity
     * @return int|float Quantity
     */
    public function getQuantity() {
        // TODO
    }


    /**
     * Set quantity
     * @param  int|float $quantity Quantity
     * @return self                Invoice line instance
     */
    public function setQuantity($quantity): self {
        // TODO
        return $this;
    }


    /**
     * Get unit code
     * @return string Unit code
     */
    public function getUnit(): string {
        // TODO
    }


    /**
     * Set unit code
     * @param  string $unitCode Unit code
     * @return self             Invoice line instance
     */
    public function setUnit(string $unitCode): self {
        // TODO
        return $this;
    }


    /**
     * Get price
     * @return float|null Price
     */
    public function getPrice(): ?float {
        // TODO
    }


    /**
     * Set price
     * @param  float     $price        Price
     * @param  int|float $baseQuantity Base quantity
     * @return self                    Invoice line instance
     */
    public function setPrice(float $price, $baseQuantity=1): self {
        // TODO
        return $this;
    }


    /**
     * Get base quantity
     * @return int|float Base quantity
     */
    public function getBaseQuantity() {
        // TODO
    }


    /**
     * Set base quantity
     * @param  int|float $baseQuantity Base quantity
     * @return self                    Invoice line instance
     */
    public function setBaseQuantity($baseQuantity): self {
        // TODO
        return $this;
    }


    /**
     * Get VAT category code
     * @return string VAT category code
     */
    public function getVatCategory(): string {
        // TODO
    }


    /**
     * Set VAT category code
     * @param  string $categoryCode VAT category code
     * @return self                 Invoice line instance
     */
    public function setVatCategory(string $categoryCode): self {
        // TODO
        return $this;
    }


    /**
     * Get VAT rate
     * @return int|null VAT rate as a percentage
     */
    public function getVatRate(): ?int {
        // TODO
    }


    /**
     * Set VAT rate
     * @param  int $rate VAT rate as a percentage
     * @return self      Invoice line instance
     */
    public function setVatRate(int $rate): self {
        // TODO
        return $this;
    }
}
