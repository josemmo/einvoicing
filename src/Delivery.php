<?php
namespace Einvoicing;

use DateTime;
use Einvoicing\Traits\PostalAddressTrait;

class Delivery {
    protected $name = null;
    protected $date = null;
    protected $locationIdentifier = null;

    use PostalAddressTrait;

    /**
     * Get party name
     * @return string|null Party name
     */
    public function getName(): ?string {
        return $this->name;
    }


    /**
     * Set party name
     * @param  string|null $name Party name
     * @return self              Delivery instance
     */
    public function setName(?string $name): self {
        $this->name = $name;
        return $this;
    }


    /**
     * Get actual delivery date
     * @return DateTime|null Actual delivery date
     */
    public function getDate(): ?DateTime {
        return $this->date;
    }


    /**
     * Set actual delivery date
     * @param  DateTime|null $date Actual delivery date
     * @return self                Delivery instance
     */
    public function setDate(?DateTime $date): self {
        $this->date = $date;
        return $this;
    }


    /**
     * Get delivery location identifier
     * @return Identifier|null Delivery location identifier
     */
    public function getLocationIdentifier(): ?Identifier {
        return $this->locationIdentifier;
    }


    /**
     * Set delivery location identifier
     * @param  Identifier|null $identifier Delivery location identifier
     * @return self                        Delivery instance
     */
    public function setLocationIdentifier(?Identifier $identifier): self {
        $this->locationIdentifier = $identifier;
        return $this;
    }
}
