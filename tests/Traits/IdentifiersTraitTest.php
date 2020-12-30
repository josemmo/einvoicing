<?php
namespace Tests\Traits;

use Einvoicing\Identifier;
use Einvoicing\Party;
use OutOfBoundsException;
use PHPUnit\Framework\TestCase;

final class IdentifiersTraitTest extends TestCase {
    /** @var Party */
    private $party;

    /** @var Identifier */
    private $identifier;

    protected function setUp(): void {
        $this->party = new Party();
        $this->identifier = new Identifier('A0123456789');
    }

    public function testCanRemoveIdentifiers(): void {
        $this->party
            ->addIdentifier(new Identifier('#0'))
            ->addIdentifier($this->identifier)
            ->addIdentifier(new Identifier('#2'))
            ->removeIdentifier(2)
            ->removeIdentifier(0);
        $this->assertSame($this->identifier, $this->party->getIdentifiers()[0]);
    }

    public function testCannotRemoveOutOfBoundsIdentifier(): void {
        $this->expectException(OutOfBoundsException::class);
        $this->party->addIdentifier($this->identifier)->removeIdentifier(1);
    }
}
