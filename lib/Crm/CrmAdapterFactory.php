<?php

namespace Vendor\Xmldoc\Crm;

use Vendor\Xmldoc\Cloud\Crm\CloudCrmAdapter;
use Vendor\Xmldoc\Contract\CrmAdapterInterface;
use Vendor\Xmldoc\Environment\PortalEnvironment;

final class CrmAdapterFactory
{
    public static function create(): CrmAdapterInterface
    {
        if (PortalEnvironment::isCloud() && class_exists(CloudCrmAdapter::class)) {
            return new CloudCrmAdapter();
        }

        return new OnPremCrmAdapter();
    }
}
