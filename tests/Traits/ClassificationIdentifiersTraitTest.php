<?php
namespace Tests\Traits;

use Einvoicing\Identifier;
use Einvoicing\InvoiceLine;
use OutOfBoundsException;
use PHPUnit\Framework\TestCase;

final class ClassificationIdentifiersTraitTest extends TestCase {
    /** @var InvoiceLine */
    private $line;

    /** @var Identifier */
    private $identifier;

    protected function setUp(): void {
        $this->line = new InvoiceLine();
        $this->identifier = new Identifier('ABCDE');
    }

    public function testCanRemoveClassificationIdentifiers(): void {
        $this->line
            ->addClassificationIdentifier(new Identifier('#0'))
            ->addClassificationIdentifier($this->identifier)
            ->addClassificationIdentifier(new Identifier('#2'))
            ->removeClassificationIdentifier(2)
            ->removeClassificationIdentifier(0);
        $this->assertSame($this->identifier, $this->line->getClassificationIdentifiers()[0]);
    }

    public function testCannotRemoveOutOfBoundsClassificationIdentifier(): void {
        $this->expectException(OutOfBoundsException::class);
        $this->line
            ->addClassificationIdentifier($this->identifier)
            ->removeClassificationIdentifier(1);
    }
}
