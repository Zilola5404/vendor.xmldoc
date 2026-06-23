<?php

declare(strict_types=1);

namespace Vendor\Xmldoc\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Vendor\Xmldoc\Config;
use Vendor\Xmldoc\Documents\Upd\UpdMapper;
use Vendor\Xmldoc\Documents\Upd\UpdValidator;
use Vendor\Xmldoc\Tests\Support\TestConfig;

final class UpdValidatorTest extends TestCase
{
    protected function setUp(): void
    {
        Config::setInstance(new TestConfig(dirname(__DIR__, 2) . '/config/mapping/upd.php'));
    }

    protected function tearDown(): void
    {
        Config::setInstance(null);
    }

    public function testValidMappedDataHasNoErrors(): void
    {
        $mapper = new UpdMapper();
        $validator = new UpdValidator($mapper);

        $mapped = $mapper->map($this->sampleCrmData());
        $errors = $validator->validate($mapped);

        $this->assertSame([], $errors);
    }

    public function testMissingBuyerInnProducesError(): void
    {
        $mapper = new UpdMapper();
        $validator = new UpdValidator($mapper);

        $data = $this->sampleCrmData();
        $data['buyer']['INN'] = '';
        $mapped = $mapper->map($data);
        $errors = $validator->validate($mapped);

        $this->assertNotEmpty($errors);
        $this->assertTrue(
            (bool)array_filter($errors, static fn(string $e): bool => str_contains($e, 'ИНН'))
        );
    }

    public function testProductSumMismatchDetected(): void
    {
        $validator = new UpdValidator(new UpdMapper());
        $errors = $validator->validate([
            'buyer_name' => 'Покупатель',
            'buyer_inn' => '7700000000',
            'buyer_kpp' => '770001001',
            'buyer_address' => 'Москва',
            'seller_name' => 'Продавец',
            'seller_inn' => '7800000000',
            'seller_address' => 'СПб',
            'signatory_name' => 'Иванов',
            'signatory_position' => 'Директор',
            'doc_number' => '1',
            'doc_date' => '01.01.2026',
            'products' => [
                [
                    'LINE' => 1,
                    'NAME' => 'Товар',
                    'QUANTITY' => 1,
                    'PRICE' => 100,
                    'SUM_NET' => 999,
                    'TAX_RATE' => 22,
                    'TAX_SUM' => 22,
                    'SUM_GROSS' => 122,
                ],
            ],
            'totals' => [
                'SUM_NET' => 999,
                'TAX_SUM' => 22,
                'SUM_GROSS' => 122,
            ],
        ]);

        $this->assertNotEmpty($errors);
    }

    public function testGrossDerivedProductSumsAccepted(): void
    {
        $validator = new UpdValidator(new UpdMapper());
        $errors = $validator->validate([
            'buyer_name' => 'Покупатель',
            'buyer_inn' => '7700000000',
            'buyer_kpp' => '770001001',
            'buyer_address' => 'Москва',
            'seller_name' => 'Продавец',
            'seller_inn' => '7800000000',
            'seller_address' => 'СПб',
            'signatory_name' => 'Иванов Иван',
            'signatory_position' => 'Директор',
            'doc_number' => '1',
            'doc_date' => '01.01.2026',
            'products' => [
                [
                    'LINE' => 1,
                    'NAME' => 'Форсунка',
                    'QUANTITY' => 4,
                    'PRICE' => 10324.48,
                    'SUM_NET' => 41297.90,
                    'TAX_RATE' => 22,
                    'TAX_SUM' => 9085.54,
                    'SUM_GROSS' => 50383.44,
                ],
            ],
            'totals' => [
                'SUM_NET' => 41297.90,
                'TAX_SUM' => 9085.54,
                'SUM_GROSS' => 50383.44,
            ],
        ]);

        $this->assertSame([], $errors);
    }

