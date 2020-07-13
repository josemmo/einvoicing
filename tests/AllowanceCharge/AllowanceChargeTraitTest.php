<?php
namespace Tests\AllowanceCharge;

use Einvoicing\AllowanceCharge\Allowance;
use Einvoicing\AllowanceCharge\Charge;
use Einvoicing\Invoice\PeppolInvoice;
use PHPUnit\Framework\TestCase;

final class AllowanceChargeTraitTest extends TestCase {
    /** @var PeppolInvoice */
    private $invoice;

    /** @var Allowance */
    private $allowance;

    /** @var Charge */
    private $charge;

    protected function setUp(): void {
        $this->invoice = new PeppolInvoice();
        $this->allowance = new Allowance();
        $this->charge = new Charge();
    }

    public function testCanRemoveAllowances(): void {
        $this->invoice
            ->addAllowance(new Allowance())
            ->addAllowance($this->allowance)
            ->addAllowance(new Allowance())
            ->removeAllowance(2)
            ->removeAllowance(0);
        $this->assertSame($this->allowance, $this->invoice->getAllowances()[0]);
    }

    public function testCannotRemoveOutOfBoundsAllowances(): void {
        $this->expectException(\OutOfBoundsException::class);
        $this->invoice->addAllowance($this->allowance)->removeAllowance(1);
    }

    public function testCanRemoveCharges(): void {
        $this->invoice
            ->addCharge(new Charge())
            ->addCharge($this->charge)
            ->addCharge(new Charge())
            ->removeCharge(2)
            ->removeCharge(0);
        $this->assertSame($this->charge, $this->invoice->getCharges()[0]);
    }

    public function testCannotRemoveOutOfBoundsCharges(): void {
        $this->expectException(\OutOfBoundsException::class);
        $this->invoice->addCharge($this->charge)->removeCharge(1);
    }
}
