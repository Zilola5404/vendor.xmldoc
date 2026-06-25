<?php

namespace Ooofix\Xmlupd\Runtime;

use Ooofix\Xmlupd\Cloud\CloudGenerateRuntime;
use Ooofix\Xmlupd\Contract\GenerateRuntimeInterface;
use Ooofix\Xmlupd\Environment\PortalEnvironment;
use Ooofix\Xmlupd\ModuleInfo;

/** Выбор runtime по типу портала внутри одного модуля ooofix.xmlupd. */
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
