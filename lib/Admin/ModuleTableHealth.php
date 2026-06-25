<?php

namespace Ooofix\Xmlupd\Admin;

use Bitrix\Main\Application;

/** Проверка таблиц модуля для публичных страниц. */
final class ModuleTableHealth
{
    public static function isLogTableReady(): bool
    {
        return self::tableExists('b_xmldoc_log');
    }

    public static function isDocumentTableReady(): bool
    {
        return self::tableExists('b_xmldoc_document');
    }

    private static function tableExists(string $table): bool
    {
        try {
            return Application::getConnection()->isTableExists($table);
        } catch (\Throwable) {
            return false;
        }
    }
}
