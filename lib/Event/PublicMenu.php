<?php

namespace Ooofix\Xmlupd\Event;

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Ooofix\Xmlupd\Admin\ModuleAccess;
use Ooofix\Xmlupd\Portal\PortalRoutes;

/** Раздел «XML Документы» в левом меню портала. */
final class PublicMenu
{
    /** @param array<int, array<string, mixed>> $arResult */
    public static function onBuildMenu(array &$arResult): void
    {
        if (!Loader::includeModule('ooofix.xmlupd') || !ModuleAccess::canRead()) {
            return;
        }

        Loc::loadMessages(__FILE__);

        foreach ($arResult as $item) {
            if (($item[0] ?? '') === 'XML Документы') {
                return;
            }
        }

        $arResult[] = [
            Loc::getMessage('OOOFIX_XMLUPD_PUBLIC_MENU_ROOT') ?: 'XML Документы',
            PortalRoutes::base(),
            [],
            ['ICON' => 'fileman_menu_icon'],
            '',
            [
                [Loc::getMessage('OOOFIX_XMLUPD_PUBLIC_MENU_DOCUMENTS') ?: 'Документы', PortalRoutes::documents()],
                [Loc::getMessage('OOOFIX_XMLUPD_PUBLIC_MENU_SETTINGS') ?: 'Настройки', PortalRoutes::settings()],
                [Loc::getMessage('OOOFIX_XMLUPD_PUBLIC_MENU_LOGS') ?: 'Логи', PortalRoutes::logs()],
            ],
        ];
    }
}
