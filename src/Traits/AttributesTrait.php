<?php
namespace Einvoicing\Traits;

use Einvoicing\Attribute;
use OutOfBoundsException;
use function array_splice;
use function count;

trait AttributesTrait {
    protected $attributes = [];

    /**
     * Get attributes
     * @return Attribute[] Array of attributes
     */
    public function getAttributes(): array {
        return $this->attributes;
    }


    /**
     * Add attribute
     * @param  Attribute $attribute Attribute instance
     * @return self                 This instance
     */
    public function addAttribute(Attribute $attribute): self {
        $this->attributes[] = $attribute;
        return $this;
    }


    /**
     * Remove attribute
     * @param  int  $index Attribute index
     * @return self        This instance
     * @throws OutOfBoundsException if attribute index is out of bounds
     */
    public function removeAttribute(int $index): self {
        if ($index < 0 || $index >= count($this->attributes)) {
            throw new OutOfBoundsException('Could not find attribute by index');
        }
        array_splice($this->attributes, $index, 1);
        return $this;
    }


    /**
     * Clear all attributes
     * @return self This instance
     */
    public function clearAttributes(): self {
        $this->attributes = [];
        return $this;
    }


    /**
     * Get attribute value
     * @param  string      $name Attribute name
     * @return string|null       Value from occurrence with given name or NULL if not found
     */
    public function getAttribute(string $name): ?string {
        foreach ($this->attributes as $attribute) {
            if ($attribute->getName() === $name) {
                return $attribute->getValue();
            }
        }
        return null;
    }


    /**
     * Set attribute value
     * @param  string      $name  Attribute name
     * @param  string|null $value New attribute value for first found occurrence, NULL to remove
     * @return self               This instance
     * @suppress PhanThrowTypeAbsentForCall, PhanTypeMismatchArgumentNullable
     */
    public function setAttribute(string $name, ?string $value): self {
        $index = null;
        $attribute = null;
        foreach ($this->attributes as $i=>$a) {
            if ($a->getName() === $name) {
                $index = $i;
                $attribute = $a;
                break;
            }
        }

        if ($attribute === null && $value !== null) {
            $this->addAttribute(new Attribute($name, $value));
        } elseif ($attribute !== null && $value !== null) {
            $attribute->setValue($value);
        } elseif ($attribute !== null && $value === null) {
            $this->removeAttribute($index);
        }

        return $this;
    }
}
