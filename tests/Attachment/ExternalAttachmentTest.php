<?php

namespace Tests\Attachment;

use Einvoicing\Attachment\ExternalAttachment;
use PHPUnit\Framework\TestCase;

class ExternalAttachmentTest extends TestCase
{
    public function testItCanCreate(): void
    {
        $attachment = (new ExternalAttachment())
            ->setId($id = 'INV123')
            ->setDescription($description = 'Invoice INV123')
            ->setDocumentTypeCode($documentTypeCode = '130')
            ->setUri($uri = 'https://google.com')
        ;

        $this->assertSame($id, $attachment->getId());
        $this->assertSame($description, $attachment->getDescription());
        $this->assertSame($documentTypeCode, $attachment->getDocumentTypeCode());
        $this->assertSame($uri, $attachment->getUri());
    }
}
