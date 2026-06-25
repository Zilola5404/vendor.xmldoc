<?php

namespace Ooofix\Xmlupd\Automation\Trigger;

use Bitrix\Main\Localization\Loc;
use Ooofix\Xmlupd\Automation\TriggerRegistry;

Loc::loadMessages(__FILE__);

/** XMLDOC-24: заготовка — документ принят. */
final class EdoAcceptedTrigger extends BaseXmlupdTrigger
{
    public static function getCode(): string
    {
        return TriggerRegistry::CODE_EDO_ACCEPTED;
    }

    public static function getName(): string
    {
        return Loc::getMessage('OOOFIX_XMLUPD_TRIGGER_EDO_ACCEPTED') ?: 'Документ принят';
    }

    public static function getDescription(): string
    {
        return Loc::getMessage('OOOFIX_XMLUPD_TRIGGER_EDO_ACCEPTED_DESC')
            ?: 'Срабатывает после принятия документа контрагентом';
    }
}
