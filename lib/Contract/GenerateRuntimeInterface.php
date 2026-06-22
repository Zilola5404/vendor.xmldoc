<?php

namespace Vendor\Xmldoc\Contract;

use Vendor\Xmldoc\Dto\GenerateRequestDto;
use Vendor\Xmldoc\GenerateResult;

/** Контракт runtime генерации УПД (коробка / облако). */
interface GenerateRuntimeInterface
{
    public function runFromDto(GenerateRequestDto $request): GenerateResult;
}
