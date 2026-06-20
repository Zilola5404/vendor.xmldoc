<?php

namespace Vendor\Xmldoc\Crm;

use Bitrix\Main\Loader;

/**
 * Загрузка файла на Диск CRM (Bitrix\Disk) с fallback на CFile.
 */
final class DiskStorage
{
    /**
     * Сохраняет файл и возвращает ID b_file для UF_UPD_FILE.
     *
     * @return array{fileId: int, diskUploaded: bool}
     */
    public static function save(
        string $storageName,
        string $content,
        int $entityTypeId,
        int $entityId,
        string $moduleId = 'vendor.xmldoc'
    ): array {
        $diskFileId = self::uploadToEntityDisk($storageName, $content, $entityTypeId, $entityId);
        if ($diskFileId > 0) {
            return ['fileId' => $diskFileId, 'diskUploaded' => true];
        }

        $fileId = (int)\CFile::SaveFile([
            'name'      => $storageName,
            'type'      => 'application/xml',
            'content'   => $content,
            'MODULE_ID' => $moduleId,
        ], 'xmldoc');

        return ['fileId' => $fileId, 'diskUploaded' => false];
    }

    private static function uploadToEntityDisk(
        string $storageName,
        string $content,
        int $entityTypeId,
        int $entityId
    ): int {
        if (!Loader::includeModule('disk') || !Loader::includeModule('crm')) {
            return 0;
        }

        if (!class_exists(\Bitrix\Crm\Integration\DiskManager::class)) {
            return 0;
        }

        $tmpPath = \CTempFile::GetFileName(bx_basename($storageName));
        \CheckDirPath($tmpPath);

        if (@file_put_contents($tmpPath, $content) === false) {
            return 0;
        }

        $fileArray = \CFile::MakeFileArray($tmpPath);
        if (!is_array($fileArray)) {
            return 0;
        }

        $fileArray['name'] = $storageName;
        $fileArray['type'] = 'application/xml';

        try {
            $folder = \Bitrix\Crm\Integration\DiskManager::getFolderForUploadedFiles($entityTypeId, $entityId);
            if ($folder === null) {
                return 0;
            }

            global $USER;
            $userId = ($USER instanceof \CUser && $USER->IsAuthorized()) ? (int)$USER->GetID() : 1;

            $uploaded = $folder->uploadFile($fileArray, ['CREATED_BY' => $userId > 0 ? $userId : 1], true);
            if ($uploaded === null) {
                return 0;
            }

            return (int)$uploaded->getFileId();
        } catch (\Throwable) {
            return 0;
        }
    }
}
