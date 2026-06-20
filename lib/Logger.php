<?php

namespace Vendor\Xmldoc;

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
            global $DB;

            $sql = sprintf(
                "INSERT INTO b_xmldoc_log (ENTITY_TYPE, ENTITY_ID, STATUS, MESSAGE, CREATED_AT)
                 VALUES ('%s', %d, '%s', '%s', %s)",
                $DB->ForSql($entityType),
                $entityId,
                $DB->ForSql($status),
                $DB->ForSql($message),
                $DB->CharToDateFunction(date('Y-m-d H:i:s'), 'FULL')
            );

            $DB->Query($sql);
        } catch (\Throwable) {
            // Лог не должен прерывать генерацию
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function fetchList(int $limit = 100, ?string $entityType = null, ?int $entityId = null): array
    {
        global $DB;

        $limit = max(1, min(500, $limit));
        $where = '1=1';

        if ($entityType !== null && $entityType !== '') {
            $where .= " AND ENTITY_TYPE='" . $DB->ForSql($entityType) . "'";
        }
        if ($entityId !== null && $entityId > 0) {
            $where .= ' AND ENTITY_ID=' . (int)$entityId;
        }

        try {
            $rows = [];
            $res = $DB->Query(
                "SELECT * FROM b_xmldoc_log WHERE {$where} ORDER BY ID DESC LIMIT " . $limit
            );
            while ($row = $res->Fetch()) {
                $rows[] = $row;
            }

            return $rows;
        } catch (\Throwable) {
            return [];
        }
    }
}
