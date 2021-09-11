<?php
namespace Tests\Attachment;

use Einvoicing\Attachment\EmbeddedAttachment;
use PHPUnit\Framework\TestCase;

final class EmbeddedAttachmentTest extends TestCase {
    public function testItCanCreate(): void {
        $attachmentContent = base64_encode('pdf content');
        $attachment = (new EmbeddedAttachment)
            ->setId('INV123')
            ->setDescription('Invoice INV123')
            ->setDocumentTypeCode('130')
            ->setFilename('INV123.pdf')
            ->setMimeCode('application/pdf')
            ->setContent($attachmentContent);

        $this->assertSame('INV123', $attachment->getId());
        $this->assertSame('Invoice INV123', $attachment->getDescription());
        $this->assertSame('130', $attachment->getDocumentTypeCode());
        $this->assertSame('INV123.pdf', $attachment->getFilename());
        $this->assertSame('application/pdf', $attachment->getMimeCode());
        $this->assertSame($attachmentContent, $attachment->getContent());
    }
}
