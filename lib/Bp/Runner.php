<?php

namespace Vendor\Xmldoc\Bp;

use Vendor\Xmldoc\DataCollector;
use Vendor\Xmldoc\GenerateService;
use Vendor\Xmldoc\Dto\EntityContextDto;
use Vendor\Xmldoc\Dto\GenerateRequestDto;

/**
 * Запуск генерации УПД из PHP-кода бизнес-процесса.
 * Рекомендуется activity «Сформировать УПД (XML)» в дизайнере роботов (v1.3.0+).
 *
 * Пример:
 * \Vendor\Xmldoc\Bp\Runner::fromDeal({=Document:ID});
 */
class Runner
{
    public static function fromDeal(int $dealId): array
    {
        return self::run(DataCollector::TYPE_DEAL, $dealId);
    }

    public static function fromSmartInvoice(int $itemId): array
    {
        return self::run(DataCollector::TYPE_SMART_INVOICE, $itemId);
    }

    public static function run(string $entityType, int $entityId): array
    {
        if (!\Bitrix\Main\Loader::includeModule('vendor.xmldoc')) {
            return ['success' => false, 'message' => 'Модуль vendor.xmldoc не установлен'];
        }

        $service = new GenerateService();
        $request = new GenerateRequestDto(
            EntityContextDto::from($entityType, $entityId, 0),
            true
        );

        return $service->runFromDto($request)->toArray();
    }
}
