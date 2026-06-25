<?php

namespace Ooofix\Xmlupd\Crm;

use Ooofix\Xmlupd\Cloud\Crm\CloudCrmAdapter;
use Ooofix\Xmlupd\Contract\CrmAdapterInterface;
use Ooofix\Xmlupd\Environment\PortalEnvironment;

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
