<?php

namespace Vendor\Xmldoc\Automation;

use Vendor\Xmldoc\Automation\Trigger\EdoAcceptedTrigger;
use Vendor\Xmldoc\Automation\Trigger\EdoDeliveredTrigger;
use Vendor\Xmldoc\Automation\Trigger\EdoRejectedTrigger;
use Vendor\Xmldoc\Automation\Trigger\EdoSentTrigger;
use Vendor\Xmldoc\Automation\Trigger\UpdGeneratedTrigger;

/**
 * Каталог триггеров CRM автоматизации модуля.
 * XMLDOC-23, XMLDOC-24
 */
final class TriggerRegistry
{
    public const CODE_UPD_GENERATED  = 'xmldoc.upd.generated';
    public const CODE_EDO_SENT       = 'xmldoc.edo.sent';
    public const CODE_EDO_DELIVERED  = 'xmldoc.edo.delivered';
    public const CODE_EDO_ACCEPTED   = 'xmldoc.edo.accepted';
    public const CODE_EDO_REJECTED   = 'xmldoc.edo.rejected';

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
        $adapter = \Vendor\Xmldoc\Crm\CrmAdapterFactory::create();

        foreach (self::definitions() as $code => $name) {
            $adapter->registerAutomationTrigger($code, $name);
        }
    }
}
