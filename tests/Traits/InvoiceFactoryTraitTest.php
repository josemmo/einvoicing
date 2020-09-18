<?php
namespace Tests\Traits;

use Einvoicing\Invoice;
use Einvoicing\Presets\Peppol;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class InvoiceFactoryTraitTest extends TestCase {
    public function testCanCreateInvoiceFromPreset(): void {
        $invoice = Invoice::fromPreset(Peppol::class);
        $this->assertInstanceOf(Invoice::class, $invoice);
        $this->assertEquals((new Peppol)->getSpecification(), $invoice->getSpecification());
    }

    public function testCannotCreateInvoiceFromInvalidPreset(): void {
        $this->expectException(InvalidArgumentException::class);
        Invoice::fromPreset(self::class);
    }
}
