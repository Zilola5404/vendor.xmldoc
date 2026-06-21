<?php

namespace Vendor\Xmldoc;

use Vendor\Xmldoc\Crm\DiskStorage;
use Vendor\Xmldoc\Crm\TimelinePublisher;

/**
 * Сохранение XML.
 * UF_UPD_FILE — только актуальная версия.
 * Каждая генерация — новый файл; старые file_id остаются в b_xmldoc_document (физическая история).
 */
class FileSaver
{
    public function save(
        string $entityType,
        int $entityId,
        int $entityTypeId,
        string $utf8Xml,
        string $docNumber
    ): array {
        $version = DocumentRegistry::getNextVersion($entityType, $entityId);
        $fileName = sprintf('УПД_%d.xml', $entityId);
        $storageName = sprintf('УПД_%d_v%d.xml', $entityId, $version);
        $encoding = Config::fileEncoding();
        $xmlContent = XmlEncoder::forStorage($utf8Xml);

        $saved = DiskStorage::save($storageName, $xmlContent, $entityTypeId, $entityId);
        $fileId = (int)$saved['fileId'];

        if ($fileId <= 0) {
            throw new \RuntimeException('Не удалось сохранить файл на диск');
        }

        if (!$this->attachToEntity($entityType, $entityId, $entityTypeId, $fileId)) {
            throw new \RuntimeException(
                'XML сохранён (file_id=' . $fileId . '), но не удалось записать в поле «Файл УПД» (UF_UPD_FILE). '
                . 'Проверьте журнал модуля и настройки smart_invoice_type_id.'
            );
        }

        DocumentRegistry::add(
            $entityType,
            $entityId,
            $fileName,
            $fileId,
            $docNumber,
            $version,
            $encoding,
            hash('sha256', $xmlContent),
            DocumentStatus::GENERATED
        );

        if (Config::publishTimeline()) {
            TimelinePublisher::publishDocumentGenerated($entityTypeId, $entityId, $fileName, $fileId, $version);
        }

        return [
            'fileId'       => $fileId,
            'fileName'     => $fileName,
            'fileUrl'      => (string)\CFile::GetPath($fileId),
            'version'      => $version,
            'encoding'     => $encoding,
            'docStatus'    => DocumentStatus::GENERATED,
            'diskUploaded' => (bool)$saved['diskUploaded'],
        ];
    }

    private function attachToEntity(
        string $entityType,
        int $entityId,
        int $entityTypeId,
        int $fileId
    ): bool {
        \Bitrix\Main\Loader::includeModule('crm');

        $fileValue = $this->buildUserFieldFileValue($fileId);
        if ($fileValue === null) {
            Logger::write(
                $entityType,
                $entityId,
                Logger::STATUS_ERROR,
                'Не удалось подготовить дескриптор файла для UF_UPD_FILE (file_id=' . $fileId . ')'
            );

            return false;
        }

        if ($entityType === DataCollector::TYPE_DEAL) {
            $fields = ['UF_UPD_FILE' => $fileValue];
            $options = ['CHECK_PERMISSIONS' => 'N'];
            $deal = new \CCrmDeal(false);
            $result = $deal->Update($entityId, $fields, true, true, $options);

            if (!$result) {
                Logger::write(
                    $entityType,
                    $entityId,
                    Logger::STATUS_ERROR,
                    'Ошибка записи UF_UPD_FILE: ' . (string)$deal->LAST_ERROR
                );

                return false;
            }

            return true;
        }

        if ($entityType === DataCollector::TYPE_SMART_INVOICE) {
            $factory = \Bitrix\Crm\Service\Container::getInstance()->getFactory($entityTypeId);
            if ($factory === null) {
                Logger::write(
                    $entityType,
                    $entityId,
                    Logger::STATUS_ERROR,
                    'Не найден factory смарт-процесса для записи UF_UPD_FILE (entityTypeId=' . $entityTypeId
                    . '). Проверьте smart_invoice_type_id в настройках модуля.'
                );

                return false;
            }

            if (!$factory->getFieldsCollection()->hasField('UF_UPD_FILE')) {
                $ufEntity = class_exists(\Vendor\Xmldoc\Install\UserFieldInstaller::class)
                    ? \Vendor\Xmldoc\Install\UserFieldInstaller::resolveUserFieldEntityId($entityTypeId)
                    : ('CRM_' . $entityTypeId);

                Logger::write(
                    $entityType,
                    $entityId,
                    Logger::STATUS_ERROR,
                    'Поле UF_UPD_FILE не найдено для entityTypeId=' . $entityTypeId
                    . ' (UF-сущность: ' . $ufEntity . '). Нажмите «Создать поля УПД» в настройках модуля.'
                );

                return false;
            }

            $item = $factory->getItem($entityId);
            if ($item === null) {
                Logger::write(
                    $entityType,
                    $entityId,
                    Logger::STATUS_ERROR,
                    'Элемент смарт-процесса не найден для записи UF_UPD_FILE (id=' . $entityId . ')'
                );

                return false;
            }

            $item->set('UF_UPD_FILE', $fileValue);
            $updateResult = $factory->getUpdateOperation($item)->disableAllChecks()->launch();

            if (!$updateResult->isSuccess()) {
                Logger::write(
                    $entityType,
                    $entityId,
                    Logger::STATUS_ERROR,
                    'Ошибка записи UF_UPD_FILE: ' . implode('; ', $updateResult->getErrorMessages())
                );

                return false;
            }

            return true;
        }

        Logger::write(
            $entityType,
            $entityId,
            Logger::STATUS_ERROR,
            'Неподдерживаемый тип сущности для записи UF_UPD_FILE: ' . $entityType
        );

        return false;
    }

    /**
     * UF типа file в CCrmDeal::Update и CRM Item ожидает массив CFile::MakeFileArray, а не голый int.
     *
     * @return array<string, mixed>|int|null
     */
    private function buildUserFieldFileValue(int $fileId): array|int|null
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

        // Fallback: часть сборок принимает ID существующего b_file.
        return $fileId;
    }
}
