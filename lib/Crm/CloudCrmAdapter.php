<?php

namespace Vendor\Xmldoc\Crm;

use Bitrix\Main\Config\Option;
use Vendor\Xmldoc\Contract\CrmAdapterInterface;
use Vendor\Xmldoc\DocumentTypeRegistry;

/**
 * Адаптер CRM для облачного Б24 (REST-приложение / webhook).
 * XMLDOC-26 — заготовка под маркет-приложение.
 */
final class CloudCrmAdapter implements CrmAdapterInterface
{
    private const MODULE = 'vendor.xmldoc';

    public function isCloud(): bool
    {
        return true;
    }

    public function getOwnerTypeId(string $entityType): int
    {
        return DocumentTypeRegistry::getOwnerTypeId($entityType);
    }

    public function registerAutomationTrigger(string $code, string $name): bool
    {
        if (!class_exists(\Bitrix\Crm\Automation\Rest\Trigger\Add::class)) {
            return false;
        }

        try {
            $result = (new \Bitrix\Crm\Automation\Rest\Trigger\Add())->process([
                'CODE' => $code,
                'NAME' => $name,
            ]);

            return $result !== null;
        } catch (\Throwable) {
            return false;
        }
    }

    public function executeAutomationTrigger(string $code, int $ownerTypeId, int $ownerId): bool
    {
        if (!class_exists(\Bitrix\Crm\Automation\Rest\Trigger\Execute::class)) {
            return false;
        }

        try {
            $result = (new \Bitrix\Crm\Automation\Rest\Trigger\Execute())->process([
                'CODE'          => $code,
                'OWNER_TYPE_ID' => $ownerTypeId,
                'OWNER_ID'      => $ownerId,
            ]);

            return $result === true;
        } catch (\Throwable) {
            return false;
        }
    }

    /** Webhook URL для crm.automation.trigger (если настроен в options). */
    public function getRestWebhookUrl(): string
    {
        return (string)Option::get(self::MODULE, 'cloud_rest_webhook', '');
    }
}
