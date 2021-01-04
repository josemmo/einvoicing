<?php
namespace Einvoicing\Payments;

class Payment {
    protected $id = null;
    protected $meansCode = null;
    protected $meansText = null;
    protected $terms = null;
    protected $card = null;
    protected $mandate = null;

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


    /**
     * Get payment card
     * @return Card|null Card instance
     */
    public function getCard(): ?Card {
        return $this->card;
    }


    /**
     * Set payment card
     * @param  Card|null $card Card instance
     * @return self            Payment instance
     */
    public function setCard(?Card $card): self {
        $this->card = $card;
        return $this;
    }


    /**
     * Get payment mandate
     * @return Mandate|null Mandate instance
     */
    public function getMandate(): ?Mandate {
        return $this->mandate;
    }


    /**
     * Set payment mandate
     * @param  Mandate|null $mandate Mandate instance
     * @return self                  Payment instance
     */
    public function setMandate(?Mandate $mandate): self {
        $this->mandate = $mandate;
        return $this;
    }
}
