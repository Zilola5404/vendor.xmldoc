<?php

namespace Vendor\Xmldoc\Event;

use Bitrix\Main\Loader;
use Bitrix\Main\Page\Asset;
use Vendor\Xmldoc\Config;
use Vendor\Xmldoc\ModuleInfo;

/** Подключение кнопки «Сформировать УПД» на страницах CRM */
class Ui
{
    public static function onProlog(): void
    {
        self::bootAssets();
    }

    public static function onEpilog(): void
    {
        self::bootAssets();
    }

    private static function bootAssets(): void
    {
        static $loaded = false;
        if ($loaded) {
            return;
        }

        if (!self::isCrmDetailPage()) {
            return;
        }

        if (!Loader::includeModule('vendor.xmldoc') || !Loader::includeModule('crm')) {
            return;
        }

        $entity = self::detectEntity();
        if ($entity === null) {
            return;
        }

        $loaded = true;

        $asset = Asset::getInstance();
        $asset->addJs(self::resolveJsPath());

        $config = [
            'entityType'  => $entity['type'],
            'entityId'    => $entity['id'],
            'ajaxUrl'     => self::resolveAjaxUrl(),
            'sessid'      => bitrix_sessid(),
            'smartTypeId' => Config::smartInvoiceTypeId(),
            'jsVersion'   => ModuleInfo::version(),
        ];

        $asset->addString(
            '<script>window.XMLDOC_CONFIG = ' . \CUtil::PhpToJSObject($config) . ';</script>',
            true,
            \Bitrix\Main\Page\AssetLocation::AFTER_JS
        );
    }

    private static function moduleVersion(): string
    {
        return ModuleInfo::version();
    }

    private static function resolveJsPath(): string
    {
        $siteDir = self::siteDir();
        $version = self::moduleVersion();

        // Всегда из исходников модуля — актуальный JS без кеша /bitrix/js/
        if (is_file($_SERVER['DOCUMENT_ROOT'] . '/local/modules/vendor.xmldoc/install/js/generate.js')) {
            return $siteDir . 'local/modules/vendor.xmldoc/install/js/generate.js?v=' . rawurlencode($version);
        }

        if (is_file($_SERVER['DOCUMENT_ROOT'] . '/bitrix/js/vendor/xmldoc/generate.js')) {
            return $siteDir . 'bitrix/js/vendor/xmldoc/generate.js?v=' . rawurlencode($version);
        }

        return $siteDir . 'local/modules/vendor.xmldoc/install/js/generate.js?v=' . rawurlencode($version);
    }

    private static function resolveAjaxUrl(): string
    {
        $siteDir = self::siteDir();
        if (is_file($_SERVER['DOCUMENT_ROOT'] . '/local/modules/vendor.xmldoc/ajax/generate.php')) {
            return $siteDir . 'local/modules/vendor.xmldoc/ajax/generate.php';
        }

        if (is_file($_SERVER['DOCUMENT_ROOT'] . '/bitrix/tools/vendor_xmldoc_generate.php')) {
            return $siteDir . 'bitrix/tools/vendor_xmldoc_generate.php';
        }

        return $siteDir . 'local/modules/vendor.xmldoc/ajax/generate.php';
    }

    private static function siteDir(): string
    {
        $dir = defined('SITE_DIR') ? (string)SITE_DIR : '/';

        return str_ends_with($dir, '/') ? $dir : $dir . '/';
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
