<?php

namespace Vendor\Xmldoc;

use Bitrix\Main\Config\Option;

/** Настройки модуля из b_option */
class Config
{
    private const MODULE = 'vendor.xmldoc';

    public static function dadataApiKey(): string
    {
        return (string)Option::get(self::MODULE, 'dadata_api_key', '');
    }

    public static function sellerRequisiteId(): int
    {
        return (int)Option::get(self::MODULE, 'seller_requisite_id', 0);
    }

    public static function signatoryUserId(): int
    {
        return (int)Option::get(self::MODULE, 'signatory_user_id', 0);
    }

    /** settings — из настроек; current_user — кто нажал кнопку / запустил БП */
    public static function signatoryMode(): string
    {
        return (string)Option::get(self::MODULE, 'signatory_mode', 'settings');
    }

    public static function signatoryPosition(): string
    {
        return (string)Option::get(self::MODULE, 'signatory_position', 'Сотрудник');
    }

    public static function smartInvoiceTypeId(): int
    {
        return (int)Option::get(self::MODULE, 'smart_invoice_type_id', '31');
    }

    public static function publishTimeline(): bool
    {
        return Option::get(self::MODULE, 'publish_timeline', 'Y') === 'Y';
    }

    public static function xsdPath(): string
    {
        $path = (string)Option::get(self::MODULE, 'xsd_path', '');
        if ($path !== '' && is_file($path)) {
            return $path;
        }

        // XSD только если явно указан в настройках (не валидируем по заглушке)
        return '';
    }

    public static function updFunction(): string
    {
        return (string)Option::get(self::MODULE, 'upd_function', 'СЧФДОП');
    }

    /** Кодировка сохраняемого файла (windows-1251 для Диадок) */
    public static function fileEncoding(): string
    {
        return (string)Option::get(self::MODULE, 'file_encoding', 'windows-1251');
    }

    public static function mappingPath(): string
    {
        return dirname(__DIR__) . '/config/mapping/upd.php';
    }
}
