<?php

namespace Ooofix\Xmlupd\Install;

use Bitrix\Main\Application;

/** Права на модуль по умолчанию — без них публичная страница недоступна. */
final class ModuleRightsInstaller
{
    public static function install(string $moduleId): void
    {
        if ($moduleId === '') {
            return;
        }

        $connection = Application::getConnection();
        $sqlHelper = $connection->getSqlHelper();
        $safeModuleId = $sqlHelper->forSql($moduleId);

        try {
            $connection->queryExecute(
                "INSERT INTO b_module_group (MODULE_ID, GROUP_ID, G_ACCESS)
                 VALUES ('{$safeModuleId}', 1, 'W')
                 ON DUPLICATE KEY UPDATE G_ACCESS = 'W'"
            );
        } catch (\Throwable) {
            try {
                $connection->queryExecute(
                    "INSERT INTO b_module_group (MODULE_ID, GROUP_ID, SITE_ID, G_ACCESS)
                     VALUES ('{$safeModuleId}', 1, '', 'W')
                     ON DUPLICATE KEY UPDATE G_ACCESS = 'W'"
                );
            } catch (\Throwable) {
                // Права можно выдать вручную в админке
            }
        }
    }
}
