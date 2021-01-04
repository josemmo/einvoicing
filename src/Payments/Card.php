<?php
namespace Einvoicing\Payments;

class Card {
    protected $pan = null;
    protected $network = null;
    protected $holder = null;

    /**
     * Get card PAN (Primary Account Number)
     * @return string|null Card PAN
     */
    public function getPan(): ?string {
        return $this->pan;
    }


    /**
     * Set card PAN (Primary Account Number)
     * @param  string $pan Card PAN
     * @return self        Card instance
     */
    public function setPan(string $pan): self {
        $this->pan = $pan;
        return $this;
    }


    /**
     * Get card network ID
     * @return string|null Card network ID
     */
    public function getNetwork(): ?string {
        return $this->network;
    }


    /**
     * Set card network ID
     * @param  string|null $network Card network ID
     * @return self                 Card instance
     */
    public function setNetwork(?string $network): self {
        $this->network = $network;
        return $this;
    }


    /**
     * Get card holder name
     * @return string|null Card holder name
     */
    public function getHolder(): ?string {
        return $this->holder;
    }


    /**
     * Set card holder name
     * @param  string|null $holder Card holder name
     * @return self                Card instance
     */
    public function setHolder(?string $holder): self {
        $this->holder = $holder;
        return $this;
    }
}
