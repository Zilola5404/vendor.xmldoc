<?php

declare(strict_types=1);

namespace Vendor\Xmldoc\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Vendor\Xmldoc\Address\RegionCodeResolver;

final class RegionCodeResolverTest extends TestCase
{
    public function testResolvesFromProvinceCodeKladr(): void
    {
        $this->assertSame('24', RegionCodeResolver::resolve('2400000000000'));
    }

    public function testResolvesKrasnoyarskFromRegionName(): void
    {
        $this->assertSame(
            '24',
            RegionCodeResolver::resolve('', 'Красноярский край', 'Красноярск', '660077', '')
        );
    }

    public function testResolvesFromPostalIndexPrefix(): void
    {
        $this->assertSame('24', RegionCodeResolver::resolve('', '', 'Норильск', '663305', ''));
    }

    public function testResolvesFromFullAddressText(): void
    {
        $this->assertSame(
            '24',
            RegionCodeResolver::resolve('', '', '', '', '663305, Красноярский край, г. Норильск, ул. Ленина, 1')
        );
    }
}
