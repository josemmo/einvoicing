<?php
namespace Tests\Traits;

use Einvoicing\InvoiceLine;
use PHPUnit\Framework\TestCase;

final class VatTraitTest extends TestCase {
    /** @var InvoiceLine */
    private $line;

    protected function setUp(): void {
        $this->line = new InvoiceLine();
    }

    public function testCanReadAndWriteRate(): void {
        $this->line->setVatRate(10);
        $this->assertEquals(10, $this->line->getVatRate());
        $this->line->setVatRate(0);
        $this->assertEquals(0, $this->line->getVatRate());
    }
}
