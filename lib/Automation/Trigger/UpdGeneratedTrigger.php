<?php

namespace Ooofix\Xmlupd\Automation\Trigger;

use Bitrix\Main\Localization\Loc;
use Ooofix\Xmlupd\Automation\TriggerRegistry;

Loc::loadMessages(__FILE__);

/** XMLDOC-23: триггер «УПД сформирован». */
final class UpdGeneratedTrigger extends BaseXmlupdTrigger
{
    public static function getCode(): string
    {
        return TriggerRegistry::CODE_UPD_GENERATED;
    }

    public static function getName(): string
    {
        return Loc::getMessage('OOOFIX_XMLUPD_TRIGGER_UPD_GENERATED') ?: 'УПД сформирован';
    }

    public static function getDescription(): string
    {
        return Loc::getMessage('OOOFIX_XMLUPD_TRIGGER_UPD_GENERATED_DESC')
            ?: 'Срабатывает после успешной генерации XML УПД';
    }
}
