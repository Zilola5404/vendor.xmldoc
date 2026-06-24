<?php

declare(strict_types=1);

namespace Vendor\Xmldoc\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Vendor\Xmldoc\Crm\ProductAmountCalculator;

final class ProductAmountCalculatorTest extends TestCase
{
    public function testOneCModeMatchesBitrix24ForRegularTaxIncludedLine(): void
    {
        $row = [
            'QUANTITY' => 4,
            'PRICE' => 12595.86,
            'TAX_RATE' => 22,
            'TAX_INCLUDED' => 'Y',
        ];

        $oneC = ProductAmountCalculator::calculate1C($row);
        $b24 = ProductAmountCalculator::calculateBitrix24($row);

        $this->assertSame($b24['PRICE'], $oneC['PRICE']);
        $this->assertSame($b24['SUM_NET'], $oneC['SUM_NET']);
        $this->assertSame($b24['TAX_SUM'], $oneC['TAX_SUM']);
        $this->assertSame($b24['SUM_GROSS'], $oneC['SUM_GROSS']);
    }

    public function testBitrix24ModeUsesRoundedExclusiveTimesQuantity(): void
    {
        $amounts = ProductAmountCalculator::calculateBitrix24([
            'QUANTITY' => 4,
            'PRICE' => 4661.16,
            'PRICE_EXCLUSIVE' => 3820.62295082,
            'TAX_RATE' => 22,
            'TAX_INCLUDED' => 'Y',
        ]);

        $this->assertSame(3820.62, $amounts['PRICE']);
        $this->assertSame(15282.48, $amounts['SUM_NET']);
        $this->assertSame(3362.16, $amounts['TAX_SUM']);
        $this->assertSame(18644.64, $amounts['SUM_GROSS']);
    }

    public function testDeal5938Row87481UsesEdoBumpInOneCMode(): void
    {
        $row = [
            'QUANTITY' => 2,
            'PRICE' => 326.42,
            'PRICE_EXCLUSIVE' => 267.55737705,
            'DISCOUNT_RATE' => 7,
            'TAX_RATE' => 22,
            'TAX_INCLUDED' => 'Y',
        ];

        $oneC = ProductAmountCalculator::calculate1C($row);
        $b24 = ProductAmountCalculator::calculateBitrix24($row);

        $this->assertSame(535.14, $oneC['SUM_NET']);
        $this->assertSame(117.72, $oneC['TAX_SUM']);
        $this->assertSame(652.86, $oneC['SUM_GROSS']);
        $this->assertSame(535.12, $b24['SUM_NET']);
        $this->assertSame(652.84, $b24['SUM_GROSS']);
    }

    public function testDeal5938Row87485UsesFloorNetBaseInOneCMode(): void
    {
        $row = [
            'QUANTITY' => 2,
            'PRICE' => 1316.89,
            'PRICE_EXCLUSIVE' => 1079.41803279,
            'DISCOUNT_RATE' => 7,
            'TAX_RATE' => 22,
            'TAX_INCLUDED' => 'Y',
        ];

        $oneC = ProductAmountCalculator::calculate1C($row);
        $b24 = ProductAmountCalculator::calculateBitrix24($row);

        $this->assertSame(1079.41, $oneC['PRICE']);
        $this->assertSame(2158.82, $oneC['SUM_NET']);
        $this->assertSame(474.94, $oneC['TAX_SUM']);
        $this->assertSame(2633.76, $oneC['SUM_GROSS']);
        $this->assertSame(2158.84, $b24['SUM_NET']);
        $this->assertSame(2633.78, $b24['SUM_GROSS']);
    }

    public function testOneCModeUnchangedForTaxExcluded(): void
    {
        $amounts = ProductAmountCalculator::calculate1C([
            'QUANTITY' => 2,
            'PRICE' => 100,
            'TAX_RATE' => 22,
            'TAX_INCLUDED' => 'N',
        ]);

        $this->assertSame(100.0, $amounts['PRICE']);
        $this->assertSame(200.0, $amounts['SUM_NET']);
        $this->assertSame(44.0, $amounts['TAX_SUM']);
        $this->assertSame(244.0, $amounts['SUM_GROSS']);
    }
}
