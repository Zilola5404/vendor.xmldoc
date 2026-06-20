<?php

namespace Vendor\Xmldoc;

use Bitrix\Main\Application;
use Bitrix\Main\Type\DateTime;

/** Реестр версий документов b_xmldoc_document (метаданные + file_id каждой версии) */
class DocumentRegistry
{
    public static function getNextVersion(string $entityType, int $entityId): int
    {
        try {
            $connection = Application::getConnection();
            $row = $connection->query(
                'SELECT MAX(VERSION) AS V FROM b_xmldoc_document WHERE ENTITY_TYPE = ? AND ENTITY_ID = ?',
                [$entityType, $entityId]
            )->fetch();

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
        if (!in_array($docStatus, DocumentStatus::all(), true)) {
            $docStatus = DocumentStatus::GENERATED;
        }

        try {
            $connection = Application::getConnection();
            $sqlHelper = $connection->getSqlHelper();
            $createdAt = $sqlHelper->getDateTimeFunction(new DateTime());

            $connection->queryExecute(
                "INSERT INTO b_xmldoc_document
                    (ENTITY_TYPE, ENTITY_ID, DOC_NUMBER, FILE_NAME, FILE_ID, VERSION, ENCODING, FILE_HASH, DOC_STATUS, CREATED_AT)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, {$createdAt})",
                [
                    $entityType,
                    $entityId,
                    (string)$docNumber,
                    $fileName,
                    $fileId !== null && $fileId > 0 ? $fileId : null,
                    $version,
                    $encoding,
                    $fileHash,
                    $docStatus,
                ]
            );
        } catch (\Throwable) {
            // Реестр не должен блокировать выдачу файла пользователю
        }
    }

    public static function updateStatus(int $registryId, string $status): bool
    {
        if (!in_array($status, DocumentStatus::all(), true)) {
            return false;
        }

        try {
            Application::getConnection()->queryExecute(
                'UPDATE b_xmldoc_document SET DOC_STATUS = ? WHERE ID = ?',
                [$status, $registryId]
            );

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
        $limit = max(1, min(500, $limit));

        try {
            $connection = Application::getConnection();
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
                "SELECT * FROM b_xmldoc_document WHERE {$where} ORDER BY ID DESC LIMIT ?",
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
