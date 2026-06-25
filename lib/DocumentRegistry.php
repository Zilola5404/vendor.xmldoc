<?php

namespace Ooofix\Xmlupd;

use Bitrix\Main\Application;
use Bitrix\Main\Type\DateTime;
use Ooofix\Xmlupd\Internal\Db;

/** Реестр версий документов b_xmldoc_document (метаданные + file_id каждой версии) */
class DocumentRegistry
{
    public static function getNextVersion(string $entityType, int $entityId): int
    {
        try {
            $helper = Application::getConnection()->getSqlHelper();
            $rows = Db::fetchAll(
                'SELECT MAX(VERSION) AS V FROM b_xmldoc_document WHERE ENTITY_TYPE = \''
                . $helper->forSql($entityType) . '\' AND ENTITY_ID = ' . (int)$entityId
            );

            return ((int)($rows[0]['V'] ?? 0)) + 1;
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
        string $docStatus = DocumentStatus::GENERATED,
        ?string $xmlFormatVersion = null
    ): void {
        if (!in_array($docStatus, DocumentStatus::all(), true)) {
            $docStatus = DocumentStatus::GENERATED;
        }

        $xmlFormatVersion = $xmlFormatVersion ?? Config::xmlFormatVersion();

        try {
            Db::insert('b_xmldoc_document', [
                'ENTITY_TYPE'        => $entityType,
                'ENTITY_ID'          => $entityId,
                'DOC_NUMBER'         => (string)$docNumber,
                'FILE_NAME'          => $fileName,
                'FILE_ID'            => $fileId !== null && $fileId > 0 ? $fileId : null,
                'VERSION'            => $version,
                'ENCODING'           => $encoding,
                'FILE_HASH'          => $fileHash,
                'DOC_STATUS'         => $docStatus,
                'XML_FORMAT_VERSION' => $xmlFormatVersion,
                'CREATED_AT'         => new DateTime(),
            ]);
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
            $helper = Application::getConnection()->getSqlHelper();
            Application::getConnection()->queryExecute(
                'UPDATE b_xmldoc_document SET DOC_STATUS = \''
                . $helper->forSql($status) . '\' WHERE ID = ' . (int)$registryId
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
            $helper = Application::getConnection()->getSqlHelper();
            $where = '1=1';

            if ($entityType !== null && $entityType !== '') {
                $where .= " AND ENTITY_TYPE = '" . $helper->forSql($entityType) . "'";
            }
            if ($entityId !== null && $entityId > 0) {
                $where .= ' AND ENTITY_ID = ' . (int)$entityId;
            }

            return Db::fetchAll(
                'SELECT * FROM b_xmldoc_document WHERE ' . $where
                . ' ORDER BY ID DESC LIMIT ' . (int)$limit
            );
        } catch (\Throwable) {
            return [];
        }
    }
}
