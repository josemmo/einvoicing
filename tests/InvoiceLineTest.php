<?php
namespace Tests;

use DomainException;
use Einvoicing\AllowanceOrCharge;
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

    public function testCannotSetNegativeVatRate(): void {
        $this->expectException(DomainException::class);
        $this->line->setVatRate(-10);
    }

    public function testCannotSetZeroBaseQuantity(): void {
        $this->expectException(DomainException::class);
        $this->line->setPrice(123, 0);
    }

    public function testCannotSetNegativeBaseQuantity(): void {
        $this->expectException(DomainException::class);
        $this->line->setBaseQuantity(-1);
    }

    public function testTotalAmountsAreCalculatedCorrectly(): void {
        $allowance = (new AllowanceOrCharge)->setAmount(20.2);
        $charge = (new AllowanceOrCharge)->setAmount(5)->markAsPercentage();
        $this->line
            ->setPrice(50, 2)
            ->setQuantity(10)
            ->setVatRate(21)
            ->addAllowance($allowance)
            ->addCharge($charge);
        $this->assertEquals(250,   $this->line->getNetAmountBeforeAllowancesCharges());
        $this->assertEquals(20.2,  $this->line->getAllowancesAmount());
        $this->assertEquals(12.5,  $this->line->getChargesAmount());
        $this->assertEquals(242.3, $this->line->getNetAmount());
    }
}
