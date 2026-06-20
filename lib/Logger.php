<?php

namespace Vendor\Xmldoc;

use Bitrix\Main\Application;
use Bitrix\Main\Type\DateTime;

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
            $connection = Application::getConnection();
            $sqlHelper = $connection->getSqlHelper();
            $createdAt = $sqlHelper->getDateTimeFunction(new DateTime());

            $connection->queryExecute(
                "INSERT INTO b_xmldoc_log (ENTITY_TYPE, ENTITY_ID, STATUS, MESSAGE, CREATED_AT)
                 VALUES (?, ?, ?, ?, {$createdAt})",
                [$entityType, $entityId, $status, $message]
            );
        } catch (\Throwable) {
            // Лог не должен прерывать генерацию
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function fetchList(int $limit = 100, ?string $entityType = null, ?int $entityId = null): array
    {
        $limit = max(1, min(500, $limit));

        try {
            $connection = Application::getConnection();
            $sqlHelper = $connection->getSqlHelper();

            $where = '1=1';
            $params = [];

            if ($entityType !== null && $entityType !== '') {
                $where .= ' AND ENTITY_TYPE = ?';
                $params[] = $entityType;
            }
            if ($entityId !== null && $entityId > 0) {
                $where .= ' AND ENTITY_ID = ?';
                $params[] = $entityId;
            }

            $params[] = $limit;

            $rows = [];
            $result = $connection->query(
                "SELECT * FROM b_xmldoc_log WHERE {$where} ORDER BY ID DESC LIMIT ?",
                $params
            );

            while ($row = $result->fetch()) {
                $rows[] = $row;
            }

            return $rows;
        } catch (\Throwable) {
            return [];
        }
    }
}
