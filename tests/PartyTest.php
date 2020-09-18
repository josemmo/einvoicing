<?php
namespace Tests;

use Einvoicing\Party;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use function implode;

final class PartyTest extends TestCase {
    /** @var Party */
    private $party;

    protected function setUp(): void {
        $this->party = new Party();
    }

    public function testCanReadAndWriteAddress(): void {
        $this->party->setAddress(['a', 'b']);
        $this->assertEquals('a:b', implode(':', $this->party->getAddress()));
        $this->party->setAddress(['x', 'y', 'z']);
        $this->assertEquals('x:y:z', implode(':', $this->party->getAddress()));
    }

    public function testCannotHaveAnAddressWithMoreThanThreeLines(): void {
        $this->expectException(InvalidArgumentException::class);
        $this->party->setAddress(['a', 'b', 'c', 'd']);
    }
}
