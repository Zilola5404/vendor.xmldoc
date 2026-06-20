<?php

namespace Vendor\Xmldoc\Automation\Trigger;

use Bitrix\Main\Localization\Loc;
use Vendor\Xmldoc\Automation\TriggerRegistry;

Loc::loadMessages(__FILE__);

/** XMLDOC-23: триггер «УПД сформирован». */
final class UpdGeneratedTrigger extends BaseXmldocTrigger
{
    public static function getCode(): string
    {
        return TriggerRegistry::CODE_UPD_GENERATED;
    }

    public static function getName(): string
    {
        return Loc::getMessage('VENDOR_XMLDOC_TRIGGER_UPD_GENERATED') ?: 'УПД сформирован';
    }

    public static function getDescription(): string
    {
        return Loc::getMessage('VENDOR_XMLDOC_TRIGGER_UPD_GENERATED_DESC')
            ?: 'Срабатывает после успешной генерации XML УПД';
    }
}
