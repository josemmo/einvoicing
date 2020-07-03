<?php
namespace Einvocing;

class Party {
    /**
     * Get party business name
     * @return string|null Party business name
     */
    public function getName(): ?string {
        // TODO
    }


    /**
     * Set party business name
     * @param  string|null $name Party business name
     * @return self              Party instance
     */
    public function setName(?string $name): self {
        // TODO
        return $this;
    }


    /**
     * Get party trading name
     * @return string|null Party trading name
     */
    public function getTradingName(): ?string {
        // TODO
    }


    /**
     * Set party trading name
     * @param  string|null $tradingName Party trading name
     * @return self                     Party instance
     */
    public function setTradingName(?string $tradingName): self {
        // TODO
        return $this;
    }

    
    /**
     * Get party company ID
     * @return string|null Party company ID
     */
    public function getCompanyId(): ?string {
        // TODO
    }


    /**
     * Set party company ID
     * @param  string|null $companyId Party company ID
     * @return self                   Party instance
     */
    public function setCompanyId(?string $companyId): self {
        // TODO
        return $this;
    }


    /**
     * Get party VAT number
     * @return string|null Party VAT number
     */
    public function getVatNumber(): ?string {
        // TODO
    }


    /**
     * Set party VAT number
     * @param  string|null $companyId Party VAT number
     * @return self                   Party instance
     */
    public function setVatNumber(?string $companyId): self {
        // TODO
        return $this;
    }


    /**
     * Get address lines
     * @return string[] Address lines (up to 3 lines)
     */
    public function getAddress(): array {
        // TODO
    }


    /**
     * Set address lines
     * @param  string[] $addressLines Address lines (up to 3 lines)
     * @return self                   Party instance
     */
    public function setAddress(array $addressLines): self {
        // TODO
        return $this;
    }


    /**
     * Get city name
     * @return string|null City name
     */
    public function getCity(): ?string {
        // TODO
    }


    /**
     * Set city name
     * @param  string|null $city City name
     * @return self              Party instance
     */
    public function setCity(?string $city): self {
        // TODO
        return $this;
    }


    /**
     * Get postal code
     * @return string|null Postal code
     */
    public function getPostalCode(): ?string {
        // TODO
    }


    /**
     * Set postal code
     * @param  string|null $postalCode Postal code
     * @return self                    Party instance
     */
    public function setPostalCode(?string $postalCode): self {
        // TODO
        return $this;
    }


    /**
     * Get country subdivision (region, province, etc.)
     * @return string|null Country subdivision
     */
    public function getSubdivision(): ?string {
        // TODO
    }


    /**
     * Set country subdivision (region, province, etc.)
     * @param  string|null $countrySubdivision Country subdivision
     * @return self                            Party instance
     */
    public function setSubdivision(?string $countrySubdivision): self {
        // TODO
        return $this;
    }


    /**
     * Get country code
     * @return string|null Country code
     */
    public function getCountry(): ?string {
        // TODO
    }


    /**
     * Set country code
     * @param  string|null $countryCode Country code
     * @return self                     Party instance
     */
    public function setCountry(?string $countryCode): self {
        // TODO
        return $this;
    }
}
