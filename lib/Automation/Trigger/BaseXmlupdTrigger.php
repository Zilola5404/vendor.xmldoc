<?php

namespace Ooofix\Xmlupd\Automation\Trigger;

use Bitrix\Main\Localization\Loc;
use Ooofix\Xmlupd\DocumentTypeRegistry;

Loc::loadMessages(__FILE__);

/**
 * Базовый триггер CRM автоматизации модуля ooofix.xmlupd.
 * Группа «Другие роботы» — XMLDOC-22.
 */
abstract class BaseXmlupdTrigger extends \Bitrix\Bizproc\Automation\Trigger\BaseTrigger
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
