<?php
namespace Einvoicing\Traits;

use Einvoicing\Identifier;
use OutOfBoundsException;
use function array_splice;
use function count;

trait ClassificationIdentifiersTrait {
    protected $classIdentifiers = [];

    /**
     * Get classification identifiers
     * @return Identifier[] Array of identifiers
     */
    public function getClassificationIdentifiers(): array {
        return $this->classIdentifiers;
    }


    /**
     * Add classification identifier
     * @param  Identifier $identifier Identifier instance
     * @return self                   This instance
     */
    public function addClassificationIdentifier(Identifier $identifier): self {
        $this->classIdentifiers[] = $identifier;
        return $this;
    }


    /**
     * Remove classification identifier
     * @param  int  $index Identifier index
     * @return self        This instance
     * @throws OutOfBoundsException if identifier index is out of bounds
     */
    public function removeClassificationIdentifier(int $index): self {
        if ($index < 0 || $index >= count($this->classIdentifiers)) {
            throw new OutOfBoundsException('Could not find classification identifier by index');
        }
        array_splice($this->classIdentifiers, $index, 1);
        return $this;
    }


    /**
     * Clear all classification identifiers
     * @return self This instance
     */
    public function clearClassificationIdentifiers(): self {
        $this->classIdentifiers = [];
        return $this;
    }
}
