<?php
namespace Tests;

use Einvoicing\AllowanceOrCharge;
use PHPUnit\Framework\TestCase;

final class AllowanceOrChargeTest extends TestCase {
    public function testCanGetEffectiveAmount(): void {
        $fixed = (new AllowanceOrCharge)->setAmount(15);
        $this->assertEquals(15, $fixed->getEffectiveAmount(200));

        $percentage = (new AllowanceOrCharge)->setAmount(10)->markAsPercentage();
        $this->assertEquals(20, $percentage->getEffectiveAmount(200));
    }

    public function testCanUseOwnBaseAmount(): void {
        $percentage = (new AllowanceOrCharge)->setAmount(10)->markAsPercentage()->setBaseAmount(50);
        $this->assertEquals(50, $percentage->getBaseAmount());
        $this->assertEquals(5, $percentage->getEffectiveAmount(200));

        $fixed = (new AllowanceOrCharge)->setAmount(15)->setBaseAmount(50);
        $this->assertEquals(15, $fixed->getEffectiveAmount(200));

        $percentage->setBaseAmount(null);
        $this->assertNull($percentage->getBaseAmount());
        $this->assertEquals(20, $percentage->getEffectiveAmount(200));
    }
}
