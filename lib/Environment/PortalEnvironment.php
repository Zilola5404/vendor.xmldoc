<?php

namespace Ooofix\Xmlupd\Environment;

use Bitrix\Main\Config\Option;
use Bitrix\Main\ModuleManager;
use Ooofix\Xmlupd\Cloud\CloudGenerateRuntime;
use Ooofix\Xmlupd\Config;
use Ooofix\Xmlupd\ModuleInfo;
use Ooofix\Xmlupd\Install\InstallEnvironment;

/** Определение типа портала: облако / коробка. */
final class PortalEnvironment
{
    private const MODULE = ModuleInfo::MODULE_ID;

    public static function crmAdapterMode(): string
    {
        $mode = (string)Config::crmAdapter();

        return in_array($mode, ['auto', 'cloud', 'onprem'], true) ? $mode : 'auto';
    }

    public static function isCloud(): bool
    {
        return match (self::crmAdapterMode()) {
            'cloud'  => true,
            'onprem' => false,
            default  => self::detectCloudAuto(),
        };
    }

    private static function detectCloudAuto(): bool
    {
        if (defined('BX24_HOST_NAME') && (string)BX24_HOST_NAME !== '') {
            return true;
        }

        if (ModuleManager::isModuleInstalled('bitrix24')) {
            return true;
        }

        $portalUrl = (string)Option::get('main', '~license_codeserver_url', '');
        if ($portalUrl !== '' && str_contains($portalUrl, 'bitrix24')) {
            return true;
        }

        $serverName = (string)Option::get('main', 'server_name', '');
        if ($serverName !== '' && str_contains($serverName, 'bitrix24.')) {
            return true;
        }

        return false;
    }

    public static function label(): string
    {
        return self::isCloud() ? 'облако Bitrix24' : 'коробка (on-premise)';
    }

    public static function activeRuntimeModuleId(): string
    {
        return self::MODULE;
    }

    public static function isCloudRuntimeReady(): bool
    {
        if (!self::isCloud()) {
            return true;
        }

        return class_exists(CloudGenerateRuntime::class);
    }

    public static function runtimePathLabel(): string
    {
        return InstallEnvironment::runtimeLabel(self::MODULE);
    }
}
