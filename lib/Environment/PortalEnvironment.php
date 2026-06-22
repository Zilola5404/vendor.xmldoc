<?php

namespace Vendor\Xmldoc\Environment;

use Bitrix\Main\Config\Option;
use Bitrix\Main\ModuleManager;
use Vendor\Xmldoc\Config;

/** Определение типа портала: облако / коробка. */
final class PortalEnvironment
{
    private const MODULE = 'vendor.xmldoc';

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
        return self::isCloud() ? 'vendor.xmldoc.cloud' : 'vendor.xmldoc';
    }

    public static function isCloudRuntimeReady(): bool
    {
        if (!self::isCloud()) {
            return true;
        }

        return \Bitrix\Main\ModuleManager::isModuleInstalled('vendor.xmldoc.cloud');
    }
}
