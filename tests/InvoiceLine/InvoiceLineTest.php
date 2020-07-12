<?php
namespace Tests\InvoiceLine;

use Einvoicing\InvoiceLine\InvoiceLine;
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

    public function testCannotSetNegativeVatRate(): void {
        $this->expectException(\DomainException::class);
        $this->line->setVatRate(-10);
    }

    public function testCannotSetZeroBaseQuantity(): void {
        $this->expectException(\DomainException::class);
        $this->line->setPrice(123, 0);
    }

    public function testCannotSetNegativeBaseQuantity(): void {
        $this->expectException(\DomainException::class);
        $this->line->setBaseQuantity(-1);
    }
}
