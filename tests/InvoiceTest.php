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

    public function testCanReadAndWriteNotes(): void {
        $note = "This is a test";
        $this->assertSame($note, $this->invoice->addNote($note)->getNotes()[0]);
        $this->invoice->removeNote(0);
        $this->assertEmpty($this->invoice->getNotes());
    }

    public function testCanRemoveNotes(): void {
        $this->invoice
            ->addNote('Note #1')
            ->addNote('Note #2')
            ->addNote('Note #3')
            ->removeNote(2)
            ->removeNote(0);
        $this->assertSame('Note #2', $this->invoice->getNotes()[0]);
    }

    public function testCannotRemoveOutOfBoundsNotes(): void {
        $this->expectException(OutOfBoundsException::class);
        $this->invoice->addNote('A sample note')->removeNote(1);
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

    public function testCanRoundNegativeZeroes(): void {
        $this->assertEquals('-1', (string) $this->invoice->round(-0.9999, 'invoice/netAmount'));
        $this->assertEquals('0',  (string) $this->invoice->round(-0.0001, 'invoice/netAmount'));
        $this->assertEquals('0',  (string) $this->invoice->round(-0,      'invoice/netAmount'));
        $this->assertEquals('0',  (string) $this->invoice->round(0,       'invoice/netAmount'));
        $this->assertEquals('0',  (string) $this->invoice->round(0.0001,  'invoice/netAmount'));
        $this->assertEquals('1',  (string) $this->invoice->round(0.9999,  'invoice/netAmount'));
    }

    public function testDecimalMatrixIsUsed(): void {
        $this->invoice->setRoundingMatrix([
            'invoice/paidAmount' => 4,
            'invoice/netAmount' => 5,
            '' => 8,
        ])->setPaidAmount(123.456789)
          ->setRoundingAmount(987.654321)
          ->addLine((new InvoiceLine)->setPrice(12.121212121))
          ->addLine((new InvoiceLine)->setPrice(34.343434343));

        $totals = $this->invoice->getTotals();
        $this->assertEquals(123.4568,   $totals->paidAmount);
        $this->assertEquals(987.654321, $totals->roundingAmount);
        $this->assertEquals(46.46465,   $totals->netAmount);
    }

    public function testTotalAmountsAreCalculatedCorrectly(): void {
        $allowance = (new AllowanceOrCharge)->setAmount(12.34);
        $charge = (new AllowanceOrCharge)->setFactorMultiplier(7.5)->setBaseAmount(300.5)->markAsPercentage();
        $firstLine = (new InvoiceLine)->setPrice(100)->setVatRate(10);
        $secondLine = (new InvoiceLine)->setPrice(200.5)->setVatRate(21);
        $this->invoice->clearLines()
            ->setRoundingMatrix(['' => 8]) // Increase decimal precision
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
