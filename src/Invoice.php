<?php
namespace Einvoicing;

use DateTime;
use Einvoicing\Models\InvoiceTotals;
use Einvoicing\Payments\Payment;
use Einvoicing\Presets\AbstractPreset;
use Einvoicing\Traits\AllowanceOrChargeTrait;
use Einvoicing\Traits\AttachmentsTrait;
use Einvoicing\Traits\BuyerAccountingReferenceTrait;
use Einvoicing\Traits\InvoiceValidationTrait;
use Einvoicing\Traits\PeriodTrait;
use Einvoicing\Traits\PrecedingInvoiceReferencesTrait;
use InvalidArgumentException;
use OutOfBoundsException;
use function array_splice;
use function count;
use function is_subclass_of;
use function round;

/** @phan-suppress PhanUnreferencedPublicClassConstant */
class Invoice {
    const DEFAULT_DECIMALS = 8;

    /**
     * Request for payment
     *
     * Document/message issued by a creditor to a debtor to request payment of one or more invoices past due.
     */
    const TYPE_REQUEST_FOR_PAYMENT = 71;

    /**
     * Debit note related to goods or services
     *
     * Debit information related to a transaction for goods or services to the relevant party.
     */
    const TYPE_DEBIT_NOTE_RELATED_TO_GOODS_OR_SERVICES = 80;

    /**
     * Metered services invoice
     *
     * Document/message claiming payment for the supply of metered services (e.g., gas, electricity, etc.) supplied to
     * a fixed meter whose consumption is measured over a period of time.
     */
    const TYPE_METERED_SERVICES_INVOICE = 82;

    /**
     * Debit note related to financial adjustments
     *
     * Document/message for providing debit information related to financial adjustments to the relevant party.
     */
    const TYPE_DEBIT_NOTE_RELATED_TO_FINANCIAL_ADJUSTMENTS = 84;

    /**
     * Tax notification
     *
     * Used to specify that the message is a tax notification.
     */
    const TYPE_TAX_NOTIFICATION = 102;

    /**
     * Final payment request based on completion of work
     *
     * The final payment request of a series of payment requests submitted upon completion of all the work.
     */
    const TYPE_FINAL_PAYMENT_REQUEST_BASED_ON_COMPLETION_OF_WORK = 218;

    /**
     * Payment request for completed units
     *
     * A request for payment for completed units.
     */
    const TYPE_PAYMENT_REQUEST_FOR_COMPLETED_UNITS = 219;

    /**
     * Commercial invoice which includes a packing list
     *
     * Commercial transaction (invoice) will include a packing list.
     */
    const TYPE_COMMERCIAL_INVOICE_WHICH_INCLUDES_A_PACKING_LIST = 331;

    /**
     * Commercial invoice
     *
     * Document/message claiming payment for goods or services supplied under conditions agreed between seller and
     * buyer.
     */
    const TYPE_COMMERCIAL_INVOICE = 380;

    /**
     * Commission note
     *
     * Document/message in which a seller specifies the amount of commission, the percentage of the invoice amount, or
     * some other basis for the calculation of the commission to which a sales agent is entitled.
     */
    const TYPE_COMMISSION_NOTE = 382;

    /**
     * Debit note
     *
     * Document/message for providing debit information to the relevant party.
     */
    const TYPE_DEBIT_NOTE = 383;

    /**
     * Prepayment invoice
     *
     * An invoice to pay amounts for goods and services in advance; these amounts will be subtracted from the final
     * invoice.
     */
    const TYPE_PREPAYMENT_INVOICE = 386;

    /**
     * Tax invoice
     *
     * An invoice for tax purposes.
     */
    const TYPE_TAX_INVOICE = 388;

    /**
     * Factored invoice
     *
     * Invoice assigned to a third party for collection.
     */
    const TYPE_FACTORED_INVOICE = 393;

    /**
     * Consignment invoice
     *
     * Commercial invoice that covers a transaction other than one involving a sale.
     */
    const TYPE_CONSIGNMENT_INVOICE = 395;

