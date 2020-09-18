<?php
namespace Tests\Traits;

use Einvoicing\AllowanceOrCharge;
use Einvoicing\Invoice;
use OutOfBoundsException;
use PHPUnit\Framework\TestCase;

final class AllowanceOrChargeTraitTest extends TestCase {
    /** @var Invoice */
    private $invoice;

    /** @var AllowanceOrCharge */
    private $allowance;

    /** @var AllowanceOrCharge */
    private $charge;

    protected function setUp(): void {
        $this->invoice = new Invoice();
        $this->allowance = new AllowanceOrCharge();
        $this->charge = new AllowanceOrCharge();
    }

    public function testCanRemoveAllowances(): void {
        $this->invoice
            ->addAllowance(new AllowanceOrCharge)
            ->addAllowance($this->allowance)
            ->addAllowance(new AllowanceOrCharge)
            ->removeAllowance(2)
            ->removeAllowance(0);
        $this->assertSame($this->allowance, $this->invoice->getAllowances()[0]);
    }

    public function testCannotRemoveOutOfBoundsAllowances(): void {
        $this->expectException(OutOfBoundsException::class);
        $this->invoice->addAllowance($this->allowance)->removeAllowance(1);
    }

    public function testCanRemoveCharges(): void {
        $this->invoice
            ->addCharge(new AllowanceOrCharge)
            ->addCharge($this->charge)
            ->addCharge(new AllowanceOrCharge)
            ->removeCharge(2)
            ->removeCharge(0);
        $this->assertSame($this->charge, $this->invoice->getCharges()[0]);
    }

    public function testCannotRemoveOutOfBoundsCharges(): void {
        $this->expectException(OutOfBoundsException::class);
        $this->invoice->addCharge($this->charge)->removeCharge(1);
    }
}
