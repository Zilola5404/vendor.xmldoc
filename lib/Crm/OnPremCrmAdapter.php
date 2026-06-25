<?php

namespace Ooofix\Xmlupd\Crm;

use Bitrix\Main\Loader;
use Ooofix\Xmlupd\Contract\CrmAdapterInterface;
use Ooofix\Xmlupd\DocumentTypeRegistry;

/** Адаптер CRM для коробочной установки. */
final class OnPremCrmAdapter implements CrmAdapterInterface
{
    public function isCloud(): bool
    {
        return false;
    }

    public function getOwnerTypeId(string $entityType): int
    {
        return DocumentTypeRegistry::getOwnerTypeId($entityType);
    }

    public function registerAutomationTrigger(string $code, string $name): bool
    {
        if (!Loader::includeModule('crm')) {
            return false;
        }

        // REST-контекст приложения (облако / локальное REST-приложение)
        if (class_exists(\Bitrix\Crm\Automation\Rest\Trigger\Add::class)) {
            try {
                $result = (new \Bitrix\Crm\Automation\Rest\Trigger\Add())->process([
                    'CODE' => $code,
                    'NAME' => $name,
                ]);

                return $result !== null && !($result instanceof \Bitrix\Rest\RestException);
            } catch (\Throwable) {
                // fallback ниже
            }
        }

        // Внутренние PHP-триггеры регистрируются через Event\CrmAutomation — true как «определены в модуле»
        return true;
    }

    public function executeAutomationTrigger(string $code, int $ownerTypeId, int $ownerId): bool
    {
        if ($ownerTypeId <= 0 || $ownerId <= 0 || !Loader::includeModule('crm')) {
            return false;
        }

        // 1. Внутренний API CRM (коробка): класс триггера + Target
        if (class_exists(\Bitrix\Crm\Automation\Factory::class)) {
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

                    return (bool)$trigger->send();
                } catch (\Throwable) {
                    // следующий способ
                }
            }
        }

        // 2. REST execute в контексте локального приложения
        if (class_exists(\Bitrix\Crm\Automation\Rest\Trigger\Execute::class)) {
            try {
                $result = (new \Bitrix\Crm\Automation\Rest\Trigger\Execute())->process([
                    'CODE'           => $code,
                    'OWNER_TYPE_ID'  => $ownerTypeId,
                    'OWNER_ID'       => $ownerId,
                ]);

                return $result === true;
            } catch (\Throwable) {
                return false;
            }
        }

        return false;
    }
}
