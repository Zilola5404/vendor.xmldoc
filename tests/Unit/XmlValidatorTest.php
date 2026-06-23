<?php

declare(strict_types=1);

namespace Vendor\Xmldoc\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Vendor\Xmldoc\Config;
use Vendor\Xmldoc\Tests\Support\TestConfig;
use Vendor\Xmldoc\Xml\XsdSchemaRegistry;
use Vendor\Xmldoc\XmlValidator;

final class XmlValidatorTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $mapping = dirname(__DIR__, 2) . '/config/mapping/upd.php';
        Config::setInstance(new TestConfig($mapping));
    }

    protected function tearDown(): void
    {
        Config::setInstance(null);
        parent::tearDown();
    }

    public function testRegistryResolves503SellerSchema(): void
    {
        $path = XsdSchemaRegistry::resolveSellerSchema('5.03', 'auto');

        $this->assertFileExists($path);
        $this->assertStringContainsString('ON_NSCHFDOPPR_1_997_01_05_03', basename($path));
    }

    public function testRegistryExtractsFormatVersionFromXml(): void
    {
        $xml = '<?xml version="1.0"?><Файл ВерсФорм="5.03" ИдФайл="test"></Файл>';

        $this->assertSame('5.03', XsdSchemaRegistry::extractFormatVersion($xml));
    }

    public function testMalformedXmlFailsWithReadableMessage(): void
    {
        $validator = new XmlValidator();
        $result = $validator->validateDetailed('<Файл><не закрыт>');

        $this->assertFalse($result['valid']);
        $this->assertNotEmpty($result['user_message']);
        $this->assertStringContainsString('Не удалось сформировать УПД', $result['user_message']);
    }

    public function testReferenceDiadocSamplePassesXsdWhenPresent(): void
    {
        $sample = dirname(__DIR__, 2) . '/tests/fixtures/upd_503_reference.xml';
        if (!is_file($sample)) {
            $this->markTestSkipped('Эталонный XML отсутствует: tests/fixtures/upd_503_reference.xml');
        }

        $xml = (string)file_get_contents($sample);
        if ($xml === '') {
            $this->markTestSkipped('Пустой эталонный XML');
        }

        if (!str_contains($xml, 'encoding="UTF-8"') && !mb_check_encoding($xml, 'UTF-8')) {
            $converted = @iconv('windows-1251', 'UTF-8//IGNORE', $xml);
            if (is_string($converted) && $converted !== '') {
                $xml = preg_replace('/encoding="windows-1251"/i', 'encoding="UTF-8"', $converted, 1) ?? $converted;
            }
        }

        $validator = new XmlValidator();
        $result = $validator->validateDetailed($xml);

        if (!$result['valid']) {
            $this->fail(
                "Эталонный XML не прошёл XSD:\n"
                . implode("\n", $result['errors'])
                . "\nСхема: " . ($result['schema_path'] ?? '')
            );
        }

        $this->assertTrue($result['valid']);
    }
}
