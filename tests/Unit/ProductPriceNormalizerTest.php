<?php

declare(strict_types=1);

namespace Vendor\Xmldoc\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Vendor\Xmldoc\Crm\ProductAmountCalculator;
use Vendor\Xmldoc\Crm\ProductPriceNormalizer;

final class ProductPriceNormalizerTest extends TestCase
{
    public function testTaxIncludedLineMatchesEdoExample(): void
    {
        $amounts = ProductPriceNormalizer::normalize([
            'QUANTITY' => 4,
            'PRICE' => 12595.86,
            'TAX_RATE' => 22,
            'TAX_INCLUDED' => 'Y',
        ], ProductAmountCalculator::MODE_1C);

        $this->assertSame(10324.48, $amounts['PRICE']);
        $this->assertSame(41297.90, $amounts['SUM_NET']);
        $this->assertSame(9085.54, $amounts['TAX_SUM']);
        $this->assertSame(50383.44, $amounts['SUM_GROSS']);
    }

    public function testPriceBruttoUsedForGrossBase(): void
    {
        $amounts = ProductPriceNormalizer::normalize([
            'QUANTITY' => 4,
            'PRICE' => 12595.86,
            'PRICE_EXCLUSIVE' => 10324.48,
            'PRICE_BRUTTO' => 12595.86,
            'TAX_RATE' => 22,
            'TAX_INCLUDED' => 'Y',
        ], ProductAmountCalculator::MODE_1C);

        $this->assertSame(10324.48, $amounts['PRICE']);
        $this->assertSame(41297.90, $amounts['SUM_NET']);
        $this->assertSame(9085.54, $amounts['TAX_SUM']);
        $this->assertSame(50383.44, $amounts['SUM_GROSS']);
    }

    public function testPriceBruttoIgnoredWhenPriceDiffers(): void
    {
        $amounts = ProductPriceNormalizer::normalize([
            'QUANTITY' => 4,
            'PRICE' => 12595.86,
            'PRICE_EXCLUSIVE' => 10008.34,
            'PRICE_BRUTTO' => 14092.32,
            'TAX_RATE' => 22,
            'TAX_INCLUDED' => 'Y',
        ], ProductAmountCalculator::MODE_1C);

        $this->assertSame(10324.48, $amounts['PRICE']);
        $this->assertSame(41297.90, $amounts['SUM_NET']);
        $this->assertSame(9085.54, $amounts['TAX_SUM']);
        $this->assertSame(50383.44, $amounts['SUM_GROSS']);
    }

    public function testTaxExcludedPriceStaysNet(): void
    {
        $amounts = ProductPriceNormalizer::normalize([
            'QUANTITY' => 2,
            'PRICE' => 100,
            'TAX_RATE' => 22,
            'TAX_INCLUDED' => 'N',
        ], ProductAmountCalculator::MODE_1C);

        $this->assertSame(100.0, $amounts['PRICE']);
        $this->assertSame(200.0, $amounts['SUM_NET']);
        $this->assertSame(44.0, $amounts['TAX_SUM']);
        $this->assertSame(244.0, $amounts['SUM_GROSS']);
    }

    public function testEmptyTaxIncludedUsesGrossPriceLikeDiadocSample(): void
    {
        $amounts = ProductPriceNormalizer::normalize([
            'QUANTITY' => 1,
            'PRICE' => 919.00,
            'TAX_RATE' => 22,
        ], ProductAmountCalculator::MODE_1C);

        $this->assertSame(753.28, $amounts['PRICE']);
        $this->assertSame(753.28, $amounts['SUM_NET']);
        $this->assertSame(165.72, $amounts['TAX_SUM']);
        $this->assertSame(919.0, $amounts['SUM_GROSS']);
    }

    public function testDiadocQtyTwoLineMatchesSample(): void
    {
        $amounts = ProductPriceNormalizer::normalize([
            'QUANTITY' => 2,
            'PRICE' => 3348.00,
            'PRICE_EXCLUSIVE' => 2744.26,
            'PRICE_BRUTTO' => 3348.00,
            'TAX_RATE' => 22,
            'TAX_INCLUDED' => 'Y',
        ], ProductAmountCalculator::MODE_1C);

        $this->assertSame(2744.26, $amounts['PRICE']);
        $this->assertSame(5488.52, $amounts['SUM_NET']);
        $this->assertSame(6696.0, $amounts['SUM_GROSS']);
        $this->assertSame(1207.48, $amounts['TAX_SUM']);
    }

    public function testDiscountSumInRowDoesNotReduceLineGross(): void
    {
        $amounts = ProductPriceNormalizer::normalize([
            'QUANTITY' => 4,
            'PRICE' => 12595.86,
            'DISCOUNT_SUM' => 1542.74,
            'TAX_RATE' => 22,
            'TAX_INCLUDED' => 'Y',
        ], ProductAmountCalculator::MODE_1C);

        $this->assertSame(50383.44, $amounts['SUM_GROSS']);
        $this->assertSame(41297.90, $amounts['SUM_NET']);
        $this->assertSame(10324.48, $amounts['PRICE']);
        $this->assertSame(9085.54, $amounts['TAX_SUM']);
    }
}
