<?php

namespace Vendor\Xmldoc\Event;

use Bitrix\Main\EventResult;

/** Регистрация триггеров CRM в списке автоматизации. */
final class CrmAutomation
{
    /** @param class-string $triggerClass */
    public static function appendTrigger(string $triggerClass): EventResult
    {
        return new EventResult(EventResult::SUCCESS, ['TRIGGER' => $triggerClass]);
    }
}
