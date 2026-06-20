<?php

namespace Vendor\Xmldoc\Controller;

use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Engine\ActionFilter;
use Bitrix\Main\Loader;
use Vendor\Xmldoc\CrmPermissions;
use Vendor\Xmldoc\DataCollector;
use Vendor\Xmldoc\GenerateResult;
use Vendor\Xmldoc\GenerateService;

/** AJAX: vendor.xmldoc:upd.generate */
class Upd extends Controller
{
    public function configureActions(): array
    {
        return [
            'generate' => [
                'prefilters' => [
                    new ActionFilter\Authentication(),
                    new ActionFilter\Csrf(),
                ],
            ],
        ];
    }

    public function generateAction(string $entityType, int $entityId): array
    {
        if (!Loader::includeModule('vendor.xmldoc') || !Loader::includeModule('crm')) {
            return GenerateResult::fail(['Модуль vendor.xmldoc или CRM не установлен'])->toArray();
        }

        if (!CrmPermissions::canGenerate($entityType, $entityId)) {
            return GenerateResult::fail([CrmPermissions::getDenyMessage()])->toArray();
        }

        $allowed = [DataCollector::TYPE_DEAL, DataCollector::TYPE_SMART_INVOICE];
        if (!in_array($entityType, $allowed, true) || $entityId <= 0) {
            return GenerateResult::fail(['Некорректные параметры запроса'])->toArray();
        }

        return (new GenerateService())->run($entityType, $entityId, false)->toArray();
    }
}
