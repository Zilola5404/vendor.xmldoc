<?php

namespace Ooofix\Xmlupd\Event;

use Ooofix\Xmlupd\ModuleInfo;

/** Пункты меню админки (только административные страницы). */
class AdminMenu
{
    public static function onBuildGlobalMenu(array &$globalMenu, array &$moduleMenu): void
    {
        global $APPLICATION;

        if ($APPLICATION->GetGroupRight('ooofix.xmlupd') < 'R') {
            return;
        }

        $moduleMenu[] = [
            'parent_menu' => 'global_menu_settings',
            'section'     => 'ooofix_xmlupd',
            'sort'        => 1200,
            'text'        => 'XML УПД',
            'title'       => ModuleInfo::MODULE_TITLE,
            'icon'        => 'fileman_menu_icon',
            'page_icon'   => 'fileman_page_icon',
            'items_id'    => 'menu_ooofix_xmlupd',
            'items'       => [
                [
                    'text'     => 'Настройки XML',
                    'url'      => 'settings.php?mid=ooofix.xmlupd&lang=' . LANGUAGE_ID,
                    'title'    => 'Настройки: ' . ModuleInfo::MODULE_TITLE,
                    'more_url' => [],
                ],
                [
                    'text'     => 'История документов',
                    'url'      => 'ooofix_xmlupd_documents.php?lang=' . LANGUAGE_ID,
                    'title'    => 'Реестр версий УПД',
                    'more_url' => [],
                ],
                [
                    'text'     => 'Журнал генерации',
                    'url'      => 'ooofix_xmlupd_log.php?lang=' . LANGUAGE_ID,
                    'title'    => 'Лог b_xmldoc_log',
                    'more_url' => [],
                ],
            ],
        ];
    }
}
