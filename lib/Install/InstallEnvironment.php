<?php

namespace Vendor\Xmldoc\Install;

use Bitrix\Main\Config\Option;
use Vendor\Xmldoc\Cloud\Crm\SmartInvoiceTypeResolver;
use Vendor\Xmldoc\Environment\PortalEnvironment;

/** Определение окружения и post-install настройки (коробка / облако). */
final class InstallEnvironment
{
    public const ENV_BOX = 'box';
    public const ENV_CLOUD = 'cloud';

    public static function detect(): string
    {
        return PortalEnvironment::isCloud() ? self::ENV_CLOUD : self::ENV_BOX;
    }

    public static function apply(string $moduleId): void
    {
        $env = self::detect();
        Option::set($moduleId, 'installed_environment', $env);

        if ($env === self::ENV_CLOUD) {
            self::applyCloud($moduleId);
        } else {
            self::applyBox($moduleId);
        }
    }

    public static function installedEnvironment(string $moduleId): string
    {
        $stored = (string)Option::get($moduleId, 'installed_environment', '');

        return in_array($stored, [self::ENV_BOX, self::ENV_CLOUD], true)
            ? $stored
            : self::detect();
    }

    public static function runtimeLabel(string $moduleId): string
    {
        return self::installedEnvironment($moduleId) === self::ENV_CLOUD
            ? 'облако (CloudGenerateRuntime)'
            : 'коробка (BoxGenerateRuntime)';
    }

    private static function applyCloud(string $moduleId): void
    {
        if (class_exists(SmartInvoiceTypeResolver::class)) {
            $detected = SmartInvoiceTypeResolver::detectFromCrm();
            if ($detected > 0) {
                Option::set($moduleId, 'smart_invoice_type_id', (string)$detected);
            }
        }

        if ((string)Option::get($moduleId, 'crm_adapter', 'auto') === 'auto') {
            Option::set($moduleId, 'crm_adapter', 'cloud');
        }
    }

    private static function applyBox(string $moduleId): void
    {
        if ((int)Option::get($moduleId, 'smart_invoice_type_id', '0') <= 0) {
            Option::set($moduleId, 'smart_invoice_type_id', '31');
        }

        if ((string)Option::get($moduleId, 'crm_adapter', 'auto') === 'auto') {
            Option::set($moduleId, 'crm_adapter', 'onprem');
        }
    }
}
