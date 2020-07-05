<?php
namespace Einvoicing;

class Invoice {
    private $number = null;
    private $type = 380; // TODO: add constants
    private $currency = "EUR"; // TODO: add constants
    private $issueDate = null;
    private $dueDate = null;
    private $note = null;
    private $seller = null;
    private $buyer = null;
    private $payee = null;
    private $lines = [];

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
     * @return \DateTime|null Invoice issue date
     */
    public function getIssueDate(): ?\DateTime {
        return $this->issueDate;
    }


    /**
     * Set invoice issue date
     * @param  \DateTime $issueDate Invoice issue date
     * @return self                 Invoice instance
     */
    public function setIssueDate(\DateTime $issueDate): self {
        $this->issueDate = $issueDate;
        return $this;
    }


    /**
     * Get payment due date
     * @return \DateTime|null Payment due date
     */
    public function getDueDate(): ?\DateTime {
        return $this->dueDate;
    }


    /**
     * Set payment due date
     * @param  \DateTime|null $dueDate Payment due date
     * @return self                    Invoice instance
     */
    public function setDueDate(?\DateTime $dueDate): self {
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
     * @throws \OutOfBoundsException if line index is out of bounds
     */
    public function removeLine(int $index): self {
        if (($index < 0) || ($index >= count($this->lines))) {
            throw new \OutOfBoundsException('Could not find line by index inside invoice');
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
}
