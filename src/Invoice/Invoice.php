<?php
namespace Einvoicing\Invoice;

use Einvoicing\AllowanceCharge\AllowanceChargeTrait;
use Einvoicing\InvoiceLine\InvoiceLine;
use Einvoicing\Party\Party;

abstract class Invoice {
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

    use AllowanceChargeTrait;

    /**
     * Get invoice specification identifier
     * @return string Invoice specification identifier
     */
    public abstract function getSpecificationIdentifier(): string;


    /**
     * Get number of decimal places for a given field
     * @param  string $field Field name
     * @return int           Number of decimal places
     * @suppress PhanUnusedProtectedNoOverrideMethodParameter
     */
    protected function getDecimals(string $field): int {
        return 2;
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
     * @throws \OutOfBoundsException if line index is out of bounds
     */
    public function removeLine(int $index): self {
        if ($index < 0 || $index >= count($this->lines)) {
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


    /**
     * Get invoice total
     * @return InvoiceTotals Invoice totals
     */
    public function getTotals(): InvoiceTotals {
        $totals = new InvoiceTotals();
        $vatMap = [];

        // Set currency code
        $totals->currency = $this->getCurrency();

        // Process all invoice lines
        foreach ($this->getLines() as $line) {
            $lineNetAmount = $line->getNetAmount($this->getDecimals('line/netAmount')) ?? 0;

            // Update invoice totals
            $totals->netAmount += $lineNetAmount;

            // Create or get VAT breakdown instance
            $lineVatCategory = $line->getVatCategory();
            $lineVatRate = $line->getVatRate();
            $vatKey = "$lineVatCategory:$lineVatRate";
            if (!isset($vatMap[$vatKey])) {
                $vatMap[$vatKey] = new VatBreakdown();
                $vatMap[$vatKey]->category = $lineVatCategory;
                $vatMap[$vatKey]->rate = $lineVatRate;
            }

            // Update VAT breakdown
            $vatMap[$vatKey]->taxableAmount += $lineNetAmount;
        }

        // Calculate VAT amounts
        foreach ($vatMap as $item) {
            $item->taxAmount = $item->taxableAmount * ($item->rate / 100);
            $item->taxAmount = round($item->taxAmount, $this->getDecimals('invoice/taxAmount'));
            $totals->vatAmount += $item->taxAmount;
        }

        // Apply allowance and charge totals
        $totals->allowancesAmount = $this->getAllowancesChargesAmount(
            $this->allowances,
            $totals->netAmount,
            $this->getDecimals('invoice/allowancesAmount')
        );
        $totals->chargesAmount = $this->getAllowancesChargesAmount(
            $this->charges,
            $totals->netAmount,
            $this->getDecimals('invoice/chargesAmount')
        );

        // Calculate rest of properties
        $totals->taxExclusiveAmount = $totals->netAmount - $totals->allowancesAmount + $totals->chargesAmount;
        $totals->taxInclusiveAmount = $totals->taxExclusiveAmount + $totals->vatAmount;
        $totals->paidAmount = $this->getPaidAmount();
        $totals->roundingAmount = $this->getRoundingAmount();
        $totals->payableAmount = $totals->taxInclusiveAmount - $totals->paidAmount + $totals->roundingAmount;

        // Attach VAT breakdown
        $totals->vatBreakdown = array_values($vatMap);

        return $totals;
    }
}
