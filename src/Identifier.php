<?php
namespace Einvoicing;

class Identifier {
    protected $value;
    protected $scheme;

    /**
     * Class constructor
     * @param string      $value  Value
     * @param string|null $scheme Scheme ID
     */
    public function __construct(string $value, ?string $scheme=null) {
        $this->setValue($value);
        $this->setScheme($scheme);
    }


    /**
     * Get value
     * @return string Value
     */
    public function getValue(): string {
        return $this->value;
    }


    /**
     * Set value
     * @param  string $value Value
     * @return self          Identifier instance
     */
    public function setValue(string $value): self {
        $this->value = $value;
        return $this;
    }


    /**
     * Get scheme ID
     * @return string|null Scheme ID
     */
    public function getScheme(): ?string {
        return $this->scheme;
    }


    /**
     * Set scheme ID
     * @param  string|null $scheme Scheme ID
     * @return self                Identifier instance
     */
    public function setScheme(?string $scheme): self {
        $this->scheme = $scheme;
        return $this;
    }
}