    /**
     * Forwarder's invoice discrepancy report
     *
     * Document/message reporting invoice discrepancies identified by the forwarder.
     */
    const TYPE_FORWARDERS_INVOICE_DISCREPANCY_REPORT = 553;

    /**
     * Insurer's invoice
     *
     * Document/message issued by an insurer specifying the cost of an insurance which has been effected and claiming
     * payment therefore.
     */
    const TYPE_INSURERS_INVOICE = 575;

    /**
     * Forwarder's invoice
     *
     * Invoice issued by a freight forwarder specifying services rendered and costs incurred and claiming payment
     * therefore.
     */
    const TYPE_FORWARDERS_INVOICE = 623;

    /**
     * Freight invoice
     *
     * Document/message issued by a transport operation specifying freight costs and charges incurred for a transport
     * operation and stating conditions of payment.
     */
    const TYPE_FREIGHT_INVOICE = 780;

    /**
     * Claim notification
     *
     * Document notifying a claim.
     */
    const TYPE_CLAIM_NOTIFICATION = 817;

    /**
     * Consular invoice
     *
     * Document/message to be prepared by an exporter in his country and presented to a diplomatic representation of the
     * importing country for endorsement and subsequently to be presented by the importer in connection with the import
     * of the goods described therein.
     */
    const TYPE_CONSULAR_INVOICE = 870;

    /**
     * Partial construction invoice
     *
     * Partial invoice in the context of a specific construction project.
     */
    const TYPE_PARTIAL_CONSTRUCTION_INVOICE = 875;

    /**
     * Partial final construction invoice
     *
     * Invoice concluding all previous partial construction invoices of a completed partial rendered service in the
     * context of a specific construction project.
     */
    const TYPE_PARTIAL_FINAL_CONSTRUCTION_INVOICE = 876;

    /**
     * Final construction invoice
     *
     * Invoice concluding all previous partial invoices and partial final construction invoices in the context of a
     * specific construction project.
     */
    const TYPE_FINAL_CONSTRUCTION_INVOICE = 877;

    /**
     * Credit note related to goods or services
     * 
     * Document message used to provide credit information related to a transaction for goods or services to the
     * relevant party.
     */
    const TYPE_CREDIT_NOTE_RELATED_TO_GOODS_OR_SERVICES = 81;

    /**
     * Credit note related to financial adjustments
     * 
     * Document message for providing credit information related to financial adjustments to the relevant party,
     * e.g., bonuses.
     */
    const TYPE_CREDIT_NOTE_RELATED_TO_FINANCIAL_ADJUSTMENTS = 83;

    /**
     * Credit note
     * 
     * Document/message for providing credit information to the relevant party.
     */
    const TYPE_CREDIT_NOTE = 381;

    /**
     * Factored credit note
     * 
     * Credit note related to assigned invoice(s).
     */
    const TYPE_FACTORED_CREDIT_NOTE = 396;

    /**
     * Forwarder's credit note
     * 
     * Document/message for providing credit information to the relevant party.
     */
    const TYPE_FORWARDERS_CREDIT_NOTE = 532;

    protected $preset = null;
    protected $roundingMatrix = null;
    protected $specification = null;
    protected $businessProcess = null;
    protected $number = null;
    protected $type = self::TYPE_COMMERCIAL_INVOICE;
    protected $currency = "EUR"; // TODO: add constants
    protected $vatCurrency = null;
    protected $issueDate = null;
    protected $dueDate = null;
    protected $taxPointDate = null;
    protected $notes = [];
    protected $buyerReference = null;
    protected $purchaseOrderReference = null;
    protected $salesOrderReference = null;
    protected $tenderOrLotReference = null;
    protected $contractReference = null;
    protected $projectReference = null;
    protected $paidAmount = 0;
    protected $roundingAmount = 0;
    protected $customVatAmount = null;
    protected $seller = null;
    protected $buyer = null;
    protected $payee = null;
    protected $delivery = null;
    protected $payment = null;
    protected $lines = [];

    use AllowanceOrChargeTrait;
    use AttachmentsTrait;
    use BuyerAccountingReferenceTrait;
    use PeriodTrait;
    use InvoiceValidationTrait;
    use PrecedingInvoiceReferencesTrait;

