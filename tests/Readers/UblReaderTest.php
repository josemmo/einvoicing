<?php
namespace Tests\Readers;

use Einvoicing\Invoice;
use Einvoicing\Readers\UblReader;
use PHPUnit\Framework\TestCase;
use InvalidArgumentException;
use function file_get_contents;

final class UblReaderTest extends TestCase {
    const INVOICE_PATH = __DIR__ . "/peppol-invoice-example.xml";
    const CREDIT_NOTE_PATH = __DIR__ . "/peppol-credit-note-example.xml";
    const ALLOWANCE_BASE_AMOUNT_PATH = __DIR__ . "/../Integration/peppol-allowance-base-amount.xml";

    /** @var UblReader */
    private $reader;

    protected function setUp(): void {
        $this->reader = new UblReader();
    }

    public function testCanReadCreditNote(): void {
        $creditNote = $this->reader->import(file_get_contents(self::CREDIT_NOTE_PATH));
        $creditNote->validate();

        $this->assertSame(Invoice::TYPE_CREDIT_NOTE_RELATED_TO_GOODS_OR_SERVICES, $creditNote->getType());

        $lines = $creditNote->getLines();
        $this->assertEquals('1000', $lines[0]->getId());
        $this->assertEquals('2000', $lines[1]->getId());

        $totals = $creditNote->getTotals();
        $this->assertEquals(1300, $totals->netAmount);
        $this->assertEquals(1300, $totals->taxExclusiveAmount);
        $this->assertEquals(325, $totals->vatAmount);
        $this->assertEquals(0, $totals->allowancesAmount);
        $this->assertEquals(0, $totals->chargesAmount);
        $this->assertEquals(1625, $totals->payableAmount);
        $this->assertEquals('S', $totals->vatBreakdown[0]->category);
        $this->assertEquals(25, $totals->vatBreakdown[0]->rate);
    }

    public function testCanReadAllowanceBaseAmount(): void {
        $invoice = $this->reader->import(file_get_contents(self::ALLOWANCE_BASE_AMOUNT_PATH));
        $invoice->validate();

        // Document level: 10% of a base amount of 500, not of the net amount of 1250
        $allowance = $invoice->getAllowances()[0];
        $this->assertTrue($allowance->isPercentage());
        $this->assertEquals(10, $allowance->getAmount());
        $this->assertEquals(500, $allowance->getBaseAmount());
        $this->assertNull($invoice->getCharges()[0]->getBaseAmount());

        // Line level: 5% of a base amount of 1000, not of the line net amount of 2800
        $line = $invoice->getLines()[0];
        $this->assertEquals(1000, $line->getAllowances()[0]->getBaseAmount());
        $this->assertEquals(50, $line->getAllowancesAmount());
        $this->assertEquals(2750, $line->getNetAmount());

        $totals = $invoice->getTotals();
        $this->assertEquals(1250, $totals->netAmount);
        $this->assertEquals(50, $totals->allowancesAmount);
        $this->assertEquals(25, $totals->chargesAmount);
        $this->assertEquals(1225, $totals->taxExclusiveAmount);
        $this->assertEquals(306.25, $totals->vatAmount);
        $this->assertEquals(1531.25, $totals->payableAmount);
    }

    public function testCanReadInvoice(): void {
        $invoice = $this->reader->import(file_get_contents(self::INVOICE_PATH));
        $invoice->validate();

        $this->assertSame(Invoice::TYPE_COMMERCIAL_INVOICE, $invoice->getType());

        $lines = $invoice->getLines();
        $this->assertEquals('1', $lines[0]->getId());
        $this->assertEquals('2', $lines[1]->getId());

        $totals = $invoice->getTotals();
        $this->assertEquals(1300, $totals->netAmount);
        $this->assertEquals(1325, $totals->taxExclusiveAmount);
        $this->assertEquals(331.25, $totals->vatAmount);
        $this->assertEquals(0, $totals->allowancesAmount);
        $this->assertEquals(25, $totals->chargesAmount);
        $this->assertEquals(1656.25, $totals->payableAmount);
        $this->assertEquals('S', $totals->vatBreakdown[0]->category);
        $this->assertEquals(25, $totals->vatBreakdown[0]->rate);
        $this->assertEquals('INV-123', $invoice->getPrecedingInvoiceReferences()[0]->getValue());
        $this->assertEquals('This is a sample string', $invoice->getAttachments()[0]->getContents());
    }

    public function testCannotReadInvoiceFromInvalidXml(): void {
        $this->expectException(InvalidArgumentException::class);
        $this->reader->import(
            '<Invoice xmlns:cac="urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2"
                xmlns:cbc="urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2"
                xmlns="urn:oasis:names:specification:ubl:schema:xsd:Invoice-2">
                <cbc:CustomizationID>0.0.1</cbc:CustomizationID>
                <cbc:ProfileID>Not-a-EN-16931-Invoice</cbc:ProfileID>
                <cac:TaxTotal>
                    <cbc:TaxAmount currencyID="EUR">0.00</cbc:TaxAmount>
                    <cac:TaxSubtotal>
                        <cbc:TaxableAmount currencyID="EUR">100.00</cbc:TaxableAmount>
                        <cbc:TaxAmount currencyID="EUR">0.00</cbc:TaxAmount>
                        <cbc:Percent>0.00</cbc:Percent>
                        <cac:TaxCategory>
                            <cbc:TaxExemptionReasonCode>No tax</cbc:TaxExemptionReasonCode>
                        </cac:TaxCategory>
                    </cac:TaxSubtotal>
                </cac:TaxTotal>
             </Invoice>'
        );
    }
}
