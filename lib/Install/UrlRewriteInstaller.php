<?php

namespace Ooofix\Xmlupd\Install;

use Bitrix\Main\UrlRewriter;
use Ooofix\Xmlupd\Portal\PortalRoutes;

/** Маршруты раздела /crm/ooofix_xmlupd/ и legacy-пути. */
final class UrlRewriteInstaller
{
    /** @return list<array{id: string, condition: string, path: string}> */
    private static function rules(): array
    {
        $base = PortalRoutes::MODULE_PUBLIC_REL;

        return array_merge(
            self::sectionRules('/crm/ooofix_xmlupd', 'ooofix.xmlupd.portal', $base),
            self::sectionRules('/crm/ooofix_vendor_xml', 'ooofix.xmlupd.portal.legacy.vendor', $base),
            self::sectionRules('/crm/xml_documents', 'ooofix.xmlupd.portal.legacy.crm', $base),
            self::sectionRules('/xml_documents', 'ooofix.xmlupd.portal.legacy', $base),
        );
    }

    /**
     * @return list<array{id: string, condition: string, path: string}>
     */
    private static function sectionRules(string $webPrefix, string $idPrefix, string $moduleBase): array
    {
        $prefix = rtrim($webPrefix, '/');

        return [
            [
                'id'        => $idPrefix . '.documents',
                'condition' => '#^' . $prefix . '/documents/#',
                'path'      => self::modulePath($moduleBase . '/documents/index.php'),
            ],
            [
                'id'        => $idPrefix . '.settings',
                'condition' => '#^' . $prefix . '/settings/#',
                'path'      => self::modulePath($moduleBase . '/settings/index.php'),
            ],
            [
                'id'        => $idPrefix . '.logs',
                'condition' => '#^' . $prefix . '/logs/#',
                'path'      => self::modulePath($moduleBase . '/logs/index.php'),
            ],
            [
                'id'        => $idPrefix . '.root',
                'condition' => '#^' . $prefix . '/#',
                'path'      => self::modulePath($moduleBase . '/index.php'),
            ],
        ];
    }

    public static function install(string $moduleId): void
    {
        unset($moduleId);

        if (!class_exists(UrlRewriter::class)) {
            return;
        }

        self::removeLegacyRules();

        foreach (self::siteIds() as $siteId) {
            self::uninstallForSite($siteId);

            foreach (self::rules() as $rule) {
                if ($rule['path'] === '') {
                    continue;
                }

                UrlRewriter::add($siteId, [
                    'CONDITION' => $rule['condition'],
                    'RULE'      => '',
                    'ID'        => $rule['id'],
                    'PATH'      => $rule['path'],
                    'SORT'      => 5,
                ]);
            }
        }
    }

    public static function uninstall(): void
    {
        if (!class_exists(UrlRewriter::class)) {
            return;
        }

        foreach (self::siteIds() as $siteId) {
            self::uninstallForSite($siteId);
        }

        self::removeLegacyRules();
    }

    private static function removeLegacyRules(): void
    {
        if (!class_exists(UrlRewriter::class)) {
            return;
        }

        $legacyIds = [
            'ooofix.xmlupd.portal.documents',
            'ooofix.xmlupd.portal.settings',
            'ooofix.xmlupd.portal.logs',
            'ooofix.xmlupd.portal.root',
            'ooofix.xmlupd.portal.legacy.crm.documents',
            'ooofix.xmlupd.portal.legacy.crm.settings',
            'ooofix.xmlupd.portal.legacy.crm.logs',
            'ooofix.xmlupd.portal.legacy.crm.root',
            'ooofix.xmlupd.portal.legacy.documents',
            'ooofix.xmlupd.portal.legacy.settings',
            'ooofix.xmlupd.portal.legacy.logs',
            'ooofix.xmlupd.portal.legacy.root',
            'ooofix.xmlupd.crm.legacy',
        ];

        foreach (self::siteIds() as $siteId) {
            foreach ($legacyIds as $id) {
                UrlRewriter::delete($siteId, ['ID' => $id]);
            }

            UrlRewriter::delete($siteId, ['CONDITION' => '#^/xml_documents/#']);
            UrlRewriter::delete($siteId, ['CONDITION' => '#^/crm/xml_documents/#']);
        }
    }

    private static function uninstallForSite(string $siteId): void
    {
        foreach (self::rules() as $rule) {
            UrlRewriter::delete($siteId, ['ID' => $rule['id']]);
            if ($rule['path'] !== '') {
                UrlRewriter::delete($siteId, ['ID' => $rule['id'], 'PATH' => $rule['path']]);
            }
        }
    }

    private static function modulePath(string $relative): string
    {
        $path = getLocalPath($relative);
        if (is_string($path) && $path !== '') {
            return $path;
        }

        $local = '/local/' . ltrim($relative, '/');
        if (is_file($_SERVER['DOCUMENT_ROOT'] . $local)) {
            return $local;
        }

        return '';
    }

    /** @return list<string> */
    private static function siteIds(): array
    {
        if (!class_exists('CSite')) {
            return ['s1'];
        }

        $ids = [];
        $rs = \CSite::GetList('sort', 'asc', ['ACTIVE' => 'Y']);
        while ($site = $rs->Fetch()) {
            if (!empty($site['LID'])) {
                $ids[] = (string)$site['LID'];
            }
        }

        return $ids !== [] ? $ids : ['s1'];
    }
}
