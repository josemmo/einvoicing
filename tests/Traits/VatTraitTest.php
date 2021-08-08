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

    public function testCanReadAndWriteExemptions(): void {
        $category = "E";
        $reason = "Supply of transport services for sick or injured persons";
        $reasonCode = "VATEX-EU-132-1P";
        $this->line->setVatCategory($category);
        $this->assertEquals($category, $this->line->getVatCategory());
        $this->line->setVatExemptionReason($reason);
        $this->assertEquals($reason, $this->line->getVatExemptionReason());
        $this->line->setVatExemptionReasonCode($reasonCode);
        $this->assertEquals($reasonCode, $this->line->getVatExemptionReasonCode());
    }
}
