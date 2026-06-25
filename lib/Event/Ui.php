<?php

namespace Ooofix\Xmlupd\Event;

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Page\Asset;
use Ooofix\Xmlupd\Admin\ModuleAccess;
use Ooofix\Xmlupd\Config;
use Ooofix\Xmlupd\ModuleInfo;
use Ooofix\Xmlupd\Portal\PortalRoutes;

/** Кнопки CRM: генерация УПД, настройки, пункт меню (JS-резерв). */
class Ui
{
    public static function onProlog(): void
    {
        self::bootCrmMenuAssets();
        self::bootDetailAssets();
    }

    public static function onEpilog(): void
    {
        self::bootCrmMenuAssets();
        self::bootDetailAssets();
    }

    private static function bootCrmMenuAssets(): void
    {
        static $menuLoaded = false;
        if ($menuLoaded || !self::isCrmSection()) {
            return;
        }

        if (!Loader::includeModule('ooofix.xmlupd') || !Loader::includeModule('crm')) {
            return;
        }

        ModuleAccess::ensureModuleLoaded();
        if (!ModuleAccess::canRead()) {
            return;
        }

        $menuLoaded = true;

        $asset = Asset::getInstance();
        $asset->addJs(self::resolveCrmMenuJsPath());

        $menuConfig = [
            'enabled' => true,
            'url'     => PortalRoutes::settings(),
            'name'    => 'XML Документы',
        ];

        $asset->addString(
            '<script>window.OX_UPD_CRM_MENU = ' . \CUtil::PhpToJSObject($menuConfig) . ';</script>',
            true,
            \Bitrix\Main\Page\AssetLocation::AFTER_JS
        );
    }

    private static function bootDetailAssets(): void
    {
        static $detailLoaded = false;
        if ($detailLoaded || !self::isCrmDetailPage()) {
            return;
        }

        if (!Loader::includeModule('ooofix.xmlupd') || !Loader::includeModule('crm')) {
            return;
        }

        ModuleAccess::ensureModuleLoaded();

        $entity = self::detectEntity();
        if ($entity === null) {
            return;
        }

        $detailLoaded = true;

        Loc::loadMessages(__FILE__);

        $canWrite = ModuleAccess::canWrite();
        $canRead = ModuleAccess::canRead();

        $asset = Asset::getInstance();
        $asset->addJs(self::resolveJsPath());

        $config = [
            'entityType'   => $entity['type'],
            'entityId'     => $entity['id'],
            'ajaxUrl'      => self::resolveAjaxUrl(),
            'sessid'       => bitrix_sessid(),
            'smartTypeId'  => Config::smartInvoiceTypeId(),
            'jsVersion'    => ModuleInfo::version(),
            'canGenerate'  => $canWrite && $entity['id'] > 0,
            'canSettings'  => $canRead,
            'settingsUrl'  => PortalRoutes::settings() . (str_contains(PortalRoutes::settings(), '?') ? '&' : '?') . 'IFRAME=Y&IFRAME_TYPE=SIDE_SLIDER',
            'settingsLabel'=> Loc::getMessage('OOOFIX_XMLUPD_UI_SETTINGS_BTN') ?: 'Настройки УПД',
            'generateLabel'=> Loc::getMessage('OOOFIX_XMLUPD_UI_GENERATE_BTN') ?: 'Сформировать УПД',
        ];

        $asset->addString(
            '<script>window.OOOFIX_XMLUPD_CONFIG = ' . \CUtil::PhpToJSObject($config) . ';</script>',
            true,
            \Bitrix\Main\Page\AssetLocation::AFTER_JS
        );
    }

    private static function resolveCrmMenuJsPath(): string
    {
        $siteDir = self::siteDir();
        $version = ModuleInfo::version();

        if (is_file($_SERVER['DOCUMENT_ROOT'] . '/bitrix/js/ooofix/xmlupd/crm-menu.js')) {
            return $siteDir . 'bitrix/js/ooofix/xmlupd/crm-menu.js?v=' . rawurlencode($version);
        }

        return $siteDir . 'local/modules/ooofix.xmlupd/install/js/crm-menu.js?v=' . rawurlencode($version);
    }

    private static function resolveJsPath(): string
    {
        $siteDir = self::siteDir();
        $version = ModuleInfo::version();

        if (is_file($_SERVER['DOCUMENT_ROOT'] . '/bitrix/js/ooofix/xmlupd/generate.js')) {
            return $siteDir . 'bitrix/js/ooofix/xmlupd/generate.js?v=' . rawurlencode($version);
        }

        return $siteDir . 'local/modules/ooofix.xmlupd/install/js/generate.js?v=' . rawurlencode($version);
    }

    private static function resolveAjaxUrl(): string
    {
        $siteDir = self::siteDir();
        if (is_file($_SERVER['DOCUMENT_ROOT'] . '/local/modules/ooofix.xmlupd/ajax/generate.php')) {
            return $siteDir . 'local/modules/ooofix.xmlupd/ajax/generate.php';
        }

        if (is_file($_SERVER['DOCUMENT_ROOT'] . '/bitrix/tools/ooofix_xmlupd_generate.php')) {
            return $siteDir . 'bitrix/tools/ooofix_xmlupd_generate.php';
        }

        return $siteDir . 'local/modules/ooofix.xmlupd/ajax/generate.php';
    }

    private static function siteDir(): string
    {
        $dir = defined('SITE_DIR') ? (string)SITE_DIR : '/';

        return str_ends_with($dir, '/') ? $dir : $dir . '/';
    }

    private static function isCrmSection(): bool
    {
        return str_contains(self::getRequestUri(), '/crm/');
    }

    private static function isCrmDetailPage(): bool
    {
        $uri = self::getRequestUri();

        return str_contains($uri, '/crm/deal/details/')
            || preg_match('#/crm/type/\d+/details/#', $uri) === 1;
    }

    /** @return array{type: string, id: int}|null */
    private static function detectEntity(): ?array
    {
        $uri = self::getRequestUri();

        if (preg_match('#/crm/deal/details/(\d+)#', $uri, $m)) {
            return ['type' => 'deal', 'id' => (int)$m[1]];
        }

        if (str_contains($uri, '/crm/deal/details/')) {
            return ['type' => 'deal', 'id' => 0];
        }

        $smartTypeId = Config::smartInvoiceTypeId();
        if ($smartTypeId > 0 && preg_match('#/crm/type/' . $smartTypeId . '/details/(\d+)#', $uri, $m)) {
            return ['type' => 'smart_invoice', 'id' => (int)$m[1]];
        }

        if ($smartTypeId > 0 && str_contains($uri, '/crm/type/' . $smartTypeId . '/details/')) {
            return ['type' => 'smart_invoice', 'id' => 0];
        }

        return null;
    }

    private static function getRequestUri(): string
    {
        $parts = [
            (string)($_SERVER['REQUEST_URI'] ?? ''),
            (string)($_SERVER['REDIRECT_URL'] ?? ''),
        ];

        global $APPLICATION;
        if (isset($APPLICATION) && is_object($APPLICATION)) {
            $parts[] = (string)$APPLICATION->GetCurPage();
            $parts[] = (string)$APPLICATION->GetCurPageParam();
        }

        return implode(' ', $parts);
    }
}
