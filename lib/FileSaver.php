<?php

namespace Vendor\Xmldoc;

use Vendor\Xmldoc\Crm\CrmEntityWriter;
use Vendor\Xmldoc\Crm\DiskStorage;
use Vendor\Xmldoc\Crm\TimelinePublisher;

/**
 * Сохранение XML.
 * UF_UPD_FILE — только актуальная версия.
 */
class FileSaver
{
    /**
     * @param class-string|null $entityWriterClass Класс с методом attachUpdFile (коробка / облако).
     */
    public function __construct(
        private readonly ?string $entityWriterClass = null,
    ) {
    }

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

        if (!$this->attachUpdFile($entityType, $entityId, $entityTypeId, $fileId)) {
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

    private function attachUpdFile(
        string $entityType,
        int $entityId,
        int $entityTypeId,
        int $fileId
    ): bool {
        $writer = $this->entityWriterClass ?? CrmEntityWriter::class;

        return $writer::attachUpdFile($entityType, $entityId, $entityTypeId, $fileId);
    }
}
