<?php

declare(strict_types=1);

namespace Vendor\Xmldoc\Tests\Support;

use Vendor\Xmldoc\Contract\ConfigInterface;

/** Заглушка настроек для unit-тестов без Bitrix. */
final class TestConfig implements ConfigInterface
{
    public function __construct(
        private readonly string $mappingPath,
        private readonly string $updFunction = 'СЧФДОП',
        private readonly string $calculationMode = '1C',
    ) {
    }

    public function dadataApiKey(): string
    {
        return '';
    }

    public function sellerRequisiteId(): int
    {
        return 0;
    }

    public function signatoryUserId(): int
    {
        return 0;
    }

    public function signatoryMode(): string
    {
        return 'settings';
    }

    public function signatoryPosition(): string
    {
        return 'Сотрудник';
    }

    public function smartInvoiceTypeId(): int
    {
        return 31;
    }

    public function publishTimeline(): bool
    {
        return false;
    }

    public function xsdPath(): string
    {
        return '';
    }

    public function updFunction(): string
    {
        return $this->updFunction;
    }

    public function fileEncoding(): string
    {
        return 'windows-1251';
    }

    public function mappingPath(): string
    {
        return $this->mappingPath;
    }

    public function crmAdapter(): string
    {
        return 'auto';
    }

    public function cloudRestWebhook(): string
    {
        return '';
    }

    public function xmlFormatVersion(): string
    {
        return '5.03';
    }

    public function xsdSchemaRevision(): string
    {
        return 'auto';
    }

    public function calculationMode(): string
    {
        return $this->calculationMode;
    }
}
