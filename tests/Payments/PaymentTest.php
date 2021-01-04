<?php
namespace Tests\Payments;

use Einvoicing\Payments\Payment;
use Einvoicing\Payments\Transfer;
use OutOfBoundsException;
use PHPUnit\Framework\TestCase;

final class PaymentTest extends TestCase {
    /** @var Payment */
    private $payment;

    /** @var Transfer */
    private $transfer;

    protected function setUp(): void {
        $this->payment = new Payment();
        $this->transfer = new Transfer();
    }

    public function testCanRemoveTransfers(): void {
        $this->payment
            ->addTransfer(new Transfer())
            ->addTransfer($this->transfer)
            ->addTransfer(new Transfer())
            ->removeTransfer(2)
            ->removeTransfer(0);
        $this->assertSame($this->transfer, $this->payment->getTransfers()[0]);
    }

    public function testCannotRemoveOutOfBoundsTransfer(): void {
        $this->expectException(OutOfBoundsException::class);
        $this->payment->addTransfer($this->transfer)->removeTransfer(1);
    }
}
