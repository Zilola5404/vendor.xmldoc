<?php

namespace Vendor\Xmldoc\Automation\Trigger;

use Bitrix\Main\Localization\Loc;
use Vendor\Xmldoc\Automation\TriggerRegistry;

Loc::loadMessages(__FILE__);

/** XMLDOC-24: заготовка — документ отправлен в ЭДО. */
final class EdoSentTrigger extends BaseXmldocTrigger
{
    public static function getCode(): string
    {
        return TriggerRegistry::CODE_EDO_SENT;
    }

    public static function getName(): string
    {
        return Loc::getMessage('VENDOR_XMLDOC_TRIGGER_EDO_SENT') ?: 'Документ отправлен';
    }

    public static function getDescription(): string
    {
        return Loc::getMessage('VENDOR_XMLDOC_TRIGGER_EDO_SENT_DESC')
            ?: 'Срабатывает после отправки документа в ЭДО';
    }
}
