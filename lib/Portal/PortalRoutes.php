<?php

namespace Ooofix\Xmlupd\Portal;

/** URL раздела «XML Документы» на портале (CRM-раздел Bitrix24). */
final class PortalRoutes
{
    public const SECTION_ID = 'OOOFIX_XMLUPD';

    /** Основной путь в CRM-разделе портала. */
    public const BASE_PATH = '/crm/ooofix_xmlupd/';

    /** Старый CRM-путь (редирект при обновлении). */
    public const LEGACY_CRM_BASE_PATH = '/crm/xml_documents/';

    /** Промежуточный CRM-путь v1.5.x (редирект при обновлении). */
    public const LEGACY_CRM_VENDOR_PATH = '/crm/ooofix_vendor_xml/';

    /** Старый корневой путь (редирект при обновлении). */
    public const LEGACY_BASE_PATH = '/xml_documents/';

    public const MODULE_PUBLIC_REL = 'modules/ooofix.xmlupd/install/public/xml_documents';

    public static function siteDir(): string
    {
        $dir = defined('SITE_DIR') ? (string)SITE_DIR : '/';

        return str_ends_with($dir, '/') ? $dir : $dir . '/';
    }

    public static function base(): string
    {
        return self::siteDir() . ltrim(self::BASE_PATH, '/');
    }

    public static function documents(): string
    {
        return self::base() . 'documents/';
    }

    public static function settings(): string
    {
        return self::base() . 'settings/';
    }

    public static function logs(): string
    {
        return self::base() . 'logs/';
    }

    /**
     * @return list<array{id: string, title: string, url: string}>
     */
    public static function sections(): array
    {
        return [
            ['id' => 'documents', 'title' => 'Документы', 'url' => self::documents()],
            ['id' => 'settings',  'title' => 'Настройки', 'url' => self::settings()],
            ['id' => 'logs',      'title' => 'Логи', 'url' => self::logs()],
        ];
    }
}
