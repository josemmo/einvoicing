<?php
use Einvoicing\Party;
use PHPUnit\Framework\TestCase;

final class PartyTest extends TestCase {
    /** @var Party */
    private $party;

    protected function setUp(): void {
        $this->party = new Party();
    }

    public function testCanReadAndWriteAddress(): void {
        $this->party->setAddress(['a', 'b']);
        $this->assertEquals('a:b', implode(':', $this->party->getAddress()));
    }

    public function testCannotHaveAnAddressWithMoreThanThreeLines(): void {
        $this->expectException(InvalidArgumentException::class);
        $this->party->setAddress(['a', 'b', 'c', 'd']);
    }
}
