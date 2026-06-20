<?php

namespace Vendor\Xmldoc\Automation\Trigger;

use Bitrix\Main\Localization\Loc;
use Vendor\Xmldoc\DocumentTypeRegistry;

Loc::loadMessages(__FILE__);

/**
 * Базовый триггер CRM автоматизации модуля xmldoc.
 * Группа «Другие роботы» — XMLDOC-22.
 */
abstract class BaseXmldocTrigger extends \Bitrix\Bizproc\Automation\Trigger\BaseTrigger
{
    public static function getGroup(): array
    {
        return DocumentTypeRegistry::robotGroup();
    }

    public static function isSupported(int $entityTypeId): bool
    {
        return DocumentTypeRegistry::isAutomationSupported($entityTypeId);
    }

    public static function isEnabled(): bool
    {
        return true;
    }
}
