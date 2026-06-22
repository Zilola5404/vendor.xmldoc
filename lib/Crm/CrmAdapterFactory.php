<?php

namespace Vendor\Xmldoc\Crm;

use Bitrix\Main\Loader;
use Vendor\Xmldoc\Contract\CrmAdapterInterface;
use Vendor\Xmldoc\Environment\PortalEnvironment;

final class CrmAdapterFactory
{
    public static function create(): CrmAdapterInterface
    {
        if (PortalEnvironment::isCloud() && Loader::includeModule('vendor.xmldoc.cloud')) {
            $class = '\\Vendor\\Xmldoc\\Cloud\\Crm\\CloudCrmAdapter';

            if (class_exists($class)) {
                return new $class();
            }
        }

        return new OnPremCrmAdapter();
    }
}
