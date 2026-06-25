<?php

namespace Ooofix\Xmlupd\Automation;

use Ooofix\Xmlupd\Automation\Trigger\EdoAcceptedTrigger;
use Ooofix\Xmlupd\Automation\Trigger\EdoDeliveredTrigger;
use Ooofix\Xmlupd\Automation\Trigger\EdoRejectedTrigger;
use Ooofix\Xmlupd\Automation\Trigger\EdoSentTrigger;
use Ooofix\Xmlupd\Automation\Trigger\UpdGeneratedTrigger;

/**
 * Каталог триггеров CRM автоматизации модуля.
 * XMLDOC-23, XMLDOC-24
 */
final class TriggerRegistry
{
    public const CODE_UPD_GENERATED  = 'ooofix.xmlupd.upd.generated';
    public const CODE_EDO_SENT       = 'ooofix.xmlupd.edo.sent';
    public const CODE_EDO_DELIVERED  = 'ooofix.xmlupd.edo.delivered';
    public const CODE_EDO_ACCEPTED   = 'ooofix.xmlupd.edo.accepted';
    public const CODE_EDO_REJECTED   = 'ooofix.xmlupd.edo.rejected';

    /** @return list<class-string> */
    public static function triggerClasses(): array
    {
        return [
            UpdGeneratedTrigger::class,
            EdoSentTrigger::class,
            EdoDeliveredTrigger::class,
            EdoAcceptedTrigger::class,
            EdoRejectedTrigger::class,
        ];
    }

    /** @return array<string, string> code => human name */
    public static function definitions(): array
    {
        $map = [];
        foreach (self::triggerClasses() as $class) {
            if (method_exists($class, 'getCode') && method_exists($class, 'getName')) {
                $map[$class::getCode()] = $class::getName();
            }
        }

        return $map;
    }

    public static function installAll(): void
    {
        $adapter = \Ooofix\Xmlupd\Crm\CrmAdapterFactory::create();

        foreach (self::definitions() as $code => $name) {
            $adapter->registerAutomationTrigger($code, $name);
        }
    }
}
