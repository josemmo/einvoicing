<?php
namespace Einvoicing;

class Attribute {
    protected $name;
    protected $value;

    /**
     * Class constructor
     * @param string $name  Attribute name
     * @param string $value Attribute value
     */
    public function __construct(string $name, string $value) {
        $this->setName($name);
        $this->setValue($value);
    }


    /**
     * Get attribute name
     * @return string Attribute name
     */
    public function getName(): string {
        return $this->name;
    }


    /**
     * Set attribute name
     * @param  string $name Attribute name
     * @return self         Attribute instance
     */
    public function setName(string $name): self {
        $this->name = $name;
        return $this;
    }


    /**
     * Get attribute value
     * @return string Attribute value
     */
    public function getValue(): string {
        return $this->value;
    }


    /**
     * Set attribute value
     * @param  string $value Attribute value
     * @return self          Attribute instance
     */
    public function setValue(string $value): self {
        $this->value = $value;
        return $this;
    }
}
