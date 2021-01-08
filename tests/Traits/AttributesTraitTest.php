<?php
namespace Tests\Traits;

use Einvoicing\Attribute;
use Einvoicing\InvoiceLine;
use OutOfBoundsException;
use PHPUnit\Framework\TestCase;

final class AttributesTraitTest extends TestCase {
    /** @var InvoiceLine */
    private $line;

    /** @var Attribute */
    private $attribute;

    protected function setUp(): void {
        $this->line = new InvoiceLine();
        $this->attribute = new Attribute('TestName', 'A0123456789');
    }

    public function testCanRemoveAttributes(): void {
        $this->line
            ->addAttribute(new Attribute('Name A', '#0'))
            ->addAttribute($this->attribute)
            ->addAttribute(new Attribute('Name B', '#2'))
            ->removeAttribute(2)
            ->removeAttribute(0);
        $this->assertSame($this->attribute, $this->line->getAttributes()[0]);
    }

    public function testCannotRemoveOutOfBoundsAttribute(): void {
        $this->expectException(OutOfBoundsException::class);
        $this->line->addAttribute($this->attribute)->removeAttribute(1);
    }

    public function testCanHaveMultipleAttributesWithSameName(): void {
        $this->line
            ->addAttribute(new Attribute('Name', '123'))
            ->addAttribute(new Attribute('Name', '321'));
        $this->assertEquals('123', $this->line->getAttributes()[0]->getValue());
        $this->assertEquals('321', $this->line->getAttributes()[1]->getValue());
    }

    public function testCanGetValueFromFirstOccurence(): void {
        $this->line
            ->addAttribute(new Attribute('Name', '123'))
            ->addAttribute(new Attribute('Another Name', 'ABC'))
            ->addAttribute(new Attribute('Name', '321'));
        $this->assertEquals('123', $this->line->getAttribute('Name'));
        $this->assertEquals('ABC', $this->line->getAttribute('Another Name'));
        $this->assertNull($this->line->getAttribute('Unused Name'));
    }

    public function testCanSetValueToFirstOccurrence(): void {
        $this->line
            ->addAttribute(new Attribute('Name', '123'))
            ->addAttribute(new Attribute('Name', '321'))
            ->setAttribute('Name', 'New value')
            ->setAttribute('Unused Name', null)
            ->setAttribute('Other Name', 'Other value');
        $this->assertEquals('New value', $this->line->getAttributes()[0]->getValue());
        $this->assertEquals('321', $this->line->getAttributes()[1]->getValue());
        $this->assertEquals('Other value', $this->line->getAttributes()[2]->getValue());

        $this->line->setAttribute('Name', null);
        $this->assertEquals('321', $this->line->getAttributes()[0]->getValue());
    }
}
