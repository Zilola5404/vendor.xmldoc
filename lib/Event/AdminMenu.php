<?php

namespace Vendor\Xmldoc\Event;

/** Пункты меню админки */
class AdminMenu
{
    public static function onBuildGlobalMenu(array &$globalMenu, array &$moduleMenu): void
    {
        global $APPLICATION;

        if ($APPLICATION->GetGroupRight('vendor.xml') < 'R') {
            return;
        }

        $moduleMenu[] = [
            'parent_menu' => 'global_menu_settings',
            'section'     => 'vendor_xml',
            'sort'        => 1200,
            'text'        => 'XML УПД',
            'title'       => 'Генерация XML УПД',
            'icon'        => 'fileman_menu_icon',
            'page_icon'   => 'fileman_page_icon',
            'items_id'    => 'menu_vendor_xml',
            'items'       => [
                [
                    'text'     => 'Настройки',
                    'url'      => 'settings.php?mid=vendor.xml&lang=' . LANGUAGE_ID,
                    'title'    => 'Настройки модуля vendor.xml',
                    'more_url' => [],
                ],
                [
                    'text'     => 'История документов',
                    'url'      => 'vendor_xml_documents.php?lang=' . LANGUAGE_ID,
                    'title'    => 'Реестр версий УПД',
                    'more_url' => [],
                ],
                [
                    'text'     => 'Журнал генерации',
                    'url'      => 'vendor_xml_log.php?lang=' . LANGUAGE_ID,
                    'title'    => 'Лог b_xmldoc_log',
                    'more_url' => [],
                ],
            ],
        ];
    }
}
