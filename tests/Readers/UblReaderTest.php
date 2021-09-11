<?php
namespace Tests\Readers;

use Einvoicing\Readers\UblReader;
use PHPUnit\Framework\TestCase;
use function file_get_contents;

final class UblReaderTest extends TestCase {
    const DOCUMENT_PATH = __DIR__ . "/peppol-example.xml";

    /** @var UblReader */
    private $reader;

    protected function setUp(): void {
        $this->reader = new UblReader();
    }

    public function testCanReadInvoice(): void {
        $invoice = $this->reader->import(file_get_contents(self::DOCUMENT_PATH));
        $invoice->validate();
        $totals = $invoice->getTotals();
        $this->assertEquals(1300, $totals->netAmount);
        $this->assertEquals(1325, $totals->taxExclusiveAmount);
        $this->assertEquals(331.25, $totals->vatAmount);
        $this->assertEquals(0, $totals->allowancesAmount);
        $this->assertEquals(25, $totals->chargesAmount);
        $this->assertEquals(1656.25, $totals->payableAmount);
        $this->assertEquals('S', $totals->vatBreakdown[0]->category);
        $this->assertEquals(25, $totals->vatBreakdown[0]->rate);
        $this->assertEquals('ABC-123.pdf', $invoice->getAttachments()[0]->getFilename());
    }
}
