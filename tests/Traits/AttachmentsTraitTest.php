<?php
namespace Tests\Traits;

use Einvoicing\Attachment;
use Einvoicing\Invoice;
use OutOfBoundsException;
use PHPUnit\Framework\TestCase;

final class AttachmentsTraitTest extends TestCase {
    /** @var Invoice */
    private $invoice;

    /** @var Attachment */
    private $attachment;

    protected function setUp(): void {
        $this->invoice = new Invoice();
        $this->attachment = new Attachment();
    }

    public function testCanRemoveAttachments(): void {
        $this->invoice
            ->addAttachment(new Attachment)
            ->addAttachment(new Attachment)
            ->addAttachment($this->attachment)
            ->addAttachment(new Attachment)
            ->removeAttachment(3)
            ->removeAttachment(1);
        $this->assertSame($this->attachment, $this->invoice->getAttachments()[1]);
    }

    public function testCannotRemoveOutOfBoundsAttachment(): void {
        $this->expectException(OutOfBoundsException::class);
        $this->invoice->addAttachment($this->attachment)->removeAttachment(1);
    }
}
