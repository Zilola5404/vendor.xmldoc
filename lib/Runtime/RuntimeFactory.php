<?php

namespace Vendor\Xmldoc\Runtime;

use Vendor\Xmldoc\Cloud\CloudGenerateRuntime;
use Vendor\Xmldoc\Contract\GenerateRuntimeInterface;
use Vendor\Xmldoc\Environment\PortalEnvironment;
use Vendor\Xmldoc\ModuleInfo;

/** Выбор runtime по типу портала внутри одного модуля ooofix.vendor.xml. */
final class RuntimeFactory
{
    public static function create(): GenerateRuntimeInterface
    {
        if (PortalEnvironment::isCloud()) {
            if (!class_exists(CloudGenerateRuntime::class)) {
                throw new \RuntimeException(
                    'Облачный runtime не найден. Обновите модуль ' . ModuleInfo::MODULE_ID . ' до актуальной версии.'
                );
            }

            return new CloudGenerateRuntime();
        }

        return new BoxGenerateRuntime();
    }

    public static function runtimeModuleId(): string
    {
        return ModuleInfo::MODULE_ID;
    }
}
