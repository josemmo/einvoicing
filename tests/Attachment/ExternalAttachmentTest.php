<?php
namespace Tests\Attachment;

use Einvoicing\Attachment\ExternalAttachment;
use PHPUnit\Framework\TestCase;

final class ExternalAttachmentTest extends TestCase {
    public function testItCanCreate(): void {
        $attachment = (new ExternalAttachment)
            ->setId('INV123')
            ->setDescription('Invoice INV123')
            ->setDocumentTypeCode('130')
            ->setUri('https://google.com');

        $this->assertSame('INV123', $attachment->getId());
        $this->assertSame('Invoice INV123', $attachment->getDescription());
        $this->assertSame('130', $attachment->getDocumentTypeCode());
        $this->assertSame('https://google.com', $attachment->getUri());
    }
}
