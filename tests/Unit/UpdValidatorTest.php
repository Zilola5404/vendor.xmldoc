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
