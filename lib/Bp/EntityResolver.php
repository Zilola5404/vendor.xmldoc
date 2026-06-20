<?php

namespace Vendor\Xmldoc\Bp;

use Vendor\Xmldoc\Config;
use Vendor\Xmldoc\DataCollector;

/** Определение сущности CRM из контекста бизнес-процесса / робота */
final class EntityResolver
{
    /**
     * @param array<int, mixed> $documentId [module, class, key]
     * @return array{0: string, 1: int} [entityType, entityId]
     */
    public static function fromDocumentId(array $documentId): array
    {
        $docClass = (string)($documentId[1] ?? '');
        $docKey = (string)($documentId[2] ?? '');

        if ($docKey === '') {
            throw new \RuntimeException('Не удалось определить документ БП');
        }

        if (preg_match('/^DEAL_(\d+)$/i', $docKey, $m)) {
            return [DataCollector::TYPE_DEAL, (int)$m[1]];
        }

        if (preg_match('/^DYNAMIC_(\d+)_(\d+)$/i', $docKey, $m)) {
            $typeId = (int)$m[1];
            $itemId = (int)$m[2];
            $smartTypeId = Config::smartInvoiceTypeId();

            if ($smartTypeId > 0 && $typeId === $smartTypeId) {
                return [DataCollector::TYPE_SMART_INVOICE, $itemId];
            }

            throw new \RuntimeException(
                'Смарт-процесс entityTypeId=' . $typeId . ' не настроен как СП «Счета» (ожидается ' . $smartTypeId . ')'
            );
        }

        if (stripos($docClass, 'Deal') !== false && preg_match('/(\d+)/', $docKey, $m)) {
            return [DataCollector::TYPE_DEAL, (int)$m[1]];
        }

        throw new \RuntimeException('Неподдерживаемый тип документа БП: ' . $docClass . ' / ' . $docKey);
    }
}
