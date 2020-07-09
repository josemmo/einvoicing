<?php
namespace Tests\Invoice;

use Einvoicing\Invoice\PeppolInvoice;
use Einvoicing\InvoiceLine\InvoiceLine;
use PHPUnit\Framework\TestCase;

final class PeppolInvoiceTest extends TestCase {
    /** @var PeppolInvoice */
    private $invoice;

    protected function setUp(): void {
        $this->invoice = new PeppolInvoice();
    }

    public function testCanReadAndWriteLines(): void {
        $line = new InvoiceLine();
        $this->assertSame($line, $this->invoice->addLine($line)->getLines()[0]);
        $this->invoice->removeLine(0);
        $this->assertEmpty($this->invoice->getLines());
    }

    public function testCanRemoveLines(): void {
        $line = new InvoiceLine();
        $this->invoice
            ->addLine(new InvoiceLine())
            ->addLine($line)
            ->addLine(new InvoiceLine())
            ->removeLine(2)
            ->removeLine(0);
        $this->assertSame($line, $this->invoice->getLines()[0]);
    }

    public function testCannotRemoveOutOfBoundsLines(): void {
        $this->expectException(\OutOfBoundsException::class);
        $this->invoice->addLine(new InvoiceLine())->removeLine(1);
    }
}
