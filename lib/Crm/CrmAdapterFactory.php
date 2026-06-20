<?php

namespace Vendor\Xmldoc\Crm;

use Bitrix\Main\Config\Option;
use Vendor\Xmldoc\Contract\CrmAdapterInterface;

final class CrmAdapterFactory
{
    private const MODULE = 'vendor.xmldoc';

    public static function create(): CrmAdapterInterface
    {
        $mode = (string)Option::get(self::MODULE, 'crm_adapter', 'auto');

        if ($mode === 'cloud') {
            return new CloudCrmAdapter();
        }

        if ($mode === 'onprem') {
            return new OnPremCrmAdapter();
        }

        // auto: облако определяется по константе или отсутствию коробочного маркера
        if (defined('BX24_HOST_NAME') && BX24_HOST_NAME !== '') {
            return new CloudCrmAdapter();
        }

        return new OnPremCrmAdapter();
    }
}
