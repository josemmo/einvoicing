<?php
use Einvoicing\InvoiceLine;
use PHPUnit\Framework\TestCase;

final class InvoiceLineTest extends TestCase {
    /** @var InvoiceLine */
    private $line;

    protected function setUp(): void {
        $this->line = new InvoiceLine();
    }

    public function testCanSetPriceWithCustomBaseQuantity(): void {
        $this->line->setBaseQuantity(5)->setPrice(123.45);
        $this->assertEquals(5, $this->line->getBaseQuantity());
        $this->line->setPrice(543.21, 2);
        $this->assertEquals(2, $this->line->getBaseQuantity());
    }
}
