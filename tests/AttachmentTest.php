<?php
namespace Tests;

use Einvoicing\Attachment;
use Einvoicing\Identifier;
use PHPUnit\Framework\TestCase;

final class AttachmentTest extends TestCase {
    public function testCanReadAndModifyFields(): void {
        $attachment = (new Attachment)
            ->setId(new Identifier('INV123'))
            ->setDescription('Invoice INV123')
            ->setExternalUrl('https://example.com')
            ->setFilename('INV123.txt')
            ->setMimeCode('text/plain')
            ->setContents('This is a sample text string');

        $this->assertSame('INV123', $attachment->getId()->getValue());
        $this->assertSame('Invoice INV123', $attachment->getDescription());
        $this->assertSame('https://example.com', $attachment->getExternalUrl());
        $this->assertSame('INV123.txt', $attachment->getFilename());
        $this->assertSame('text/plain', $attachment->getMimeCode());
        $this->assertSame('This is a sample text string', $attachment->getContents());
    }
}
