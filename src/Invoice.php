<?php
namespace Einvoicing;

use DateTime;
use Einvoicing\Models\InvoiceTotals;
use Einvoicing\Traits\AllowanceOrChargeTrait;
use OutOfBoundsException;
use function array_splice;
use function count;
use function round;

class Invoice {
    const DEFAULT_DECIMALS = 8;

    protected $roundingMatrix = null;
    protected $specification = null;
    protected $number = null;
    protected $type = 380; // TODO: add constants
    protected $currency = "EUR"; // TODO: add constants
    protected $issueDate = null;
    protected $dueDate = null;
    protected $note = null;
    protected $paidAmount = 0;
    protected $roundingAmount = 0;
    protected $seller = null;
    protected $buyer = null;
    protected $payee = null;
    protected $lines = [];

    use AllowanceOrChargeTrait;

    /**
     * Get number of decimal places for a given field
     * @param  string $field Field name
     * @return int           Number of decimal places
     */
    public function getDecimals(string $field): int {
        return $this->roundingMatrix[$field] ?? $this->roundingMatrix[null] ?? self::DEFAULT_DECIMALS;
    }


    /**
     * Set rounding matrix
     * @param  array $matrix Rounding matrix
     * @return self          Invoice instance
     */
    public function setRoundingMatrix(array $matrix): self {
        $this->roundingMatrix = $matrix;
        return $this;
    }


    /**
     * Get specification identifier
     * @return string|null Specification identifier
     */
    public function getSpecification(): ?string {
        return $this->specification;
    }


    /**
     * Set specification identifier
     * @param  string $specification Specification identifier
     * @return self                  Invoice instance
     */
    public function setSpecification(string $specification): self {
        $this->specification = $specification;
        return $this;
    }


    /**
     * Get invoice number
     * @return string|null Invoice number
     */
    public function getNumber(): ?string {
        return $this->number;
    }


    /**
     * Set invoice number
     * @param  string $number Invoice number
     * @return self           Invoice instance
     */
    public function setNumber(string $number): self {
        $this->number = $number;
        return $this;
    }


    /**
     * Get invoice type code
     * @return int Invoice type code
     */
    public function getType(): int {
        return $this->type;
    }


    /**
     * Set invoice type code
     * @param  int  $typeCode Invoice type code
     * @return self           Invoice instance
     */
    public function setType(int $typeCode): self {
        $this->type = $typeCode;
        return $this;
    }


    /**
     * Get document currency code
     * @return string Document currency code
     */
    public function getCurrency(): string {
        return $this->currency;
    }


    /**
     * Set document currency code
     * @param  string $currencyCode Document currency code
     * @return self                 Invoice instance
     */
    public function setCurrency(string $currencyCode): self {
        $this->currency = $currencyCode;
        return $this;
    }


    /**
     * Get invoice issue date
     * @return DateTime|null Invoice issue date
     */
    public function getIssueDate(): ?DateTime {
        return $this->issueDate;
    }


    /**
     * Set invoice issue date
     * @param  DateTime $issueDate Invoice issue date
     * @return self                Invoice instance
     */
    public function setIssueDate(DateTime $issueDate): self {
        $this->issueDate = $issueDate;
        return $this;
    }


    /**
     * Get payment due date
     * @return DateTime|null Payment due date
     */
    public function getDueDate(): ?DateTime {
        return $this->dueDate;
    }


    /**
     * Set payment due date
     * @param  DateTime|null $dueDate Payment due date
     * @return self                   Invoice instance
     */
    public function setDueDate(?DateTime $dueDate): self {
        $this->dueDate = $dueDate;
        return $this;
    }


    /**
     * Get invoice note
     * @return string|null Invoice note
     */
    public function getNote(): ?string {
        return $this->note;
    }


    /**
     * Set invoice note
     * @param  string|null $note Invoice note
     * @return self              Invoice instance
     */
    public function setNote(?string $note): self {
        $this->note = $note;
        return $this;
    }


    /**
     * Get invoice prepaid amount
     * NOTE: may be rounded according to the CIUS specification
     * @return float Invoice prepaid amount
     */
    public function getPaidAmount(): float {
        return round($this->paidAmount, $this->getDecimals('invoice/paidAmount'));
    }


    /**
     * Set invoice prepaid amount
     * @param  float $paidAmount Invoice prepaid amount
     * @return self              Invoice instance
     */
    public function setPaidAmount(float $paidAmount): self {
        $this->paidAmount = $paidAmount;
        return $this;
    }


    /**
     * Get invoice rounding amount
     * NOTE: may be rounded according to the CIUS specification
     * @return float Invoice rounding amount
     */
    public function getRoundingAmount(): float {
        return round($this->roundingAmount, $this->getDecimals('invoice/roundingAmount'));
    }


    /**
     * Set invoice rounding amount
     * @param  float $roundingAmount Invoice rounding amount
     * @return self                  Invoice instance
     */
    public function setRoundingAmount(float $roundingAmount): self {
        $this->roundingAmount = $roundingAmount;
        return $this;
    }


    /**
     * Get seller
     * @return Party|null Seller instance
     */
    public function getSeller(): ?Party {
        return $this->seller;
    }


    /**
     * Set seller
     * @param  Party $seller Seller instance
     * @return self          Invoice instance
     */
    public function setSeller(Party $seller): self {
        $this->seller = $seller;
        return $this;
    }


    /**
     * Get buyer
     * @return Party|null Buyer instance
     */
    public function getBuyer(): ?Party {
        return $this->buyer;
    }


    /**
     * Set buyer
     * @param  Party $buyer Buyer instance
     * @return self          Invoice instance
     */
    public function setBuyer(Party $buyer): self {
        $this->buyer = $buyer;
        return $this;
    }


    /**
     * Get payee
     * @return Party|null Payee instance
     */
    public function getPayee(): ?Party {
        return $this->payee;
    }


    /**
     * Set payee
     * @param  Party|null $payee Payee instance
     * @return self              Invoice instance
     */
    public function setPayee(?Party $payee): self {
        $this->payee = $payee;
        return $this;
    }


    /**
     * Get invoice lines
     * @return InvoiceLine[] Invoice lines
     */
    public function getLines(): array {
        return $this->lines;
    }


    /**
     * Add invoice line
     * @param  InvoiceLine $line Invoice line instance
     * @return self              Invoice instance
     */
    public function addLine(InvoiceLine $line): self {
        $this->lines[] = $line;
        return $this;
    }


    /**
     * Remove invoice line
     * @param  int  $index Invoice line index
     * @return self        Invoice instance
     * @throws OutOfBoundsException if line index is out of bounds
     */
    public function removeLine(int $index): self {
        if ($index < 0 || $index >= count($this->lines)) {
            throw new OutOfBoundsException('Could not find line by index inside invoice');
        }
        array_splice($this->lines, $index, 1);
        return $this;
    }


    /**
     * Clear all invoice lines
     * @return self Invoice instance
     */
    public function clearLines(): self {
        $this->lines = [];
        return $this;
    }


    /**
     * Get invoice total
     * @return InvoiceTotals Invoice totals
     */
    public function getTotals(): InvoiceTotals {
        return InvoiceTotals::fromInvoice($this);
    }
}
