<?php

namespace Ooofix\Xmlupd\Admin;

use Bitrix\Main\Loader;

/** Проверка прав доступа к настройкам модуля (без доступа к админке). */
final class ModuleAccess
{
    public static function canRead(): bool
    {
        global $APPLICATION, $USER;

        $right = (string)$APPLICATION->GetGroupRight(SettingsService::MODULE_ID);
        if ($right !== '' && $right !== 'D' && $right >= 'R') {
            return true;
        }

        return is_object($USER) && method_exists($USER, 'IsAdmin') && $USER->IsAdmin();
    }

    public static function canWrite(): bool
    {
        global $APPLICATION, $USER;

        $right = (string)$APPLICATION->GetGroupRight(SettingsService::MODULE_ID);
        if ($right >= 'W') {
            return true;
        }

        return is_object($USER) && method_exists($USER, 'IsAdmin') && $USER->IsAdmin();
    }

    public static function ensureModuleLoaded(): bool
    {
        if (Loader::includeModule(SettingsService::MODULE_ID)) {
            return true;
        }

        $relative = getLocalPath('modules/' . SettingsService::MODULE_ID . '/include.php');
        $include = is_string($relative) ? $_SERVER['DOCUMENT_ROOT'] . $relative : '';

        if ($include !== '' && is_file($include)) {
            require_once $include;

            return Loader::includeModule(SettingsService::MODULE_ID);
        }

        return false;
    }
}
