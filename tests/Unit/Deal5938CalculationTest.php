<?php

declare(strict_types=1);

namespace Vendor\Xmldoc\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Vendor\Xmldoc\Config;
use Vendor\Xmldoc\Crm\DocumentTotalsCalculator;
use Vendor\Xmldoc\Crm\ProductAmountCalculator;
use Vendor\Xmldoc\Crm\ProductPriceNormalizer;
use Vendor\Xmldoc\Documents\Upd\UpdMapper;
use Vendor\Xmldoc\Documents\Upd\UpdValidator;
use Vendor\Xmldoc\Tests\Support\TestConfig;

/**
 * Регрессия по сделке №5938 (35 строк, НДС 22%, скидка 7% на части позиций).
 * Фикстура: tests/fixtures/deal_5938_product_rows.json
 */
final class Deal5938CalculationTest extends TestCase
{
    private const SHRUS_ROW_ID = 87479;

    /** @var list<array<string, mixed>> */
    private array $rows;

    protected function setUp(): void
    {
        $path = dirname(__DIR__) . '/fixtures/deal_5938_product_rows.json';
        $this->rows = json_decode((string)file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
    }

    protected function tearDown(): void
    {
        Config::setInstance(null);
    }

    public function testFixtureHasExpectedShape(): void
    {
        $this->assertCount(35, $this->rows);
        $this->assertSame(self::SHRUS_ROW_ID, $this->rows[1]['ID']);
    }

    public function testShrusLineMatchesBitrix24Card(): void
    {
        $shrus = $this->rowById(self::SHRUS_ROW_ID);
        $amounts = ProductAmountCalculator::calculateBitrix24($shrus);

        $this->assertSame(3820.62, $amounts['PRICE']);
        $this->assertSame(15282.48, $amounts['SUM_NET']);
        $this->assertSame(3362.16, $amounts['TAX_SUM']);
        $this->assertSame(18644.64, $amounts['SUM_GROSS']);
    }

    public function testShrusLineMatchesOneCMode(): void
    {
        $shrus = $this->rowById(self::SHRUS_ROW_ID);
        $oneC = ProductAmountCalculator::calculate1C($shrus);
        $b24 = ProductAmountCalculator::calculateBitrix24($shrus);

        $this->assertSame($b24['PRICE'], $oneC['PRICE']);
        $this->assertSame($b24['SUM_NET'], $oneC['SUM_NET']);
        $this->assertSame($b24['TAX_SUM'], $oneC['TAX_SUM']);
        $this->assertSame($b24['SUM_GROSS'], $oneC['SUM_GROSS']);
    }

    public function testOneCModeLineTotalsMatchEdoReference(): void
    {
        $lines = $this->sumLines(ProductAmountCalculator::MODE_1C);
        $totals = DocumentTotalsCalculator::finalize1C($lines, 22.0);

        $this->assertSame(280118.44, $lines['SUM_NET']);
        $this->assertSame(61626.05, $lines['TAX_SUM']);
        $this->assertSame(341744.49, $lines['SUM_GROSS']);
        $this->assertSame($this->edoHeaderTotals(), $totals);
    }

    public function testBitrix24ModeLineTotalsMatchDealCard(): void
    {
        $lines = $this->sumLines(ProductAmountCalculator::MODE_BITRIX24);

        $this->assertSame(280118.57, $lines['SUM_NET']);
        $this->assertSame(61626.05, $lines['TAX_SUM']);
        $this->assertSame(341744.62, $lines['SUM_GROSS']);
    }

    public function testBitrix24MapperTotalsEqualSumOfLines(): void
    {
        Config::setInstance(new TestConfig(
            dirname(__DIR__, 2) . '/config/mapping/upd.php',
            calculationMode: ProductAmountCalculator::MODE_BITRIX24,
        ));

        $products = $this->buildProducts(ProductAmountCalculator::MODE_BITRIX24);
        $mapped = (new UpdMapper())->map($this->crmPayload($products));

        $this->assertFalse($mapped['totals_from_deal']);
        $this->assertSame(ProductAmountCalculator::MODE_BITRIX24, $mapped['calculation_mode']);
        $this->assertSame(280118.57, $mapped['totals']['SUM_NET']);
        $this->assertSame(61626.05, $mapped['totals']['TAX_SUM']);
        $this->assertSame(341744.62, $mapped['totals']['SUM_GROSS']);
        $this->assertSame([], (new UpdValidator(new UpdMapper()))->validate($mapped));
    }

    public function testOneCMapperTotalsMatchEdoReference(): void
    {
        Config::setInstance(new TestConfig(
            dirname(__DIR__, 2) . '/config/mapping/upd.php',
            calculationMode: ProductAmountCalculator::MODE_1C,
        ));

        $products = $this->buildProducts(ProductAmountCalculator::MODE_1C);
        $mapped = (new UpdMapper())->map($this->crmPayload($products));

        $this->assertFalse($mapped['totals_from_deal']);
        $this->assertSame(280118.42, $mapped['totals']['SUM_NET']);
        $this->assertSame(61626.07, $mapped['totals']['TAX_SUM']);
        $this->assertSame(341744.49, $mapped['totals']['SUM_GROSS']);
    }

    /**
     * @return array{SUM_NET: float, TAX_SUM: float, SUM_GROSS: float}
     */
    private function sumLines(string $mode): array
    {
        $net = 0.0;
        $tax = 0.0;
        $gross = 0.0;

        foreach ($this->rows as $row) {
            $amounts = ProductAmountCalculator::calculate($row, $mode);
            $net += $amounts['SUM_NET'];
            $tax += $amounts['TAX_SUM'];
            $gross += $amounts['SUM_GROSS'];
        }

        return [
            'SUM_NET' => round($net, 2),
            'TAX_SUM' => round($tax, 2),
            'SUM_GROSS' => round($gross, 2),
        ];
    }

    /**
     * @return array{SUM_NET: float, TAX_SUM: float, SUM_GROSS: float}
     */
    private function edoHeaderTotals(): array
    {
        return [
            'SUM_NET' => 280118.42,
            'TAX_SUM' => 61626.07,
            'SUM_GROSS' => 341744.49,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function rowById(int $id): array
    {
        foreach ($this->rows as $row) {
            if ((int)$row['ID'] === $id) {
                return $row;
            }
        }

        $this->fail('Row not found: ' . $id);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function buildProducts(string $mode): array
    {
        $products = [];
        $line = 0;

        foreach ($this->rows as $row) {
            $line++;
            $amounts = ProductPriceNormalizer::normalize($row, $mode);
            $products[] = [
                'LINE' => $line,
                'NAME' => (string)$row['PRODUCT_NAME'],
                'QUANTITY' => (float)$row['QUANTITY'],
                'PRICE' => $amounts['PRICE'],
                'SUM_NET' => $amounts['SUM_NET'],
                'SUM_GROSS' => $amounts['SUM_GROSS'],
                'TAX_RATE' => $amounts['TAX_RATE'],
                'TAX_SUM' => $amounts['TAX_SUM'],
                'MEASURE' => 'шт',
            ];
        }

        return $products;
    }

    /**
     * @param list<array<string, mixed>> $products
     * @return array<string, mixed>
     */
    private function crmPayload(array $products): array
    {
        return [
            'entity' => [
                'ID' => 5938,
                'DOC_DATE' => '23.06.2026',
                'TOTAL_GROSS' => 341744.62,
                'TOTAL_NET' => 280118.53,
                'TOTAL_TAX' => 61626.09,
            ],
            'buyer' => [
                'NAME' => 'Покупатель',
                'INN' => '7700000000',
                'KPP' => '770001001',
                'ADDRESS_FULL' => 'Москва',
            ],
            'seller' => [
                'NAME' => 'Продавец',
                'INN' => '7800000000',
                'ADDRESS_FULL' => 'СПб',
            ],
            'products' => $products,
            'signatory' => [
                'NAME' => 'Иванов',
                'POSITION' => 'Директор',
            ],
        ];
    }
}
