<?php

namespace Vendor\Xmldoc\Automation\Trigger;

use Bitrix\Main\Localization\Loc;
use Vendor\Xmldoc\Automation\TriggerRegistry;

Loc::loadMessages(__FILE__);

/** XMLDOC-24: заготовка — документ доставлен контрагенту. */
final class EdoDeliveredTrigger extends BaseXmldocTrigger
{
    public static function getCode(): string
    {
        return TriggerRegistry::CODE_EDO_DELIVERED;
    }

    public static function getName(): string
    {
        return Loc::getMessage('VENDOR_XMLDOC_TRIGGER_EDO_DELIVERED') ?: 'Документ доставлен';
    }

    public static function getDescription(): string
    {
        return Loc::getMessage('VENDOR_XMLDOC_TRIGGER_EDO_DELIVERED_DESC')
            ?: 'Срабатывает после доставки документа контрагенту в ЭДО';
    }
}
