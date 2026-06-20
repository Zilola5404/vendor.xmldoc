<?php

namespace Vendor\Xmldoc;

use Bitrix\Main\Application;
use Bitrix\Main\Type\DateTime;
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

        $this->attachToEntity($entityType, $entityId, $entityTypeId, $fileId);

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
            'fileId'     => $fileId,
            'fileName'   => $fileName,
            'fileUrl'    => (string)\CFile::GetPath($fileId),
            'version'    => $version,
            'encoding'   => $encoding,
            'docStatus'  => DocumentStatus::GENERATED,
            'diskUploaded' => (bool)$saved['diskUploaded'],
        ];
    }

    private function attachToEntity(
        string $entityType,
        int $entityId,
        int $entityTypeId,
        int $fileId
    ): void {
        \Bitrix\Main\Loader::includeModule('crm');

        if ($entityType === DataCollector::TYPE_DEAL) {
            $fields = ['UF_UPD_FILE' => $fileId];
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
            }

            return;
        }

        if ($entityType === DataCollector::TYPE_SMART_INVOICE) {
            $factory = \Bitrix\Crm\Service\Container::getInstance()->getFactory($entityTypeId);
            if ($factory === null) {
                Logger::write(
                    $entityType,
                    $entityId,
                    Logger::STATUS_ERROR,
                    'Не найден factory смарт-процесса для записи UF_UPD_FILE'
                );

                return;
            }

            $item = $factory->getItem($entityId);
            if ($item === null) {
                Logger::write(
                    $entityType,
                    $entityId,
                    Logger::STATUS_ERROR,
                    'Элемент смарт-процесса не найден для записи UF_UPD_FILE'
                );

                return;
            }

            $item->set('UF_UPD_FILE', $fileId);
            $operation = $factory->getUpdateOperation($item)->disableAllChecks();
            $updateResult = $operation->launch();

            if (!$updateResult->isSuccess()) {
                Logger::write(
                    $entityType,
                    $entityId,
                    Logger::STATUS_ERROR,
                    'Ошибка записи UF_UPD_FILE: ' . implode('; ', $updateResult->getErrorMessages())
                );
            }
        }
    }
}
