<?php

namespace Vendor\Xmldoc\Cloud\Crm;

use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Vendor\Xmldoc\Contract\CrmAdapterInterface;
use Vendor\Xmldoc\DocumentTypeRegistry;
use Vendor\Xmldoc\ModuleInfo;

/**
 * Адаптер CRM для облачного Б24.
 * Триггеры: Factory → REST Execute → входящий webhook.
 */
final class CloudCrmAdapter implements CrmAdapterInterface
{
    private const MODULE = ModuleInfo::MODULE_ID;

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
        if (class_exists(\Bitrix\Crm\Automation\Rest\Trigger\Add::class)) {
            try {
                $result = (new \Bitrix\Crm\Automation\Rest\Trigger\Add())->process([
                    'CODE' => $code,
                    'NAME' => $name,
                ]);

                if ($result !== null && !($result instanceof \Bitrix\Rest\RestException)) {
                    return true;
                }
            } catch (\Throwable) {
                // webhook ниже
            }
        }

        $webhookResult = RestWebhookClient::call('crm.automation.trigger.add', [
            'CODE' => $code,
            'NAME' => $name,
        ]);

        return is_array($webhookResult) && !empty($webhookResult['result']);
    }

    public function executeAutomationTrigger(string $code, int $ownerTypeId, int $ownerId): bool
    {
        if ($ownerTypeId <= 0 || $ownerId <= 0) {
            return false;
        }

        if (Loader::includeModule('crm') && class_exists(\Bitrix\Crm\Automation\Factory::class)) {
            $triggerClass = \Bitrix\Crm\Automation\Factory::getTriggerByCode($code);
            if (is_string($triggerClass) && class_exists($triggerClass)) {
                try {
                    $target = \Bitrix\Crm\Automation\Factory::createTarget($ownerTypeId);
                    if (method_exists($target, 'setEntityId')) {
                        $target->setEntityId($ownerId);
                    } elseif (method_exists($target, 'setDocumentId')) {
                        $target->setDocumentId($ownerId);
                    }

                    /** @var \Bitrix\Bizproc\Automation\Trigger\BaseTrigger $trigger */
                    $trigger = new $triggerClass();
                    $trigger->setTarget($target);

                    if ($trigger->send()) {
                        return true;
                    }
                } catch (\Throwable) {
                    // следующий способ
                }
            }
        }

        if (class_exists(\Bitrix\Crm\Automation\Rest\Trigger\Execute::class)) {
            try {
                $result = (new \Bitrix\Crm\Automation\Rest\Trigger\Execute())->process([
                    'CODE'          => $code,
                    'OWNER_TYPE_ID' => $ownerTypeId,
                    'OWNER_ID'      => $ownerId,
                ]);

                if ($result === true) {
                    return true;
                }
            } catch (\Throwable) {
                // webhook ниже
            }
        }

        return RestWebhookClient::executeAutomationTrigger($code, $ownerTypeId, $ownerId);
    }

    public function getRestWebhookUrl(): string
    {
        return (string)Option::get(self::MODULE, 'cloud_rest_webhook', '');
    }
}
