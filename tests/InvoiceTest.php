<?php
namespace Tests;

use Einvoicing\AllowanceOrCharge;
use Einvoicing\Invoice;
use Einvoicing\InvoiceLine;
use Einvoicing\Presets\Peppol;
use InvalidArgumentException;
use OutOfBoundsException;
use PHPUnit\Framework\TestCase;

final class InvoiceTest extends TestCase {
    /** @var Invoice */
    private $invoice;

    /** @var InvoiceLine */
    private $line;

    protected function setUp(): void {
        $this->invoice = (new Invoice)->setRoundingMatrix(['' => 2]);
        $this->line = new InvoiceLine();
    }

    public function testCanCreateInvoiceFromPreset(): void {
        $invoice = new Invoice(Peppol::class);
        $this->assertEquals((new Peppol)->getSpecification(), $invoice->getSpecification());
    }

    public function testCannotCreateInvoiceFromInvalidPreset(): void {
        $this->expectException(InvalidArgumentException::class);
        new Invoice(self::class);
    }

    public function testCanReadAndWriteLines(): void {
        $this->assertSame($this->line, $this->invoice->addLine($this->line)->getLines()[0]);
        $this->invoice->removeLine(0);
        $this->assertEmpty($this->invoice->getLines());
    }

    public function testCanRemoveLines(): void {
        $this->invoice
            ->addLine(new InvoiceLine)
            ->addLine($this->line)
            ->addLine(new InvoiceLine)
            ->removeLine(2)
            ->removeLine(0);
        $this->assertSame($this->line, $this->invoice->getLines()[0]);
    }

    public function testCannotRemoveOutOfBoundsLines(): void {
        $this->expectException(OutOfBoundsException::class);
        $this->invoice->addLine(new InvoiceLine)->removeLine(1);
    }

    public function testDecimalMatrixIsUsed(): void {
        $this->invoice->setRoundingMatrix([
            "invoice/paidAmount" => 4,
            "line/netAmount" => 8,
            "" => 3
        ])->setPaidAmount(123.456789)
          ->setRoundingAmount(987.654321)
          ->addLine((new InvoiceLine)->setPrice(12.121212121))
          ->addLine((new InvoiceLine)->setPrice(34.343434343));

        $this->assertEquals(123.4568,    $this->invoice->getPaidAmount());
        $this->assertEquals(987.654,     $this->invoice->getRoundingAmount());
        $this->assertEquals(46.464646464, $this->invoice->getTotals()->netAmount);
    }

    public function testTotalAmountsAreCalculatedCorrectly(): void {
        $allowance = (new AllowanceOrCharge)->setAmount(12.34);
        $charge = (new AllowanceOrCharge)->setAmount(7.5)->markAsPercentage();
        $firstLine = (new InvoiceLine)->setPrice(100)->setVatRate(10);
        $secondLine = (new InvoiceLine)->setPrice(200.5)->setVatRate(21);
        $this->invoice->clearLines()
            ->setPaidAmount(10.2)
            ->addLine($firstLine)
            ->addLine($secondLine)
            ->addAllowance($allowance)
            ->addCharge($charge);

        $totals = $this->invoice->getTotals();
        $this->assertEquals(300.5,    $totals->netAmount);
        $this->assertEquals(12.34,    $totals->allowancesAmount);
        $this->assertEquals(22.5375,  $totals->chargesAmount);
        $this->assertEquals(52.105,   $totals->vatAmount);
        $this->assertEquals(310.6975, $totals->taxExclusiveAmount);
        $this->assertEquals(362.8025, $totals->taxInclusiveAmount);
        $this->assertEquals(10.2,     $totals->paidAmount);
        $this->assertEquals(0,        $totals->roundingAmount);
        $this->assertEquals(352.6025, $totals->payableAmount);
        $this->assertEquals(10,       $totals->vatBreakdown[0]->taxAmount);
        $this->assertEquals(42.105,   $totals->vatBreakdown[1]->taxAmount);
    }
}
