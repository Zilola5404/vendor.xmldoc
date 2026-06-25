<?php

namespace Ooofix\Xmlupd\Crm;

use Bitrix\Crm\Service\Container;
use Bitrix\Main\Loader;
use Ooofix\Xmlupd\DataCollector;
use Ooofix\Xmlupd\Install\UserFieldInstaller;
use Ooofix\Xmlupd\Logger;

/** Запись UF_UPD_FILE в карточку CRM (коробка: CCrmDeal + Factory для СП). */
final class CrmEntityWriter
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

        $fileValue = self::buildUserFieldFileValue($fileId);
        if ($fileValue === null) {
            self::logError($entityType, $entityId, 'Не удалось подготовить дескриптор файла (file_id=' . $fileId . ')');

            return false;
        }

        if ($entityType === DataCollector::TYPE_DEAL) {
            $deal = new \CCrmDeal(false);
            $fields = ['UF_UPD_FILE' => $fileValue];
            $options = ['CHECK_PERMISSIONS' => 'N'];
            $result = $deal->Update($entityId, $fields, true, true, $options);

            if (!$result) {
                self::logError($entityType, $entityId, 'CCrmDeal::Update: ' . (string)$deal->LAST_ERROR);

                return false;
            }

            return true;
        }

        if ($entityType === DataCollector::TYPE_SMART_INVOICE) {
            return self::updateDynamicItem($entityType, $entityId, $entityTypeId, $fileValue);
        }

        self::logError($entityType, $entityId, 'Неподдерживаемый тип сущности: ' . $entityType);

        return false;
    }

    /**
     * @param array<string, mixed>|int $fileValue
     */
    private static function updateDynamicItem(
        string $entityType,
        int $entityId,
        int $entityTypeId,
        array|int $fileValue
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

        $item->set('UF_UPD_FILE', $fileValue);

        $result = $factory->getUpdateOperation($item)->disableAllChecks()->launch();
        if (!$result->isSuccess()) {
            self::logError($entityType, $entityId, implode('; ', $result->getErrorMessages()));

            return false;
        }

        return true;
    }

    /**
     * @return array<string, mixed>|int|null
     */
    public static function buildUserFieldFileValue(int $fileId): array|int|null
    {
        if ($fileId <= 0) {
            return null;
        }

        $fileArray = \CFile::MakeFileArray($fileId);
        if (is_array($fileArray) && ($fileArray['tmp_name'] ?? '') !== '') {
            return $fileArray;
        }

        $row = \CFile::GetByID($fileId)->Fetch();
        if (!is_array($row)) {
            return null;
        }

        $relativePath = (string)($row['SRC'] ?? \CFile::GetPath($fileId));
        if ($relativePath === '') {
            return null;
        }

        $absolutePath = (string)(($_SERVER['DOCUMENT_ROOT'] ?? '') . $relativePath);
        if ($absolutePath !== '' && is_file($absolutePath)) {
            $fileArray = \CFile::MakeFileArray($absolutePath);
            if (is_array($fileArray)) {
                $fileArray['name'] = (string)($row['ORIGINAL_NAME'] ?? $row['FILE_NAME'] ?? basename($relativePath));

                return $fileArray;
            }
        }

        return $fileId;
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
