<?php

namespace Vendor\Xmldoc;

/** Реестр версий документов b_xmldoc_document (метаданные + file_id каждой версии) */
class DocumentRegistry
{
    public static function getNextVersion(string $entityType, int $entityId): int
    {
        global $DB;

        try {
            $row = $DB->Query(sprintf(
                "SELECT MAX(VERSION) AS V FROM b_xmldoc_document WHERE ENTITY_TYPE='%s' AND ENTITY_ID=%d",
                $DB->ForSql($entityType),
                $entityId
            ))->Fetch();

            return ((int)($row['V'] ?? 0)) + 1;
        } catch (\Throwable) {
            return 1;
        }
    }

    public static function add(
        string $entityType,
        int $entityId,
        string $fileName,
        ?int $fileId,
        ?string $docNumber,
        int $version,
        string $encoding,
        ?string $fileHash = null,
        string $docStatus = DocumentStatus::GENERATED
    ): void {
        global $DB;

        if (!in_array($docStatus, DocumentStatus::all(), true)) {
            $docStatus = DocumentStatus::GENERATED;
        }

        try {
            $hashSql = $fileHash !== null ? "'" . $DB->ForSql($fileHash) . "'" : 'NULL';

            $sql = sprintf(
                "INSERT INTO b_xmldoc_document
                    (ENTITY_TYPE, ENTITY_ID, DOC_NUMBER, FILE_NAME, FILE_ID, VERSION, ENCODING, FILE_HASH, DOC_STATUS, CREATED_AT)
                 VALUES ('%s', %d, '%s', '%s', %s, %d, '%s', %s, '%s', %s)",
                $DB->ForSql($entityType),
                $entityId,
                $DB->ForSql((string)$docNumber),
                $DB->ForSql($fileName),
                $fileId ? (int)$fileId : 'NULL',
                $version,
                $DB->ForSql($encoding),
                $hashSql,
                $DB->ForSql($docStatus),
                $DB->CharToDateFunction(date('Y-m-d H:i:s'), 'FULL')
            );

            $DB->Query($sql);
        } catch (\Throwable) {
            // Реестр не должен блокировать выдачу файла пользователю
        }
    }

    public static function updateStatus(int $registryId, string $status): bool
    {
        global $DB;

        if (!in_array($status, DocumentStatus::all(), true)) {
            return false;
        }

        try {
            $DB->Query(sprintf(
                "UPDATE b_xmldoc_document SET DOC_STATUS='%s' WHERE ID=%d",
                $DB->ForSql($status),
                $registryId
            ));

            return true;
        } catch (\Throwable) {
            return false;
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
                "SELECT * FROM b_xmldoc_document WHERE {$where} ORDER BY ID DESC LIMIT " . $limit
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
