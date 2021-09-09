<?php

namespace Tests\Attachment;

use Einvoicing\Attachment\EmbeddedAttachment;
use PHPUnit\Framework\TestCase;

class EmbeddedAttachmentTest extends TestCase
{
    public function testItCanCreate(): void
    {
        $attachment = (new EmbeddedAttachment())
            ->setId($id = 'INV123')
            ->setDescription($description = 'Invoice INV123')
            ->setDocumentTypeCode($documentTypeCode = '130')
            ->setFilename($filename = 'INV123.pdf')
            ->setMimeType($mimeType = 'application/pdf')
            ->setContent($content = base64_encode('pdf content'))
        ;

        $this->assertSame($id, $attachment->getId());
        $this->assertSame($description, $attachment->getDescription());
        $this->assertSame($documentTypeCode, $attachment->getDocumentTypeCode());
        $this->assertSame($filename, $attachment->getFilename());
        $this->assertSame($mimeType, $attachment->getMimeType());
        $this->assertSame($content, $attachment->getContent());
    }
}
