<?php

namespace Ooofix\Xmlupd\Install;

use Ooofix\Xmlupd\Portal\PortalRoutes;

/**
 * Публичные страницы раздела в DOCUMENT_ROOT.
 * Логика страниц — в install/public/xml_documents/ модуля, в корне сайта — прокси-заглушки.
 */
final class SiteSectionInstaller
{
    /** @var array<string, string> */
    private const SECTIONS = [
        ''          => 'XML Документы',
        'documents' => 'Документы XML',
        'settings'  => 'Настройки XML',
        'logs'      => 'Логи XML',
    ];

    public static function install(): void
    {
        self::removePath(PortalRoutes::LEGACY_BASE_PATH);
        self::removePath(PortalRoutes::LEGACY_CRM_BASE_PATH);
        self::removePath(PortalRoutes::LEGACY_CRM_VENDOR_PATH);

        foreach (array_keys(self::SECTIONS) as $dir) {
            self::installSection(PortalRoutes::BASE_PATH, $dir);
        }

        self::installLegacyRedirects(PortalRoutes::LEGACY_BASE_PATH);
        self::installLegacyRedirects(PortalRoutes::LEGACY_CRM_BASE_PATH);
        self::installLegacyRedirects(PortalRoutes::LEGACY_CRM_VENDOR_PATH);
    }

    public static function uninstall(): void
    {
        self::removePath(PortalRoutes::BASE_PATH);
        self::removePath(PortalRoutes::LEGACY_BASE_PATH);
        self::removePath(PortalRoutes::LEGACY_CRM_BASE_PATH);
        self::removePath(PortalRoutes::LEGACY_CRM_VENDOR_PATH);
    }

    private static function removePath(string $webPath): void
    {
        $relative = '/' . trim($webPath, '/');
        $absolute = $_SERVER['DOCUMENT_ROOT'] . $relative;

        if (is_dir($absolute)) {
            DeleteDirFilesEx($relative);
        }
    }

    private static function installSection(string $rootPath, string $dir): void
    {
        $relativePath = rtrim($rootPath, '/') . ($dir !== '' ? '/' . $dir : '');
        $absolutePath = $_SERVER['DOCUMENT_ROOT'] . $relativePath;

        if (!is_dir($absolutePath)) {
            mkdir($absolutePath, BX_DIR_PERMISSIONS, true);
        }

        file_put_contents(
            $absolutePath . '/index.php',
            self::pageStubPhp($dir !== '' ? $dir : 'index')
        );
    }

    private static function installLegacyRedirects(string $legacyRootPath): void
    {
        $legacyRoot = rtrim($legacyRootPath, '/');

        foreach (array_keys(self::SECTIONS) as $dir) {
            $relativePath = $legacyRoot . ($dir !== '' ? '/' . $dir : '');
            $absolutePath = $_SERVER['DOCUMENT_ROOT'] . $relativePath;

            if (!is_dir($absolutePath)) {
                mkdir($absolutePath, BX_DIR_PERMISSIONS, true);
            }

            file_put_contents(
                $absolutePath . '/index.php',
                self::legacyRedirectPhp($dir !== '' ? $dir : 'index')
            );
        }
    }

    private static function modulePublicRel(): string
    {
        return PortalRoutes::MODULE_PUBLIC_REL;
    }

    private static function pageStubPhp(string $section): string
    {
        $moduleBase = self::modulePublicRel();
        $sectionPath = $section === 'index' ? 'index.php' : $section . '/index.php';

        return <<<PHP
<?php
require \$_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';
\$moduleRel = '{$moduleBase}';
\$moduleBase = getLocalPath(\$moduleRel);
if (!is_string(\$moduleBase) || \$moduleBase === '') {
    \$moduleBase = '/local/' . \$moduleRel;
}
\$bootstrapPath = \$_SERVER['DOCUMENT_ROOT'] . \$moduleBase . '/bootstrap.php';
if (!is_file(\$bootstrapPath)) {
    require \$_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php';
    ShowError('Обновите модуль ooofix.xmlupd до последней версии.');
    require \$_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php';
    return;
}
require \$bootstrapPath;
\$target = \$_SERVER['DOCUMENT_ROOT'] . \$moduleBase . '/{$sectionPath}';
if (!is_file(\$target)) {
    require \$_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php';
    ShowError('Обновите модуль ooofix.xmlupd до последней версии.');
    require \$_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php';
    return;
}
require \$target;
PHP;
    }

    private static function legacyRedirectPhp(string $section): string
    {
        $target = match ($section) {
            'settings'  => 'crm/ooofix_xmlupd/settings/',
            'logs'      => 'crm/ooofix_xmlupd/logs/',
            default     => 'crm/ooofix_xmlupd/documents/',
        };

        return <<<PHP
<?php
require \$_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';
\$siteDir = defined('SITE_DIR') ? (string)SITE_DIR : '/';
if (!str_ends_with(\$siteDir, '/')) {
    \$siteDir .= '/';
}
LocalRedirect(\$siteDir . '{$target}');
PHP;
    }
}
