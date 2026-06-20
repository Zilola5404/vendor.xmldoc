<?php

namespace Vendor\Xmldoc\Event;

/** Пункты меню админки */
class AdminMenu
{
    public static function onBuildGlobalMenu(array &$globalMenu, array &$moduleMenu): void
    {
        global $APPLICATION;

        if ($APPLICATION->GetGroupRight('vendor.xmldoc') < 'R') {
            return;
        }

        $moduleMenu[] = [
            'parent_menu' => 'global_menu_settings',
            'section'     => 'vendor_xmldoc',
            'sort'        => 1200,
            'text'        => 'XML УПД',
            'title'       => 'Генерация XML УПД',
            'icon'        => 'fileman_menu_icon',
            'page_icon'   => 'fileman_page_icon',
            'items_id'    => 'menu_vendor_xmldoc',
            'items'       => [
                [
                    'text'     => 'Настройки',
                    'url'      => 'settings.php?mid=vendor.xmldoc&lang=' . LANGUAGE_ID,
                    'title'    => 'Настройки модуля vendor.xmldoc',
                    'more_url' => [],
                ],
                [
                    'text'     => 'История документов',
                    'url'      => 'vendor_xmldoc_documents.php?lang=' . LANGUAGE_ID,
                    'title'    => 'Реестр версий УПД',
                    'more_url' => [],
                ],
                [
                    'text'     => 'Журнал генерации',
                    'url'      => 'vendor_xmldoc_log.php?lang=' . LANGUAGE_ID,
                    'title'    => 'Лог b_xmldoc_log',
                    'more_url' => [],
                ],
            ],
        ];
    }
}
