<?php
namespace Einvoicing\Traits;

use DomainException;

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
     * @throws DomainException if VAT rate is negative
     */
    public function setVatRate(?float $rate): self {
        if ($rate < 0) {
            throw new DomainException('VAT rate cannot be negative');
        }
        $this->vatRate = $rate;
        return $this;
    }
}
