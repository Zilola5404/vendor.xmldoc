<?php

namespace Ooofix\Xmlupd\Install;

use Bitrix\Main\EventManager;

/** Надёжная очистка обработчиков при удалении модуля. */
final class EventInstaller
{
    public static function uninstallAll(string $moduleId): void
    {
        $manager = EventManager::getInstance();

        $handlers = [
            ['main', 'OnProlog', 'Ooofix\\Xmlupd\\Event\\Ui', 'onProlog'],
            ['main', 'OnEpilog', 'Ooofix\\Xmlupd\\Event\\Ui', 'onEpilog'],
            ['main', 'OnBuildGlobalMenu', 'Ooofix\\Xmlupd\\Event\\AdminMenu', 'onBuildGlobalMenu'],
            ['main', 'OnBuildMenu', 'Ooofix\\Xmlupd\\Event\\PublicMenu', 'onBuildMenu'],
            ['crm', 'OnAfterCrmControlPanelBuild', 'Ooofix\\Xmlupd\\Event\\CrmMenu', 'onAfterCrmControlPanelBuild'],
        ];

        foreach ($handlers as [$fromModule, $event, $class, $method]) {
            $manager->unRegisterEventHandler($fromModule, $event, $moduleId, $class, $method);

            if (method_exists($manager, 'unRegisterEventHandlerCompatible')) {
                $manager->unRegisterEventHandlerCompatible($fromModule, $event, $moduleId, $class, $method);
            }
        }

        self::purgeModuleEvents($moduleId);
    }

    private static function purgeModuleEvents(string $moduleId): void
    {
        $connection = \Bitrix\Main\Application::getConnection();
        $sqlHelper = $connection->getSqlHelper();
        $moduleSql = $sqlHelper->forSql($moduleId);

        $connection->queryExecute(
            "DELETE FROM b_module_to_module WHERE TO_MODULE_ID = '{$moduleSql}'"
        );
    }
}
