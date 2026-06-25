<?php

namespace Ooofix\Xmlupd\Automation;

use Ooofix\Xmlupd\Crm\CrmAdapterFactory;
use Ooofix\Xmlupd\DocumentTypeRegistry;
use Ooofix\Xmlupd\Logger;

/** Запуск триггеров CRM автоматизации. */
final class TriggerService
{
    public static function fire(string $code, string $entityType, int $entityId): bool
    {
        if (!DocumentTypeRegistry::exists($entityType)) {
            return false;
        }

        try {
            $ownerTypeId = DocumentTypeRegistry::getOwnerTypeId($entityType);
            $adapter = CrmAdapterFactory::create();
            $ok = $adapter->executeAutomationTrigger($code, $ownerTypeId, $entityId);

            if ($ok) {
                Logger::write($entityType, $entityId, Logger::STATUS_SUCCESS, 'Триггер CRM: ' . $code);
            }

            return $ok;
        } catch (\Throwable $e) {
            Logger::write($entityType, $entityId, Logger::STATUS_ERROR, 'Триггер CRM: ' . $e->getMessage());

            return false;
        }
    }

    public static function fireUpdGenerated(string $entityType, int $entityId): bool
    {
        return self::fire(TriggerRegistry::CODE_UPD_GENERATED, $entityType, $entityId);
    }

    public static function fireEdoSent(string $entityType, int $entityId): bool
    {
        return self::fire(TriggerRegistry::CODE_EDO_SENT, $entityType, $entityId);
    }

    public static function fireEdoDelivered(string $entityType, int $entityId): bool
    {
        return self::fire(TriggerRegistry::CODE_EDO_DELIVERED, $entityType, $entityId);
    }

    public static function fireEdoAccepted(string $entityType, int $entityId): bool
    {
        return self::fire(TriggerRegistry::CODE_EDO_ACCEPTED, $entityType, $entityId);
    }

    public static function fireEdoRejected(string $entityType, int $entityId): bool
    {
        return self::fire(TriggerRegistry::CODE_EDO_REJECTED, $entityType, $entityId);
    }
}
