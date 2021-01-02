<?php
namespace Einvoicing\Traits;

use InvalidArgumentException;
use function count;

trait PostalAddressTrait {
    protected $address = [];
    protected $city = null;
    protected $postalCode = null;
    protected $subdivision = null;
    protected $country = null;

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
     * @return self                   This instance
     * @throws InvalidArgumentException if more than 3 lines are provided
     */
    public function setAddress(array $addressLines): self {
        if (count($addressLines) > 3) {
            throw new InvalidArgumentException('Address cannot have more than 3 lines');
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
     * @return self              This instance
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
     * @return self                    This instance
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
     * @return self                     This instance
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
     * @return self                     This instance
     */
    public function setCountry(?string $countryCode): self {
        $this->country = $countryCode;
        return $this;
    }
}
