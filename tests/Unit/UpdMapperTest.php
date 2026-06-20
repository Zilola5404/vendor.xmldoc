<?php

declare(strict_types=1);

namespace Vendor\Xmldoc\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Vendor\Xmldoc\Config;
use Vendor\Xmldoc\Documents\Upd\UpdMapper;
use Vendor\Xmldoc\Tests\Support\TestConfig;

final class UpdMapperTest extends TestCase
{
    protected function setUp(): void
    {
        Config::setInstance(new TestConfig(dirname(__DIR__, 2) . '/config/mapping/upd.php'));
    }

    protected function tearDown(): void
    {
        Config::setInstance(null);
    }

    public function testMapCalculatesTotals(): void
    {
        $mapper = new UpdMapper();
        $mapped = $mapper->map([
            'entity' => [
                'ID' => 100,
                'UF_UPD_NUMBER' => '6608',
                'DOC_DATE' => '15.06.2026',
            ],
            'buyer' => [
                'NAME' => 'ООО Покупатель',
                'INN' => '7700000000',
                'KPP' => '770001001',
                'ADDRESS_FULL' => 'Москва',
            ],
            'seller' => [
                'NAME' => 'ООО Продавец',
                'INN' => '7800000000',
                'ADDRESS_FULL' => 'СПб',
            ],
            'products' => [
                [
                    'LINE' => 1,
                    'NAME' => 'Товар',
                    'QUANTITY' => 2,
                    'PRICE' => 100,
                    'SUM_NET' => 200,
                    'TAX_RATE' => 22,
                    'TAX_SUM' => 44,
                    'SUM_GROSS' => 244,
                    'MEASURE' => 'шт',
                ],
            ],
            'signatory' => [
                'NAME' => 'Иванов Иван',
                'POSITION' => 'Директор',
            ],
        ]);

        $this->assertSame('6608', $mapped['doc_number']);
        $this->assertSame('7700000000', $mapped['buyer_inn']);
        $this->assertSame(200.0, $mapped['totals']['SUM_NET']);
        $this->assertSame(44.0, $mapped['totals']['TAX_SUM']);
        $this->assertSame(244.0, $mapped['totals']['SUM_GROSS']);
    }

    public function testResolveDocNumberFromEntityIdWhenEmpty(): void
    {
        $mapper = new UpdMapper();
        $mapped = $mapper->map([
            'entity' => ['ID' => 42, 'DOC_DATE' => '01.01.2026'],
            'buyer' => [],
            'seller' => [],
            'products' => [],
            'signatory' => [],
        ]);

        $this->assertSame('42', $mapped['doc_number']);
    }
}
