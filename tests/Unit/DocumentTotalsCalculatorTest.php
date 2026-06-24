<?php

declare(strict_types=1);

namespace Vendor\Xmldoc\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Vendor\Xmldoc\Crm\DocumentTotalsCalculator;

final class DocumentTotalsCalculatorTest extends TestCase
{
    public function testFinalize1CAdjustsHeaderWhenLineNetExceedsNetFromGross(): void
    {
        $totals = DocumentTotalsCalculator::finalize1C([
            'SUM_NET' => 280118.44,
            'TAX_SUM' => 61626.05,
            'SUM_GROSS' => 341744.49,
        ], 22.0);

        $this->assertSame(280118.42, $totals['SUM_NET']);
        $this->assertSame(61626.07, $totals['TAX_SUM']);
        $this->assertSame(341744.49, $totals['SUM_GROSS']);
    }

    public function testFinalizeBitrix24ReturnsLineTotalsAsIs(): void
    {
        $input = [
            'SUM_NET' => 280118.57,
            'TAX_SUM' => 61626.05,
            'SUM_GROSS' => 341744.62,
        ];

        $this->assertSame($input, DocumentTotalsCalculator::finalizeBitrix24($input));
    }
}
