<?php

namespace Vendor\Xmldoc\Automation\Trigger;

use Bitrix\Main\Localization\Loc;
use Vendor\Xmldoc\Automation\TriggerRegistry;

Loc::loadMessages(__FILE__);

/** XMLDOC-24: заготовка — документ принят. */
final class EdoAcceptedTrigger extends BaseXmldocTrigger
{
    public static function getCode(): string
    {
        return TriggerRegistry::CODE_EDO_ACCEPTED;
    }

    public static function getName(): string
    {
        return Loc::getMessage('VENDOR_XMLDOC_TRIGGER_EDO_ACCEPTED') ?: 'Документ принят';
    }

    public static function getDescription(): string
    {
        return Loc::getMessage('VENDOR_XMLDOC_TRIGGER_EDO_ACCEPTED_DESC')
            ?: 'Срабатывает после принятия документа контрагентом';
    }
}
