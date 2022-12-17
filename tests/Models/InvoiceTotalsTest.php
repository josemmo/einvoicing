<?php
namespace Tests\Models;

use Einvoicing\AllowanceOrCharge;
use Einvoicing\Invoice;
use Einvoicing\InvoiceLine;
use Einvoicing\Models\InvoiceTotals;
use PHPUnit\Framework\TestCase;

final class InvoiceTotalsTest extends TestCase {
    /** @var Invoice */
    private $invoice;

    protected function setUp(): void {
        $this->invoice = (new Invoice)->setRoundingMatrix(['' => 3]);
    }

    public function testClassConstructors(): void {
        $line = (new InvoiceLine())
            ->setName('Test Line')
            ->setPrice(100);
        $this->invoice->addLine($line);

        $totalsA = InvoiceTotals::fromInvoice($this->invoice);
        $totalsB = $this->invoice->getTotals();
        $this->assertInstanceOf(InvoiceTotals::class, $totalsA);
        $this->assertInstanceOf(InvoiceTotals::class, $totalsB);
        $this->assertEquals(100, $totalsA->payableAmount);
        $this->assertEquals(100, $totalsB->payableAmount);
    }

    public function testRoundingOfTotals(): void {
        $line = (new InvoiceLine())
            ->setPrice(0.25)
            ->setQuantity(26935.78)
            ->setVatRate(19);
        $this->invoice->addLine($line);

        $totals = $this->invoice->getTotals();
        $this->assertEquals(6733.945, $totals->taxExclusiveAmount);
        $this->assertEquals(1279.45,  $totals->vatAmount);
        $this->assertEquals(8013.395, $totals->taxInclusiveAmount);
    }

    public function testVatExemptionReasons(): void {
        $firstLine = (new InvoiceLine())
            ->setName('Line #1')
            ->setVatCategory('E')
            ->setVatExemptionReasonCode('VATEX-EU-O')
            ->setVatExemptionReason('Not subject to VAT');
        $secondLine = (new InvoiceLine())
            ->setName('Line #2')
            ->setVatCategory('E')
            ->setVatRate(0)
            ->setVatExemptionReasonCode('VATEX-EU-132-1P');
        $thirdLine = (clone $firstLine)
            ->setName('Line #3');
        $allowance = (new AllowanceOrCharge())
            ->setReason('Allowance')
            ->setAmount(100)
            ->setVatCategory('E')
            ->setVatExemptionReason('Another reason expressed as text');

        $this->invoice
            ->addLine($firstLine)
            ->addLine($secondLine)
            ->addLine($thirdLine)
            ->addAllowance($allowance);
        $totals = $this->invoice->getTotals();

        $this->assertEquals('VATEX-EU-O', $totals->vatBreakdown[0]->exemptionReasonCode);
        $this->assertEquals('Another reason expressed as text', $totals->vatBreakdown[0]->exemptionReason);
        $this->assertEquals(null, $totals->vatBreakdown[1]->exemptionReason);
        $this->assertEquals('VATEX-EU-132-1P', $totals->vatBreakdown[1]->exemptionReasonCode);
    }
}
