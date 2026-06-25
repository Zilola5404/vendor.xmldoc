<?php

namespace Ooofix\Xmlupd\Automation\Trigger;

use Bitrix\Main\Localization\Loc;
use Ooofix\Xmlupd\Automation\TriggerRegistry;

Loc::loadMessages(__FILE__);

/** XMLDOC-24: заготовка — документ отклонён. */
final class EdoRejectedTrigger extends BaseXmlupdTrigger
{
    public static function getCode(): string
    {
        return TriggerRegistry::CODE_EDO_REJECTED;
    }

    public static function getName(): string
    {
        return Loc::getMessage('OOOFIX_XMLUPD_TRIGGER_EDO_REJECTED') ?: 'Документ отклонён';
    }

    public static function getDescription(): string
    {
        return Loc::getMessage('OOOFIX_XMLUPD_TRIGGER_EDO_REJECTED_DESC')
            ?: 'Срабатывает после отклонения документа контрагентом';
    }
}
