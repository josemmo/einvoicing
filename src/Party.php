<?php
namespace Einvoicing;

use Einvoicing\Traits\IdentifiersTrait;
use Einvoicing\Traits\PostalAddressTrait;

class Party {
    protected $electronicAddress = null;
    protected $name = null;
    protected $tradingName = null;
    protected $companyId = null;
    protected $vatNumber = null;
    protected $taxRegistrationId = null;
    protected $contactName = null;
    protected $contactPhone = null;
    protected $contactEmail = null;

    use IdentifiersTrait;
    use PostalAddressTrait;

    /**
     * Get electronic address
     * @return Identifier|null Electronic address
     */
    public function getElectronicAddress(): ?Identifier {
        return $this->electronicAddress;
    }


    /**
     * Set electronic address
     * @param  Identifier|null $electronicAddress Electronic address
     * @return self                               Party instance
     */
    public function setElectronicAddress(?Identifier $electronicAddress): self {
        $this->electronicAddress = $electronicAddress;
        return $this;
    }


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
     * Get party company legal ID
     * @return Identifier|null Party company legal ID
     */
    public function getCompanyId(): ?Identifier {
        return $this->companyId;
    }


    /**
     * Set party company legal ID
     * @param  Identifier|null $companyId Party company legal ID
     * @return self                       Party instance
     */
    public function setCompanyId(?Identifier $companyId): self {
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
     * Get tax registration ID
     * @return Identifier|null Tax registration ID
     */
    public function getTaxRegistrationId(): ?Identifier {
        return $this->taxRegistrationId;
    }


    /**
     * Set tax registration ID
     * @param  Identifier|null $taxRegistrationId Tax registration ID
     * @return self                               Party instance
     */
    public function setTaxRegistrationId(?Identifier $taxRegistrationId): self {
        $this->taxRegistrationId = $taxRegistrationId;
        return $this;
    }


    /**
     * Get contact point name
     * @return string|null Contact name
     */
    public function getContactName(): ?string {
        return $this->contactName;
    }


    /**
     * Set contact point name
     * @param  string|null $contactName Contact name
     * @return self                     This instance
     */
    public function setContactName(?string $contactName): self {
        $this->contactName = $contactName;
        return $this;
    }


    /**
     * Get contact point phone number
     * @return string|null Contact phone number
     */
    public function getContactPhone(): ?string {
        return $this->contactPhone;
    }


    /**
     * Set contact point phone number
     * @param  string|null $contactPhone Contact phone number
     * @return self                      This instance
     */
    public function setContactPhone(?string $contactPhone): self {
        $this->contactPhone = $contactPhone;
        return $this;
    }


    /**
     * Get contact point email addresss
     * @return string|null Contact email address
     */
    public function getContactEmail(): ?string {
        return $this->contactEmail;
    }


    /**
     * Set contact point email address
     * @param  string|null $contactEmail Contact email address
     * @return self                      This instance
     */
    public function setContactEmail(?string $contactEmail): self {
        $this->contactEmail = $contactEmail;
        return $this;
    }


    /**
     * Has contact information
     * @return boolean TRUE if party has any contact information, FALSE otherwise
     */
    public function hasContactInformation(): bool {
        return ($this->contactName !== null) || ($this->contactPhone !== null) || ($this->contactEmail !== null);
    }
}
