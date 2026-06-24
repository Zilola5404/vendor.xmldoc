<?php

namespace Vendor\Xmldoc\Event;

use Vendor\Xmldoc\ModuleInfo;

/** Пункты меню админки */
class AdminMenu
{
    public static function onBuildGlobalMenu(array &$globalMenu, array &$moduleMenu): void
    {
        global $APPLICATION;

        if ($APPLICATION->GetGroupRight('ooofix.vendor.xml') < 'R') {
            return;
        }

        $moduleMenu[] = [
            'parent_menu' => 'global_menu_settings',
            'section'     => 'ooofix_vendor_xml',
            'sort'        => 1200,
            'text'        => 'XML УПД',
            'title'       => ModuleInfo::MODULE_TITLE,
            'icon'        => 'fileman_menu_icon',
            'page_icon'   => 'fileman_page_icon',
            'items_id'    => 'menu_ooofix_vendor_xml',
            'items'       => [
                [
                    'text'     => 'Настройки',
                    'url'      => 'settings.php?mid=ooofix.vendor.xml&lang=' . LANGUAGE_ID,
                    'title'    => 'Настройки: ' . ModuleInfo::MODULE_TITLE,
                    'more_url' => [],
                ],
                [
                    'text'     => 'История документов',
                    'url'      => 'ooofix_vendor_xml_documents.php?lang=' . LANGUAGE_ID,
                    'title'    => 'Реестр версий УПД',
                    'more_url' => [],
                ],
                [
                    'text'     => 'Журнал генерации',
                    'url'      => 'ooofix_vendor_xml_log.php?lang=' . LANGUAGE_ID,
                    'title'    => 'Лог b_xmldoc_log',
                    'more_url' => [],
                ],
            ],
        ];
    }
}
