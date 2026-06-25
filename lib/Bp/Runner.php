<?php

namespace Ooofix\Xmlupd\Bp;

use Ooofix\Xmlupd\DataCollector;
use Ooofix\Xmlupd\GenerateService;
use Ooofix\Xmlupd\Dto\EntityContextDto;
use Ooofix\Xmlupd\Dto\GenerateRequestDto;

/**
 * Запуск генерации УПД из PHP-кода бизнес-процесса.
 * Рекомендуется activity «Сформировать УПД (XML)» в дизайнере роботов (v1.3.0+).
 *
 * Пример:
 * \Ooofix\Xmlupd\Bp\Runner::fromDeal({=Document:ID});
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
        if (!\Bitrix\Main\Loader::includeModule('ooofix.xmlupd')) {
            return ['success' => false, 'message' => 'Модуль ooofix.xmlupd не установлен'];
        }

        $service = new GenerateService();
        $request = new GenerateRequestDto(
            EntityContextDto::from($entityType, $entityId, 0),
            true
        );

        return $service->runFromDto($request)->toArray();
    }
}
