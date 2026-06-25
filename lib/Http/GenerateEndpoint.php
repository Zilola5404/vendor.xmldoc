<?php

namespace Ooofix\Xmlupd\Http;

use Bitrix\Main\Loader;
use Ooofix\Xmlupd\CrmPermissions;
use Ooofix\Xmlupd\DataCollector;
use Ooofix\Xmlupd\GenerateResult;
use Ooofix\Xmlupd\Dto\GenerateRequestDto;
use Ooofix\Xmlupd\Dto\EntityContextDto;
use Ooofix\Xmlupd\GenerateService;

/** Общая логика AJAX-генерации УПД */
class GenerateEndpoint
{
    /** @return array<string, mixed> */
    public static function execute(): array
    {
        if (!check_bitrix_sessid()) {
            return GenerateResult::fail(['Сессия истекла. Обновите страницу.'])->toArray();
        }

        if (!Loader::includeModule('ooofix.xmlupd') || !Loader::includeModule('crm')) {
            return GenerateResult::fail(['Модуль ooofix.xmlupd или CRM не установлен'])->toArray();
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
