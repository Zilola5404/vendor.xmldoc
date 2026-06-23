<?php

namespace Vendor\Xmldoc\Cloud\Crm;

use Bitrix\Crm\Service\Container;
use Bitrix\Main\Loader;
use Vendor\Xmldoc\Crm\CrmEntityWriter;
use Vendor\Xmldoc\DataCollector;
use Vendor\Xmldoc\Install\UserFieldInstaller;
use Vendor\Xmldoc\Logger;

/** Запись UF_UPD_FILE через CRM Service API (облако). */
final class CloudCrmEntityWriter
{
    public static function attachUpdFile(
        string $entityType,
        int $entityId,
        int $entityTypeId,
        int $fileId
    ): bool {
        if ($fileId <= 0) {
            return false;
        }

        Loader::includeModule('crm');

        if ($entityType === DataCollector::TYPE_DEAL) {
            $entityTypeId = \CCrmOwnerType::Deal;
        }

        $fileValue = CrmEntityWriter::buildUserFieldFileValue($fileId);
        if ($fileValue === null) {
            self::logError($entityType, $entityId, 'Не удалось подготовить дескриптор файла (file_id=' . $fileId . ')');

            return false;
        }

        return self::updateDynamicItem($entityType, $entityId, $entityTypeId, $fileValue, $fileId);
    }

    /**
     * @param array<string, mixed>|int $fileValue
     */
    private static function updateDynamicItem(
        string $entityType,
        int $entityId,
        int $entityTypeId,
        array|int $fileValue,
        ?int $sourceFileId = null
    ): bool {
        $factory = Container::getInstance()->getFactory($entityTypeId);
        if ($factory === null) {
            self::logError(
                $entityType,
                $entityId,
                'Factory не найден (entityTypeId=' . $entityTypeId . '). Проверьте smart_invoice_type_id.'
            );

            return false;
        }

        if (!$factory->getFieldsCollection()->hasField('UF_UPD_FILE')) {
            $ufEntity = class_exists(UserFieldInstaller::class)
                ? UserFieldInstaller::resolveUserFieldEntityId($entityTypeId)
                : ('CRM_' . $entityTypeId);

            self::logError(
                $entityType,
                $entityId,
                'Поле UF_UPD_FILE отсутствует (entityTypeId=' . $entityTypeId . ', UF=' . $ufEntity . ')'
            );

            return false;
        }

        $item = $factory->getItem($entityId);
        if ($item === null) {
            self::logError($entityType, $entityId, 'Элемент CRM не найден (id=' . $entityId . ')');

            return false;
        }

        $valueForField = self::resolveFileFieldValue($factory, $fileValue, $sourceFileId);
        $item->set('UF_UPD_FILE', $valueForField);

        $result = $factory->getUpdateOperation($item)->disableAllChecks()->launch();
        if (!$result->isSuccess()) {
            self::logError($entityType, $entityId, implode('; ', $result->getErrorMessages()));

            return false;
        }

        return true;
    }

    /**
     * @param array<string, mixed>|int $fileValue
     * @return array<string, mixed>|int
     */
    private static function resolveFileFieldValue(
        \Bitrix\Crm\Service\Factory $factory,
        array|int $fileValue,
        ?int $sourceFileId
    ): array|int {
        if (!is_array($fileValue)) {
            return $fileValue;
        }

        $field = $factory->getFieldsCollection()->getField('UF_UPD_FILE');
        if ($field === null) {
            return $fileValue;
        }

        try {
            $uploader = Container::getInstance()->getFileUploader();
            if (method_exists($uploader, 'saveFilePersistently')) {
                $persistedId = $uploader->saveFilePersistently($field, $fileValue);
                if ($persistedId !== null && (int)$persistedId > 0) {
                    return (int)$persistedId;
                }
            }
        } catch (\Throwable) {
            // fallback — массив или исходный file_id
        }

        if ($sourceFileId !== null && $sourceFileId > 0) {
            return $sourceFileId;
        }

        return $fileValue;
    }

    private static function logError(string $entityType, int $entityId, string $message): void
    {
        Logger::write(
            $entityType,
            $entityId,
            Logger::STATUS_ERROR,
            'Ошибка записи UF_UPD_FILE: ' . $message
        );
    }
}
