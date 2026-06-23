<?php

declare(strict_types=1);

namespace Vendor\Xmldoc\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Vendor\Xmldoc\Person\FioParser;

final class FioParserTest extends TestCase
{
    public function testKeepsSeparateFields(): void
    {
        $fio = FioParser::resolve('Иванов', 'Иван', 'Иванович');

        $this->assertSame('Иванов', $fio['last']);
        $this->assertSame('Иван', $fio['first']);
        $this->assertSame('Иванович', $fio['middle']);
    }

    public function testParsesFullNameString(): void
    {
        $fio = FioParser::resolve('', '', '', 'Кондаков Андрей Владимирович');

        $this->assertSame('Кондаков', $fio['last']);
        $this->assertSame('Андрей', $fio['first']);
        $this->assertSame('Владимирович', $fio['middle']);
    }

    public function testParsesIpPrefix(): void
    {
        $fio = FioParser::resolve('', '', '', 'ИП Петров Пётр Петрович');

        $this->assertSame('Петров', $fio['last']);
        $this->assertSame('Пётр', $fio['first']);
    }

    public function testFillsMissingLastNameFromFirstNameField(): void
    {
        $fio = FioParser::resolve('', 'Сидоров Алексей', '');

        $this->assertSame('Сидоров', $fio['last']);
        $this->assertSame('Алексей', $fio['first']);
    }
}
