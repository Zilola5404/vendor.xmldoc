<?php

namespace Ooofix\Xmlupd;

use Ooofix\Xmlupd\Dto\EntityContextDto;
use Ooofix\Xmlupd\Dto\GenerateRequestDto;
use Ooofix\Xmlupd\Runtime\RuntimeFactory;

/** Оркестратор: делегирует генерацию runtime коробки или облака. */
class GenerateService
{
    public function runFromDto(GenerateRequestDto $request): GenerateResult
    {
        return RuntimeFactory::create()->runFromDto($request);
    }

    public static function request(
        string $entityType,
        int $entityId,
        bool $checkPermissions = true,
        int $ownerTypeId = 0
    ): GenerateRequestDto {
        return new GenerateRequestDto(
            EntityContextDto::from($entityType, $entityId, $ownerTypeId),
            $checkPermissions
        );
    }
}