    /**
     * Invoice constructor
     * @param string|null $preset Preset classname or NULL for blank invoice
     * @throws InvalidArgumentException if not a valid preset
     */
    public function __construct(?string $preset=null) {
        if ($preset === null) return;

        // Validate preset classname
        if (!is_subclass_of($preset, AbstractPreset::class)) {
            throw new InvalidArgumentException("$preset is not a valid invoice preset");
        }
        /** @var AbstractPreset */
        $this->preset = new $preset();

        // Initialize instance from preset
        $this->setSpecification($this->preset->getSpecification());
        $this->preset->setupInvoice($this);
    }


    /**
     * Get number of decimal places for a given field
     * @param  string $field Field name
     * @return int           Number of decimal places
     */
    public function getDecimals(string $field): int {
        return $this->roundingMatrix[$field] ?? $this->roundingMatrix[''] ?? self::DEFAULT_DECIMALS;
    }


    /**
     * Round value
     * @param  float  $value Value to round
     * @param  string $field Field name
     * @return float         Rounded value
     */
    public function round(float $value, string $field): float {
        $rounded = round($value, $this->getDecimals($field));
        if ($rounded == 0) {
            $rounded += 0; // To fix negative zero
        }
        return $rounded;
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
     * Get business process type
     * @return string|null Business process type
     */
    public function getBusinessProcess(): ?string {
        return $this->businessProcess;
    }


    /**
     * Set business process type
     * @param  string|null $businessProcess Business process type
     * @return self                         Invoice instance
     */
    public function setBusinessProcess(?string $businessProcess): self {
        $this->businessProcess = $businessProcess;
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
     * Get VAT accounting currency code
     * @return string|null VAT accounting currency code or NULL if same as document's
     */
    public function getVatCurrency(): ?string {
        return $this->vatCurrency;
    }


    /**
     * Set VAT accounting currency code
     * @param  string|null $currencyCode VAT accounting currency code or NULL if same as document's
     * @return self                      Invoice instance
     */
    public function setVatCurrency(?string $currencyCode): self {
        $this->vatCurrency = $currencyCode;
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
     * Get tax point date
     * @return DateTime|null Tax point date
     */
    public function getTaxPointDate(): ?DateTime {
        return $this->taxPointDate;
    }


    /**
     * Set tax point date
     * @param  DateTime|null $taxPointDate Tax point date
     * @return self                        Invoice instance
     */
    public function setTaxPointDate(?DateTime $taxPointDate): self {
        $this->taxPointDate = $taxPointDate;
        return $this;
    }


    /**
     * Get invoice notes
     * @return string[] Invoice notes
     */
    public function getNotes(): array {
        return $this->notes;
    }


    /**
     * Add invoice note
     * @param  string $note Invoice note
     * @return self         Invoice instance
     */
    public function addNote(string $note): self {
        $this->notes[] = $note;
        return $this;
    }


    /**
     * Remove invoice note
     * @param  int  $index Invoice note index
     * @return self        Invoice instance
     * @throws OutOfBoundsException if invoice note index is out of bounds
     */
    public function removeNote(int $index): self {
        if ($index < 0 || $index >= count($this->notes)) {
            throw new OutOfBoundsException('Could not find invoice note by index');
        }
        array_splice($this->notes, $index, 1);
        return $this;
    }


    /**
     * Clear all invoice notes
     * @return self Invoice instance
     */
    public function clearNotes(): self {
        $this->notes = [];
        return $this;
    }


    /**
     * Get invoice note
     * @return string|null Invoice note
     * @deprecated 0.2.1
     * @see Invoice::getNotes()
     */
    public function getNote(): ?string {
        return $this->notes[0] ?? null;
    }


    /**
     * Set invoice note
     * @param  string|null $note Invoice note
     * @return self              Invoice instance
     * @deprecated 0.2.1
     * @see Invoice::addNote()
     */
    public function setNote(?string $note): self {
        // @phan-suppress-next-line PhanPartialTypeMismatchProperty
        $this->notes = ($note === null) ? [] : [$note];
        return $this;
    }


    /**
     * Get buyer reference
     * @return string|null Buyer reference
     */
    public function getBuyerReference(): ?string {
        return $this->buyerReference;
    }


    /**
     * Set buyer reference
     * @param  string|null $buyerReference Buyer reference
     * @return self                        Invoice instance
     */
    public function setBuyerReference(?string $buyerReference): self {
        $this->buyerReference = $buyerReference;
        return $this;
    }


    /**
     * Get purchase order reference
     * @return string|null Purchase order reference
     */
    public function getPurchaseOrderReference(): ?string {
        return $this->purchaseOrderReference;
    }


    /**
     * Set purchase order reference
     * @param  string|null $purchaseOrderReference Purchase order reference
     * @return self                                Invoice instance
     */
    public function setPurchaseOrderReference(?string $purchaseOrderReference): self {
        $this->purchaseOrderReference = $purchaseOrderReference;
        return $this;
    }


    /**
     * Get sales order reference
     * @return string|null Sales order reference
     */
    public function getSalesOrderReference(): ?string {
        return $this->salesOrderReference;
    }


    /**
     * Set sales order reference
     * @param  string|null $salesOrderReference Sales order reference
     * @return self                             Invoice instance
     */
    public function setSalesOrderReference(?string $salesOrderReference): self {
        $this->salesOrderReference = $salesOrderReference;
        return $this;
    }


    /**
     * Get tender or lot reference
     * @return string|null Tender or lot reference
     */
    public function getTenderOrLotReference(): ?string {
        return $this->tenderOrLotReference;
    }


    /**
     * Set tender or lot reference
     * @param  string|null $tenderOrLotReference Tender or lot reference
     * @return self                              Invoice instance
     */
    public function setTenderOrLotReference(?string $tenderOrLotReference): self {
        $this->tenderOrLotReference = $tenderOrLotReference;
        return $this;
    }


    /**
     * Get contract reference
     * @return string|null Contract reference
     */
    public function getContractReference(): ?string {
        return $this->contractReference;
    }


    /**
     * Set contract reference
     * @param  string|null $contractReference Contract reference
     * @return self                           Invoice instance
     */
    public function setContractReference(?string $contractReference): self {
        $this->contractReference = $contractReference;
        return $this;
    }


    /**
     * Get project reference
     * @return string|null Project reference
     */
    public function getProjectReference(): ?string {
        return $this->projectReference;
    }


    /**
     * Set project reference
     * @param  string|null $contractReference Project reference
     * @return self                           Invoice instance
     */
    public function setProjectReference(?string $projectReference): self {
        $this->projectReference = $projectReference;
        return $this;
    }


    /**
     * Get invoice prepaid amount
     * @return float Invoice prepaid amount
     */
    public function getPaidAmount(): float {
        return $this->paidAmount;
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
     * @return float Invoice rounding amount
     */
    public function getRoundingAmount(): float {
        return $this->roundingAmount;
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
     * Get total VAT amount in VAT accounting currency
     * @return float|null Total amount in accounting currency
     */
    public function getCustomVatAmount(): ?float {
        return $this->customVatAmount;
    }


    /**
     * Set total VAT amount in VAT accounting currency
     * @param  float|null  $customVatAmount Total amount in accounting currency
     * @return self                         Invoice instance
     */
    public function setCustomVatAmount(?float $customVatAmount): self {
        $this->customVatAmount = $customVatAmount;
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
     * Get delivery information
     * @return Delivery|null Delivery instance
     */
    public function getDelivery(): ?Delivery {
        return $this->delivery;
    }


    /**
     * Set delivery information
     * @param  Delivery|null $delivery Delivery instance
     * @return self                    Invoice instance
     */
    public function setDelivery(?Delivery $delivery): self {
        $this->delivery = $delivery;
        return $this;
    }


    /**
     * Get payment information
     * @return Payment|null Payment instance
     */
    public function getPayment(): ?Payment {
        return $this->payment;
    }


    /**
     * Set payment information
     * @param  Payment|null $payment Payment instance
     * @return self                  Invoice instance
     */
    public function setPayment(?Payment $payment): self {
        $this->payment = $payment;
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
