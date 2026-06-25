<?php

namespace Ooofix\Xmlupd\Event;

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Ooofix\Xmlupd\Admin\ModuleAccess;
use Ooofix\Xmlupd\ModuleInfo;
use Ooofix\Xmlupd\Portal\PortalRoutes;

/** Пункт в горизонтальном меню CRM (ведёт в раздел портала). */
final class CrmMenu
{
    public const ITEM_ID = 'OOOFIX_XMLUPD';
    public const MENU_ID = 'menu_crm_ooofix_xmlupd';

    /** @param array<int, array<string, mixed>> $items */
    public static function onAfterCrmControlPanelBuild(array &$items): void
    {
        ModuleAccess::ensureModuleLoaded();

        if (!Loader::includeModule('ooofix.xmlupd') || !ModuleAccess::canRead()) {
            return;
        }

        Loc::loadMessages(__FILE__);

        foreach ($items as $item) {
            if (($item['ID'] ?? '') === self::ITEM_ID) {
                return;
            }
        }

        $items[] = [
            'ID'      => self::ITEM_ID,
            'MENU_ID' => self::MENU_ID,
            'NAME'    => Loc::getMessage('OOOFIX_XMLUPD_CRM_MENU_NAME') ?: 'XML Документы',
            'TITLE'   => Loc::getMessage('OOOFIX_XMLUPD_CRM_MENU_TITLE') ?: ModuleInfo::MODULE_TITLE,
            'URL'     => PortalRoutes::settings(),
            'SORT'    => 1450,
        ];
    }

    public static function publicUrl(): string
    {
        return PortalRoutes::settings();
    }
}
