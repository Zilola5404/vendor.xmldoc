<?php

namespace Vendor\Xmldoc\Http;

use Bitrix\Main\Loader;
use Vendor\Xmldoc\CrmPermissions;
use Vendor\Xmldoc\DataCollector;
use Vendor\Xmldoc\GenerateResult;
use Vendor\Xmldoc\Dto\GenerateRequestDto;
use Vendor\Xmldoc\Dto\EntityContextDto;
use Vendor\Xmldoc\GenerateService;

/** Общая логика AJAX-генерации УПД */
class GenerateEndpoint
{
    /** @return array<string, mixed> */
    public static function execute(): array
    {
        if (!check_bitrix_sessid()) {
            return GenerateResult::fail(['Сессия истекла. Обновите страницу.'])->toArray();
        }

        if (!Loader::includeModule('vendor.xmldoc') || !Loader::includeModule('crm')) {
            return GenerateResult::fail(['Модуль vendor.xmldoc или CRM не установлен'])->toArray();
        }

        $entityType = (string)($_POST['entityType'] ?? $_REQUEST['entityType'] ?? '');
        $entityId = (int)($_POST['entityId'] ?? $_REQUEST['entityId'] ?? 0);

        $allowed = [DataCollector::TYPE_DEAL, DataCollector::TYPE_SMART_INVOICE];
        if (!in_array($entityType, $allowed, true) || $entityId <= 0) {
            return GenerateResult::fail(['Некорректные параметры запроса'])->toArray();
        }

        global $USER;
        if (!$USER->IsAuthorized()) {
            return GenerateResult::fail(['Требуется авторизация'])->toArray();
        }

        if (!CrmPermissions::canGenerate($entityType, $entityId)) {
            return GenerateResult::fail([CrmPermissions::getDenyMessage()])->toArray();
        }

        $service = new GenerateService();
        $request = new GenerateRequestDto(
            EntityContextDto::from($entityType, $entityId, 0),
            false
        );

        return $service->runFromDto($request)->toArray();
    }
}
