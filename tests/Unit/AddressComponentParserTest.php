<?php

declare(strict_types=1);

namespace Vendor\Xmldoc\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Vendor\Xmldoc\Address\AddressComponentParser;

final class AddressComponentParserTest extends TestCase
{
    public function testParsesSingleLineAddressFromHouseField(): void
    {
        $normalized = AddressComponentParser::normalize([
            'ADDRESS_POSTAL_CODE' => '',
            'ADDRESS_REGION'      => '',
            'ADDRESS_DISTRICT'    => '',
            'ADDRESS_CITY'        => '',
            'ADDRESS_STREET'      => '',
            'ADDRESS_HOUSE'       => '660098, Красноярский край, г.о. Город Красноярск, г. Красноярск, ул Авиаторов, дом 28, квартира 123',
            'ADDRESS_BUILDING'    => '',
            'ADDRESS_FLAT'        => '',
            'ADDRESS_FULL'        => '',
        ]);

        $this->assertSame('660098', $normalized['ADDRESS_POSTAL_CODE']);
        $this->assertSame('Красноярский край', $normalized['ADDRESS_REGION']);
        $this->assertSame('г.о. Город Красноярск', $normalized['ADDRESS_DISTRICT']);
        $this->assertSame('Красноярск', $normalized['ADDRESS_CITY']);
        $this->assertSame('ул Авиаторов', $normalized['ADDRESS_STREET']);
        $this->assertSame('28', $normalized['ADDRESS_HOUSE']);
        $this->assertSame('кв. 123', $normalized['ADDRESS_FLAT']);
        $this->assertLessThanOrEqual(50, mb_strlen($normalized['ADDRESS_HOUSE']));
    }

    public function testStructuredAddressStaysUntouched(): void
    {
        $normalized = AddressComponentParser::normalize([
            'ADDRESS_POSTAL_CODE' => '660077',
            'ADDRESS_REGION'      => 'Красноярский край',
            'ADDRESS_DISTRICT'    => '',
            'ADDRESS_CITY'        => 'Красноярск',
            'ADDRESS_STREET'      => 'ул. 78 Добровольческой Бригады',
            'ADDRESS_HOUSE'       => 'д. 5',
            'ADDRESS_BUILDING'    => '',
            'ADDRESS_FLAT'        => '81',
            'ADDRESS_FULL'        => '',
        ]);

        $this->assertSame('д. 5', $normalized['ADDRESS_HOUSE']);
        $this->assertSame('ул. 78 Добровольческой Бригады', $normalized['ADDRESS_STREET']);
        $this->assertSame('кв. 81', $normalized['ADDRESS_FLAT']);
    }
}
