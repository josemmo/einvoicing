<?php
namespace Einvoicing\Traits;

use Einvoicing\InvoiceReference;
use OutOfBoundsException;

trait PrecedingInvoiceReferencesTrait {
    protected $precedingInvoiceReferences = [];

    /**
     * Get preceding invoice references
     * @return InvoiceReference[] Array of preceding invoice references
     */
    public function getPrecedingInvoiceReferences(): array {
        return $this->precedingInvoiceReferences;
    }


    /**
     * Add preceding invoice reference
     * @param  InvoiceReference $reference Preceding invoice reference
     * @return self                        This instance
     */
    public function addPrecedingInvoiceReference(InvoiceReference $reference): self {
        $this->precedingInvoiceReferences[] = $reference;
        return $this;
    }


    /**
     * Remove preceding invoice reference
     * @param  int  $index Preceding invoice reference index
     * @return self        This instance
     * @throws OutOfBoundsException if preceding invoice reference index is out of bounds
     */
    public function removePrecedingInvoiceReference(int $index): self {
        if ($index < 0 || $index >= count($this->precedingInvoiceReferences)) {
            throw new OutOfBoundsException('Could not find preceding invoice reference by index');
        }
        array_splice($this->precedingInvoiceReferences, $index, 1);
        return $this;
    }


    /**
     * Clear all preceding invoice references
     * @return self This instance
     */
    public function clearPrecedingInvoiceReferences(): self {
        $this->precedingInvoiceReferences = [];
        return $this;
    }
}
