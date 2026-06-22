<?php

namespace Vendor\Xmldoc\Runtime;

use Bitrix\Main\Loader;
use Vendor\Xmldoc\Contract\GenerateRuntimeInterface;
use Vendor\Xmldoc\Environment\PortalEnvironment;

/** Выбор runtime по типу портала: коробка → Box, облако → vendor.xmldoc.cloud. */
final class RuntimeFactory
{
    public static function create(): GenerateRuntimeInterface
    {
        if (PortalEnvironment::isCloud()) {
            if (!Loader::includeModule('vendor.xmldoc.cloud')) {
                throw new \RuntimeException(
                    'Облачный портал Bitrix24: установите модуль «vendor.xmldoc.cloud» '
                    . '(Настройки → Настройки модулей).'
                );
            }

            $class = '\\Vendor\\Xmldoc\\Cloud\\CloudGenerateRuntime';
            if (!class_exists($class)) {
                throw new \RuntimeException('Модуль vendor.xmldoc.cloud установлен, но runtime не найден.');
            }

            return new $class();
        }

        return new BoxGenerateRuntime();
    }

    public static function runtimeModuleId(): string
    {
        return PortalEnvironment::isCloud() ? 'vendor.xmldoc.cloud' : 'vendor.xmldoc';
    }
}
