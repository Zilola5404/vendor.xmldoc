<?php

namespace Vendor\Xmldoc;

use Vendor\Xmldoc\Dto\EntityContextDto;
use Vendor\Xmldoc\Dto\GenerateRequestDto;
use Vendor\Xmldoc\Runtime\RuntimeFactory;

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
