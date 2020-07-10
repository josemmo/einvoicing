<?php
namespace Einvoicing\Party;

class Party {
    protected $name = null;
    protected $tradingName = null;
    protected $companyId = null;
    protected $vatNumber = null;
    protected $address = [];
    protected $city = null;
    protected $postalCode = null;
    protected $subdivision = null;
    protected $country = null;

    /**
     * Get party legal name
     * @return string|null Party legal name
     */
    public function getName(): ?string {
        return $this->name;
    }


    /**
     * Set party legal name
     * @param  string|null $name Party legal name
     * @return self              Party instance
     */
    public function setName(?string $name): self {
        $this->name = $name;
        return $this;
    }


    /**
     * Get party trading name (also known as business name)
     * @return string|null Party trading name
     */
    public function getTradingName(): ?string {
        return $this->tradingName;
    }


    /**
     * Set party trading name (also known as business name)
     * @param  string|null $tradingName Party trading name
     * @return self                     Party instance
     */
    public function setTradingName(?string $tradingName): self {
        $this->tradingName = $tradingName;
        return $this;
    }

    
    /**
     * Get party company ID
     * @return string|null Party company ID
     */
    public function getCompanyId(): ?string {
        return $this->companyId;
    }


    /**
     * Set party company ID
     * @param  string|null $companyId Party company ID
     * @return self                   Party instance
     */
    public function setCompanyId(?string $companyId): self {
        $this->companyId = $companyId;
        return $this;
    }


    /**
     * Get party VAT number
     * @return string|null Party VAT number
     */
    public function getVatNumber(): ?string {
        return $this->vatNumber;
    }


    /**
     * Set party VAT number
     * @param  string|null $vatNumber Party VAT number
     * @return self                   Party instance
     */
    public function setVatNumber(?string $vatNumber): self {
        $this->vatNumber = $vatNumber;
        return $this;
    }


    /**
     * Get address lines
     * @return string[] Address lines (up to 3 lines)
     */
    public function getAddress(): array {
        return $this->address;
    }


    /**
     * Set address lines
     * @param  string[] $addressLines Address lines (up to 3 lines)
     * @return self                   Party instance
     * @throws \InvalidArgumentException if more than 3 lines are provided
     */
    public function setAddress(array $addressLines): self {
        if (count($addressLines) > 3) {
            throw new \InvalidArgumentException('Address cannot have more than 3 lines');
        }
        $this->address = $addressLines;
        return $this;
    }


    /**
     * Get city name
     * @return string|null City name
     */
    public function getCity(): ?string {
        return $this->city;
    }


    /**
     * Set city name
     * @param  string|null $city City name
     * @return self              Party instance
     */
    public function setCity(?string $city): self {
        $this->city = $city;
        return $this;
    }


    /**
     * Get postal code
     * @return string|null Postal code
     */
    public function getPostalCode(): ?string {
        return $this->postalCode;
    }


    /**
     * Set postal code
     * @param  string|null $postalCode Postal code
     * @return self                    Party instance
     */
    public function setPostalCode(?string $postalCode): self {
        $this->postalCode = $postalCode;
        return $this;
    }


    /**
     * Get country subdivision (region, province, etc.)
     * @return string|null Country subdivision
     */
    public function getSubdivision(): ?string {
        return $this->subdivision;
    }


    /**
     * Set country subdivision (region, province, etc.)
     * @param  string|null $subdivision Country subdivision
     * @return self                     Party instance
     */
    public function setSubdivision(?string $subdivision): self {
        $this->subdivision = $subdivision;
        return $this;
    }


    /**
     * Get country code
     * @return string|null Country code
     */
    public function getCountry(): ?string {
        return $this->country;
    }


    /**
     * Set country code
     * @param  string|null $countryCode Country code
     * @return self                     Party instance
     */
    public function setCountry(?string $countryCode): self {
        $this->country = $countryCode;
        return $this;
    }
}
