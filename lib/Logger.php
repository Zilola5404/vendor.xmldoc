<?php

namespace Ooofix\Xmlupd;

use Bitrix\Main\Application;
use Ooofix\Xmlupd\Internal\Db;

/** Журнал операций в b_xmldoc_log */
class Logger
{
    public const STATUS_STARTED  = 'started';
    public const STATUS_SUCCESS  = 'success';
    public const STATUS_ERROR    = 'error';
    public const STATUS_VALIDATE = 'validate_error';

    public static function write(string $entityType, int $entityId, string $status, string $message = ''): void
    {
        try {
            Db::insert('b_xmldoc_log', [
                'ENTITY_TYPE' => $entityType,
                'ENTITY_ID'   => $entityId,
                'STATUS'      => $status,
                'MESSAGE'     => $message,
                'CREATED_AT'  => new \Bitrix\Main\Type\DateTime(),
            ]);
        } catch (\Throwable) {
            try {
                $helper = Application::getConnection()->getSqlHelper();
                Application::getConnection()->queryExecute(
                    'INSERT INTO b_xmldoc_log (ENTITY_TYPE, ENTITY_ID, STATUS, MESSAGE, CREATED_AT) VALUES ('
                    . "'" . $helper->forSql($entityType) . "', "
                    . (int)$entityId . ", "
                    . "'" . $helper->forSql($status) . "', "
                    . "'" . $helper->forSql($message) . "', "
                    . Db::nowExpression()
                    . ')'
                );
            } catch (\Throwable) {
                // Лог не должен прерывать генерацию
            }
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function fetchList(int $limit = 100, ?string $entityType = null, ?int $entityId = null): array
    {
        $limit = max(1, min(500, $limit));

        try {
            $helper = Application::getConnection()->getSqlHelper();
            $where = '1=1';

            if ($entityType !== null && $entityType !== '') {
                $where .= " AND ENTITY_TYPE = '" . $helper->forSql($entityType) . "'";
            }
            if ($entityId !== null && $entityId > 0) {
                $where .= ' AND ENTITY_ID = ' . (int)$entityId;
            }

            return Db::fetchAll(
                'SELECT * FROM b_xmldoc_log WHERE ' . $where
                . ' ORDER BY ID DESC LIMIT ' . (int)$limit
            );
        } catch (\Throwable) {
            return [];
        }
    }
}
