<?php

namespace Ooofix\Xmlupd\Edo;

use Ooofix\Xmlupd\Automation\TriggerService;
use Ooofix\Xmlupd\DocumentRegistry;
use Ooofix\Xmlupd\DocumentStatus;

/** Обновление статуса ЭДО и запуск CRM-триггеров (заготовка XMLDOC-24). */
final class EdoStatusService
{
    public static function markSent(string $entityType, int $entityId, int $registryId): bool
    {
        if (!DocumentRegistry::updateStatus($registryId, DocumentStatus::SENT)) {
            return false;
        }

        return TriggerService::fireEdoSent($entityType, $entityId);
    }

    public static function markDelivered(string $entityType, int $entityId, int $registryId): bool
    {
        if (!DocumentRegistry::updateStatus($registryId, DocumentStatus::DELIVERED)) {
            return false;
        }

        return TriggerService::fireEdoDelivered($entityType, $entityId);
    }

    public static function markAccepted(string $entityType, int $entityId, int $registryId): bool
    {
        if (!DocumentRegistry::updateStatus($registryId, DocumentStatus::ACCEPTED)) {
            return false;
        }

        return TriggerService::fireEdoAccepted($entityType, $entityId);
    }

    public static function markRejected(string $entityType, int $entityId, int $registryId): bool
    {
        if (!DocumentRegistry::updateStatus($registryId, DocumentStatus::REJECTED)) {
            return false;
        }

        return TriggerService::fireEdoRejected($entityType, $entityId);
    }
}
