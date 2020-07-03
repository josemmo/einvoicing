<?php
namespace Einvocing;

class Invoice {
    /**
     * Get invoice number
     * @return string|null Invoice number
     */
    public function getNumber(): ?string {
        // TODO
    }


    /**
     * Set invoice number
     * @param  string $number Invoice number
     * @return self           Invoice instance
     */
    public function setNumber(string $number): self {
        // TODO
        return $this;
    }


    /**
     * Get invoice type code
     * @return int Invoice type code
     */
    public function getType(): int {
        // TODO
    }


    /**
     * Set invoice type code
     * @param  int  $typeCode Invoice type code
     * @return self           Invoice instance
     */
    public function setType(int $typeCode): self {
        // TODO
        return $this;
    }



    /**
     * Get document currency code
     * @return string Document currency code
     */
    public function getCurrency(): string {
        // TODO
    }


    /**
     * Set document currency code
     * @param  string $currencyCode Document currency code
     * @return self                 Invoice instance
     */
    public function setCurrency(string $currencyCode): self {
        // TODO
        return $this;
    }


    /**
     * Get invoice issue date
     * @return \DateTime|null Invoice issue date
     */
    public function getIssueDate(): ?\DateTime {
        // TODO
    }


    /**
     * Set invoice issue date
     * @param  \DateTime $issueDate Invoice issue date
     * @return self                 Invoice instance
     */
    public function setIssueDate(\DateTime $issueDate): self {
        // TODO
        return $this;
    }


    /**
     * Get payment due date
     * @return \DateTime|null Payment due date
     */
    public function getDueDate(): ?\DateTime {
        // TODO
    }


    /**
     * Set payment due date
     * @param  \DateTime|null $dueDate Payment due date
     * @return self                    Invoice instance
     */
    public function setDueDate(?\DateTime $dueDate): self {
        // TODO
        return $this;
    }


    /**
     * Get invoice note
     * @return string|null Invoice note
     */
    public function getNote(): ?string {
        // TODO
    }


    /**
     * Set invoice note
     * @param  string|null $note Invoice note
     * @return self              Invoice instance
     */
    public function setNote(?string $note): self {
        // TODO
        return $this;
    }


    /**
     * Get seller
     * @return Party|null Seller instance
     */
    public function getSeller(): ?Party {
        // TODO
    }


    /**
     * Set seller
     * @param  Party $seller Seller instance
     * @return self          Invoice instance
     */
    public function setSeller(Party $seller): self {
        // TODO
        return $this;
    }


    /**
     * Get buyer
     * @return Party|null Buyer instance
     */
    public function getBuyer(): ?Party {
        // TODO
    }


    /**
     * Set buyer
     * @param  Party $buyer Buyer instance
     * @return self          Invoice instance
     */
    public function setBuyer(Party $buyer): self {
        // TODO
        return $this;
    }


    /**
     * Get payee
     * @return Party|null Payee instance
     */
    public function getPayee(): ?Party {
        // TODO
    }


    /**
     * Set payee
     * @param  Party|null $payee Payee instance
     * @return self              Invoice instance
     */
    public function setPayee(?Party $payee): self {
        // TODO
        return $this;
    }


    /**
     * Get invoice lines
     * @return InvoiceLine[] Invoice lines
     */
    public function getLines(): array {
        // TODO
    }


    /**
     * Add invoice line
     * @param  InvoiceLine $line Invoice line instance
     * @return self              Invoice instance
     */
    public function addLine(InvoiceLine $line): self {
        // TODO
        return $this;
    }


    /**
     * Remove invoice line
     * @param  int  $index Invoice line index
     * @return self        Invoice instance
     * @throws \OutOfBoundsException if line index is out of bounds
     */
    public function removeLine(int $index): self {
        // TODO
        return $this;
    }


    /**
     * Clear all invoice lines
     * @return self Invoice instance
     */
    public function clearLines(): self {
        // TODO
        return $this;
    }
}
