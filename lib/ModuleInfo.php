<?php

namespace Vendor\Xmldoc;

/** Версия и метаданные модуля */
final class ModuleInfo
{
    public const MODULE_ID = 'ooofix.vendor.xml';
    public const MODULE_TITLE = 'Генерация XML (УПД)';
    public const MODULE_DESCRIPTION = 'Формирование XML УПД из CRM: автоопределение коробка / облако Bitrix24';
    public const PARTNER_NAME = 'ООО "РЕШЕНИЕ"';
    public const PARTNER_URI = 'https://ooofix.ru';

    public static function version(): string
    {
        static $version = null;
        if ($version !== null) {
            return $version;
        }

        $arModuleVersion = [];
        $path = dirname(__DIR__) . '/install/version.php';
        if (is_file($path)) {
            include $path;
        }

        $version = (string)($arModuleVersion['VERSION'] ?? '1.0.0');

        return $version;
    }

    public static function programName(): string
    {
        return self::MODULE_ID . ' ' . self::version();
    }
}
