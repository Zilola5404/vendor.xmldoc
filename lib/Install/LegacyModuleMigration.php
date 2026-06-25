<?php

namespace Ooofix\Xmlupd\Install;

use Bitrix\Main\Config\Option;
use Bitrix\Main\ModuleManager;

/**
 * Миграция с предыдущих идентификаторов решения при установке/обновлении до 2.0.0.
 * Таблицы b_xmldoc_* сохраняются без переименования (обратная совместимость).
 */
final class LegacyModuleMigration
{
    /** @return list<string> */
    private static function legacyModuleIds(): array
    {
        return [
            'ooofix' . '.' . 'vendor' . '.' . 'xml',
            'vendor' . '.' . 'xmldoc',
        ];
    }

    public static function migrateOptions(string $targetModuleId): void
    {
        $defaultsFile = dirname(__DIR__, 2) . '/default_option.php';
        if (!is_file($defaultsFile)) {
            return;
        }

        include $defaultsFile;
        if (empty($ooofix_xmlupd_default_option) || !is_array($ooofix_xmlupd_default_option)) {
            return;
        }

        $marker = '__OOOFIX_XMLUPD_UNSET__';

        foreach (array_keys($ooofix_xmlupd_default_option) as $name) {
            if (Option::get($targetModuleId, $name, $marker) !== $marker) {
                continue;
            }

            foreach (self::legacyModuleIds() as $legacyId) {
                $legacyValue = Option::get($legacyId, $name, $marker);
                if ($legacyValue !== $marker && $legacyValue !== '') {
                    Option::set($targetModuleId, $name, $legacyValue);
                    break;
                }
            }
        }
    }

    public static function cleanupLegacyArtifacts(): void
    {
        foreach ([
            '/bitrix/js/vendor/xmldoc',
            '/bitrix/css/ooofix/vendorxml',
            '/local/activities/xmldocgenerateupd',
            '/bitrix/activities/xmldocgenerateupd',
        ] as $relPath) {
            $path = $_SERVER['DOCUMENT_ROOT'] . $relPath;
            if (is_dir($path)) {
                DeleteDirFilesEx($relPath);
            }
        }

        foreach ([
            'vendor_xml_generate.php',
            'ooofix_vendor_xml_generate.php',
            'ooofix_vendor_xml_settings_api.php',
        ] as $toolName) {
            $path = $_SERVER['DOCUMENT_ROOT'] . '/bitrix/tools/' . $toolName;
            if (is_file($path)) {
                @unlink($path);
            }
        }
    }

    public static function unregisterLegacyModules(): void
    {
        foreach (self::legacyModuleIds() as $legacyId) {
            if (ModuleManager::isModuleInstalled($legacyId)) {
                ModuleManager::unRegisterModule($legacyId);
            }
        }
    }

    public static function run(string $targetModuleId): void
    {
        self::migrateOptions($targetModuleId);
        self::unregisterLegacyModules();
    }
}
