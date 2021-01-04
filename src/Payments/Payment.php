<?php
namespace Einvoicing\Payments;

class Payment {
    protected $id = null;
    protected $meansCode = null;
    protected $meansText = null;
    protected $terms = null;

    /**
     * Get payment ID
     * @return string|null Payment ID
     */
    public function getId(): ?string {
        return $this->id;
    }


    /**
     * Set payment ID
     * @param  string|null $id Payment ID
     * @return self            Payment instance
     */
    public function setId(?string $id): self {
        $this->id = $id;
        return $this;
    }


    /**
     * Get payment means code
     * @return string|null Payment means code
     */
    public function getMeansCode(): ?string {
        return $this->meansCode;
    }


    /**
     * Set payment means code
     * @param  string $meansCode Payment means code
     * @return self              Payment instance
     */
    public function setMeansCode(string $meansCode): self {
        $this->meansCode = $meansCode;
        return $this;
    }


    /**
     * Get payment means text
     * @return string|null Payment means text
     */
    public function getMeansText(): ?string {
        return $this->meansText;
    }


    /**
     * Set payment means text
     * @param  string|null $meansText Payment means text
     * @return self                   Payment instance
     */
    public function setMeansText(?string $meansText): self {
        $this->meansText = $meansText;
        return $this;
    }


    /**
     * Get payment terms
     * @return string|null Payment terms
     */
    public function getTerms(): ?string {
        return $this->terms;
    }


    /**
     * Set payment terms
     * @param  string|null $terms Payment terms
     * @return self               Payment instance
     */
    public function setTerms(?string $terms): self {
        $this->terms = $terms;
        return $this;
    }
}
