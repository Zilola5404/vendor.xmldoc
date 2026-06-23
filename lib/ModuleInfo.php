<?php

namespace Vendor\Xmldoc;

/** Версия и метаданные модуля */
final class ModuleInfo
{
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
        return 'vendor.xml ' . self::version();
    }
}
