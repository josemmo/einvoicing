<?php
namespace Tests\Traits;

use Einvoicing\Exceptions\ValidationException;
use Einvoicing\Invoice;
use PHPUnit\Framework\TestCase;

final class InvoiceValidationTraitTest extends TestCase {
    /** @var Invoice */
    private $invoice;

    protected function setUp(): void {
        $this->invoice = new Invoice();
    }

    public function testThrowsExceptionOnInvalidInvoice(): void {
        $this->expectException(ValidationException::class);
        $this->invoice->validate();
    }
}