    public function testReconciledLastLinePassesValidation(): void
    {
        $validator = new UpdValidator(new UpdMapper());
        $errors = $validator->validate([
            'buyer_name' => 'Покупатель',
            'buyer_inn' => '7700000000',
            'buyer_kpp' => '770001001',
            'buyer_address' => 'Москва',
            'seller_name' => 'Продавец',
            'seller_inn' => '7800000000',
            'seller_address' => 'СПб',
            'signatory_name' => 'Иванов Иван',
            'signatory_position' => 'Директор',
            'doc_number' => '1',
            'doc_date' => '01.01.2026',
            'products' => [
                [
                    'LINE' => 1,
                    'NAME' => 'Товар',
                    'QUANTITY' => 4,
                    'PRICE' => 10324.48,
                    'SUM_NET' => 41297.90,
                    'TAX_RATE' => 22,
                    'TAX_SUM' => 9085.54,
                    'SUM_GROSS' => 50383.44,
                ],
                [
                    'LINE' => 2,
                    'NAME' => 'Крышка цепи ГРМ Cummins ISF2.8 Е-5 Оригинал № 5363383 | Foton',
                    'QUANTITY' => 1,
                    'PRICE' => 1200.01,
                    'SUM_NET' => 1200.01,
                    'TAX_RATE' => 22,
                    'TAX_SUM' => 264.00,
                    'SUM_GROSS' => 1464.01,
                ],
            ],
            'totals' => [
                'SUM_NET' => 42497.91,
                'TAX_SUM' => 9349.54,
                'SUM_GROSS' => 51847.45,
            ],
        ]);

        $this->assertSame([], $errors);
    }

    public function testDealTotalsDoNotRequireLineSumMatch(): void
    {
        $validator = new UpdValidator(new UpdMapper());
        $errors = $validator->validate([
            'buyer_name' => 'Покупатель',
            'buyer_inn' => '7700000000',
            'buyer_kpp' => '770001001',
            'buyer_address' => 'Москва',
            'seller_name' => 'Продавец',
            'seller_inn' => '7800000000',
            'seller_address' => 'СПб',
            'signatory_name' => 'Иванов',
            'signatory_position' => 'Директор',
            'doc_number' => '1',
            'doc_date' => '01.01.2026',
            'totals_from_deal' => true,
            'products' => [
                [
                    'LINE' => 1,
                    'NAME' => 'Товар',
                    'QUANTITY' => 1,
                    'PRICE' => 100.0,
                    'SUM_NET' => 100.0,
                    'TAX_RATE' => 22,
                    'TAX_SUM' => 22.0,
                    'SUM_GROSS' => 122.0,
                ],
            ],
            'totals' => [
                'SUM_NET' => 424914.96,
                'TAX_SUM' => 93481.32,
                'SUM_GROSS' => 518396.28,
            ],
        ]);

        $this->assertSame([], $errors);
    }

    /** @return array<string, mixed> */
    private function sampleCrmData(): array
    {
        return [
            'entity' => [
                'ID' => 100,
                'UF_UPD_NUMBER' => '6608',
                'DOC_DATE' => '15.06.2026',
            ],
            'buyer' => [
                'NAME' => 'ООО Покупатель',
                'INN' => '7700000000',
                'KPP' => '770001001',
                'ADDRESS_FULL' => 'Москва, ул. Тестовая, 1',
            ],
            'seller' => [
                'NAME' => 'ООО Продавец',
                'INN' => '7800000000',
                'ADDRESS_FULL' => 'СПб, Невский, 1',
            ],
            'products' => [
                [
                    'LINE' => 1,
                    'NAME' => 'Товар',
                    'QUANTITY' => 1,
                    'PRICE' => 100,
                    'SUM_NET' => 100,
                    'TAX_RATE' => 22,
                    'TAX_SUM' => 22,
                    'SUM_GROSS' => 122,
                    'MEASURE' => 'шт',
                ],
            ],
            'signatory' => [
                'NAME' => 'Иванов Иван',
                'POSITION' => 'Директор',
            ],
        ];
    }
}
