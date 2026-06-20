<?php

namespace Vendor\Xmldoc\Automation\Trigger;

use Bitrix\Main\Localization\Loc;
use Vendor\Xmldoc\Automation\TriggerRegistry;

Loc::loadMessages(__FILE__);

/** XMLDOC-24: заготовка — документ отклонён. */
final class EdoRejectedTrigger extends BaseXmldocTrigger
{
    public static function getCode(): string
    {
        return TriggerRegistry::CODE_EDO_REJECTED;
    }

    public static function getName(): string
    {
        return Loc::getMessage('VENDOR_XMLDOC_TRIGGER_EDO_REJECTED') ?: 'Документ отклонён';
    }

    public static function getDescription(): string
    {
        return Loc::getMessage('VENDOR_XMLDOC_TRIGGER_EDO_REJECTED_DESC')
            ?: 'Срабатывает после отклонения документа контрагентом';
    }
}
