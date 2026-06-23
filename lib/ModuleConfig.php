<?php

namespace Vendor\Xmldoc;

use Bitrix\Main\Config\Option;
use Vendor\Xmldoc\Contract\ConfigInterface;

/** Реализация настроек модуля из b_option. */
final class ModuleConfig implements ConfigInterface
{
    private const MODULE = 'vendor.xml';

    public function dadataApiKey(): string
    {
        return (string)Option::get(self::MODULE, 'dadata_api_key', '');
    }

    public function sellerRequisiteId(): int
    {
        return (int)Option::get(self::MODULE, 'seller_requisite_id', 0);
    }

    public function signatoryUserId(): int
    {
        return (int)Option::get(self::MODULE, 'signatory_user_id', 0);
    }

    public function signatoryMode(): string
    {
        return (string)Option::get(self::MODULE, 'signatory_mode', 'settings');
    }

    public function signatoryPosition(): string
    {
        return (string)Option::get(self::MODULE, 'signatory_position', 'Сотрудник');
    }

    public function smartInvoiceTypeId(): int
    {
        return (int)Option::get(self::MODULE, 'smart_invoice_type_id', '31');
    }

    public function publishTimeline(): bool
    {
        return Option::get(self::MODULE, 'publish_timeline', 'Y') === 'Y';
    }

    public function xsdPath(): string
    {
        $path = (string)Option::get(self::MODULE, 'xsd_path', '');
        if ($path !== '' && is_file($path)) {
            return $path;
        }

        return '';
    }

    public function updFunction(): string
    {
        return (string)Option::get(self::MODULE, 'upd_function', 'СЧФДОП');
    }

    public function fileEncoding(): string
    {
        return (string)Option::get(self::MODULE, 'file_encoding', 'windows-1251');
    }

    public function mappingPath(): string
    {
        return dirname(__DIR__) . '/config/mapping/upd.php';
    }

    public function crmAdapter(): string
    {
        return (string)Option::get(self::MODULE, 'crm_adapter', 'auto');
    }

    public function cloudRestWebhook(): string
    {
        return (string)Option::get(self::MODULE, 'cloud_rest_webhook', '');
    }

    public function xmlFormatVersion(): string
    {
        $version = (string)Option::get(self::MODULE, 'xml_format_version', '5.03');

        return in_array($version, ['5.02', '5.03'], true) ? $version : '5.03';
    }

    public function xsdSchemaRevision(): string
    {
        return (string)Option::get(self::MODULE, 'xsd_schema_revision', 'auto');
    }
}
