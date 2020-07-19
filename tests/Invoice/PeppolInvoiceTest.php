<?php
namespace Tests\Invoice;

use Einvoicing\AllowanceCharge\Allowance;
use Einvoicing\AllowanceCharge\Charge;
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

    public function testTotalAmountsAreCalculatedCorrectly(): void {
        $allowance = (new Allowance)->setAmount(12.34);
        $charge = (new Charge)->setAmount(7.5)->markAsPercentage();
        $firstLine = (new InvoiceLine)->setPrice(100)->setVatRate(10);
        $secondLine = (new InvoiceLine)->setPrice(200.5)->setVatRate(21);
        $this->invoice->clearLines()
            ->setPaidAmount(10.20)
            ->addLine($firstLine)
            ->addLine($secondLine)
            ->addAllowance($allowance)
            ->addCharge($charge);

        $totals = $this->invoice->getTotals();
        $this->assertEquals(300.5,  $totals->netAmount);
        $this->assertEquals(12.34,  $totals->allowancesAmount);
        $this->assertEquals(22.54,  $totals->chargesAmount);
        $this->assertEquals(52.11,  $totals->vatAmount);
        $this->assertEquals(310.7,  $totals->taxExclusiveAmount);
        $this->assertEquals(362.81, $totals->taxInclusiveAmount);
        $this->assertEquals(10.20,  $totals->paidAmount);
        $this->assertEquals(0,      $totals->roundingAmount);
        $this->assertEquals(352.61, $totals->payableAmount);
        $this->assertEquals(10,     $totals->vatBreakdown[0]->taxAmount);
        $this->assertEquals(42.11,  $totals->vatBreakdown[1]->taxAmount);
    }
}
