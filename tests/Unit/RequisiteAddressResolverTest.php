<?php

declare(strict_types=1);

namespace Vendor\Xmldoc\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Vendor\Xmldoc\Address\RequisiteAddressResolver;

final class RequisiteAddressResolverTest extends TestCase
{
    public function testNormalizeRowBuildsStructuredAddress(): void
    {
        $address = RequisiteAddressResolver::normalizeRow([
            'POSTAL_CODE' => '660077',
            'PROVINCE' => 'Красноярский край',
            'CITY' => 'Красноярск',
            'ADDRESS_1' => 'ул. 78 Добровольческой Бригады',
            'ADDRESS_2' => 'д. 5',
            'APARTMENT' => '81',
        ]);

        $this->assertSame('660077', $address['ADDRESS_POSTAL_CODE']);
        $this->assertSame('Красноярск', $address['ADDRESS_CITY']);
        $this->assertSame('ул. 78 Добровольческой Бригады', $address['ADDRESS_STREET']);
        $this->assertSame('д. 5', $address['ADDRESS_HOUSE']);
        $this->assertSame('81', $address['ADDRESS_FLAT']);
    }

    public function testMissingRequiredFieldsListsEmptyParts(): void
    {
        $missing = RequisiteAddressResolver::missingRequiredFields([
            'ADDRESS_POSTAL_CODE' => '',
            'ADDRESS_REGION' => 'Красноярский край',
            'ADDRESS_CITY' => 'Красноярск',
            'ADDRESS_STREET' => '',
            'ADDRESS_HOUSE' => '',
            'ADDRESS_REGION_CODE' => '24',
            'ADDRESS_FULL' => '',
        ]);

        $this->assertContains('индекс', $missing);
        $this->assertContains('улица', $missing);
        $this->assertContains('дом', $missing);
    }
}
