<?php
namespace Einvoicing;

use Einvoicing\Traits\AllowanceOrChargeTrait;
use Einvoicing\Traits\AttributesTrait;
use Einvoicing\Traits\BuyerAccountingReferenceTrait;
use Einvoicing\Traits\ClassificationIdentifiersTrait;
use Einvoicing\Traits\PeriodTrait;
use Einvoicing\Traits\VatTrait;

class InvoiceLine {
    protected $id = null;
    protected $orderLineReference = null;
    protected $name = null;
    protected $description = null;
    protected $originCountry = null;
    protected $note = null;
    protected $standardIdentifier = null;
    protected $buyerIdentifier = null;
    protected $sellerIdentifier = null;
    protected $quantity = 1;
    protected $unit = "C62"; // TODO: add constants
    protected $price = null;
    protected $baseQuantity = 1;

    use AllowanceOrChargeTrait;
    use AttributesTrait;
    use BuyerAccountingReferenceTrait;
    use ClassificationIdentifiersTrait;
    use PeriodTrait;
    use VatTrait;

    /**
     * Get invoice line identifier
     * @return string|null Invoice line identifier
     */
    public function getId(): ?string {
        return $this->id;
    }


    /**
     * Set invoice line identifier
     * @param  string $id Invoice line identifier
     * @return self       Invoice line instance
     */
    public function setId(string $id): self {
        $this->id = $id;
        return $this;
    }


    /**
     * Get order line reference
     * @return string|null Order line reference
     */
    public function getOrderLineReference(): ?string {
        return $this->orderLineReference;
    }


    /**
     * Set order line reference
     * @param  string|null $reference Order line reference
     * @return self                   Invoice line instance
     */
    public function setOrderLineReference(?string $reference): self {
        $this->orderLineReference = $reference;
        return $this;
    }


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
     * Get item origin country
     * @return string|null Item origin country code
     */
    public function getOriginCountry(): ?string {
        return $this->originCountry;
    }


    /**
     * Set item origin country
     * @param  string|null $originCountry Item origin country code
     * @return self                       Invoice line instance
     */
    public function setOriginCountry(?string $originCountry): self {
        $this->originCountry = $originCountry;
        return $this;
    }


    /**
     * Get invoice line note
     * @return string|null Invoice line note
     */
    public function getNote(): ?string {
        return $this->note;
    }


    /**
     * Set invoice line note
     * @param  string|null $note Invoice line note
     * @return self              Invoice line instance
     */
    public function setNote(?string $note): self {
        $this->note = $note;
        return $this;
    }


    /**
     * Get item standard identifier
     * @return Identifier|null Item standard identifier
     */
    public function getStandardIdentifier(): ?Identifier {
        return $this->standardIdentifier;
    }


    /**
     * Set item standard identifier
     * @param  Identifier|null $identifier Item standard identifier
     * @return self                        Invoice line instance
     */
    public function setStandardIdentifier(?Identifier $identifier): self {
        $this->standardIdentifier = $identifier;
        return $this;
    }


    /**
     * Get buyer identifier
     * @return string|null Buyer identifier
     */
    public function getBuyerIdentifier(): ?string {
        return $this->buyerIdentifier;
    }


    /**
     * Set buyer identifier
     * @param  string|null $identifier Buyer identifier
     * @return self                    Invoice line instance
     */
    public function setBuyerIdentifier(?string $identifier): self {
        $this->buyerIdentifier = $identifier;
        return $this;
    }


    /**
     * Get seller identifier
     * @return string|null Seller identifier
     */
    public function getSellerIdentifier(): ?string {
        return $this->sellerIdentifier;
    }


    /**
     * Set seller identifier
     * @param  string|null $identifier Seller identifier
     * @return self                    Invoice line instance
     */
    public function setSellerIdentifier(?string $identifier): self {
        $this->sellerIdentifier = $identifier;
        return $this;
    }


    /**
     * Get quantity
     * @return float Quantity
     */
    public function getQuantity(): float {
        return $this->quantity;
    }


    /**
     * Set quantity
     * @param  float $quantity Quantity
     * @return self            Invoice line instance
     */
    public function setQuantity(float $quantity): self {
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
     * @param  float      $price        Price
     * @param  float|null $baseQuantity Base quantity
     * @return self                     Invoice line instance
     */
    public function setPrice(float $price, ?float $baseQuantity=null): self {
        $this->price = $price;
        if ($baseQuantity !== null) {
            $this->setBaseQuantity($baseQuantity);
        }
        return $this;
    }


    /**
     * Get base quantity
     * @return float Base quantity
     */
    public function getBaseQuantity(): float {
        return $this->baseQuantity;
    }


    /**
     * Set base quantity
     * @param  float $baseQuantity Base quantity
     * @return self                Invoice line instance
     */
    public function setBaseQuantity(float $baseQuantity): self {
        $this->baseQuantity = $baseQuantity;
        return $this;
    }


    /**
     * Get total net amount (without VAT) before allowances/charges
     * @return float|null Net amount before allowances/charges
     */
    public function getNetAmountBeforeAllowancesCharges(): ?float {
        if ($this->price === null || $this->baseQuantity <= 0) {
            return null;
        }
        return ($this->price / $this->baseQuantity) * $this->quantity;
    }


    /**
     * Get allowances total amount
     * @return float Allowances total amount
     */
    public function getAllowancesAmount(): float {
        $allowancesAmount = 0;
        $baseAmount = $this->getNetAmountBeforeAllowancesCharges() ?? 0.0;
        foreach ($this->getAllowances() as $item) {
            $allowancesAmount += $item->getEffectiveAmount($baseAmount);
        }
        return $allowancesAmount;
    }


    /**
     * Get charges total amount
     * @return float Charges total amount
     */
    public function getChargesAmount(): float {
        $chargesAmount = 0;
        $baseAmount = $this->getNetAmountBeforeAllowancesCharges() ?? 0.0;
        foreach ($this->getCharges() as $item) {
            $chargesAmount += $item->getEffectiveAmount($baseAmount);
        }
        return $chargesAmount;
    }


    /**
     * Get total net amount (without VAT)
     * NOTE: inclusive of line level allowances and charges
     * @return float|null Net amount
     */
    public function getNetAmount(): ?float {
        $netAmount = $this->getNetAmountBeforeAllowancesCharges();
        if ($netAmount === null) {
            return null;
        }
        $netAmount -= $this->getAllowancesAmount();
        $netAmount += $this->getChargesAmount();
        return $netAmount;
    }
}
