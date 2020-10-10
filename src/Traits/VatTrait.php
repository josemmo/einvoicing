<?php
namespace Einvoicing\Traits;

trait VatTrait {
    protected $vatCategory = "S"; // TODO: add constants
    protected $vatRate = null;

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
     * @return self                 This instance
     */
    public function setVatCategory(string $categoryCode): self {
        $this->vatCategory = $categoryCode;
        return $this;
    }


    /**
     * Get VAT rate
     * @return float|null VAT rate as a percentage or NULL when not subject to VAT
     */
    public function getVatRate(): ?float {
        return $this->vatRate;
    }


    /**
     * Set VAT rate
     * @param  float|null $rate VAT rate as a percentage or NULL when not subject to VAT
     * @return self             This instance
     */
    public function setVatRate(?float $rate): self {
        $this->vatRate = $rate;
        return $this;
    }
}
