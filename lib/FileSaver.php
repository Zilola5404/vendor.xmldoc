<?php

namespace Vendor\Xmldoc;

use Bitrix\Crm\Timeline\CommentEntry;
use Bitrix\Main\Loader;

/**
 * Сохранение XML.
 * UF_UPD_FILE — только актуальная версия.
 * Каждая генерация — новый CFile; старые file_id остаются в b_xmldoc_document (физическая история).
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

        $fileId = (int)\CFile::SaveFile([
            'name'      => $storageName,
            'type'      => 'application/xml',
            'content'   => $xmlContent,
            'MODULE_ID' => 'vendor.xmldoc',
        ], 'xmldoc');

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
            $this->publishTimeline($entityTypeId, $entityId, $fileName, $fileId, $version);
        }

        return [
            'fileId'     => $fileId,
            'fileName'   => $fileName,
            'fileUrl'    => (string)\CFile::GetPath($fileId),
            'version'    => $version,
            'encoding'   => $encoding,
            'docStatus'  => DocumentStatus::GENERATED,
        ];
    }

    private function attachToEntity(
        string $entityType,
        int $entityId,
        int $entityTypeId,
        int $fileId
    ): void {
        Loader::includeModule('crm');

        if ($entityType === DataCollector::TYPE_DEAL) {
            $fields = ['UF_UPD_FILE' => $fileId];
            $options = ['CHECK_PERMISSIONS' => 'N'];
            $deal = new \CCrmDeal(false);
            $deal->Update($entityId, $fields, true, true, $options);

            return;
        }

        if ($entityType === DataCollector::TYPE_SMART_INVOICE) {
            $factory = \Bitrix\Crm\Service\Container::getInstance()->getFactory($entityTypeId);
            if ($factory === null) {
                return;
            }
            $item = $factory->getItem($entityId);
            if ($item === null) {
                return;
            }
            $item->set('UF_UPD_FILE', $fileId);
            $factory->getUpdateOperation($item)->disableAllChecks()->launch();
        }
    }

    private function publishTimeline(
        int $entityTypeId,
        int $entityId,
        string $fileName,
        int $fileId,
        int $version
    ): void {
        if (!class_exists(CommentEntry::class)) {
            return;
        }

        global $USER;
        $authorId = (int)($USER instanceof \CUser ? $USER->GetID() : 0);
        $url = htmlspecialcharsbx((string)\CFile::GetPath($fileId));
        $text = sprintf(
            'Сформирован документ <a href="%s" target="_blank">%s</a> (версия %d, статус: %s)',
            $url,
            htmlspecialcharsbx($fileName),
            $version,
            DocumentStatus::GENERATED
        );

        CommentEntry::create([
            'TEXT'      => $text,
            'AUTHOR_ID' => $authorId > 0 ? $authorId : 1,
            'BINDINGS'  => [
                ['ENTITY_TYPE_ID' => $entityTypeId, 'ENTITY_ID' => $entityId],
            ],
        ]);
    }
}
