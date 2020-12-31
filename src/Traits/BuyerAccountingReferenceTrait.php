<?php
namespace Einvoicing\Traits;

trait BuyerAccountingReferenceTrait {
    protected $buyerAccountingReference = null;

    /**
     * Get buyer accounting reference
     * @return string|null Buyer accounting reference
     */
    public function getBuyerAccountingReference(): ?string {
        return $this->buyerAccountingReference;
    }


    /**
     * Set buyer accounting reference
     * @param  string|null $buyerAccountingReference Buyer accounting reference
     * @return self                                  This instance
     */
    public function setBuyerAccountingReference(?string $buyerAccountingReference): self {
        $this->buyerAccountingReference = $buyerAccountingReference;
        return $this;
    }
}
