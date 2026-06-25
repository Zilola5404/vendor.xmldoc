<?php

namespace Ooofix\Xmlupd\Contract;

use Ooofix\Xmlupd\Dto\GenerateRequestDto;
use Ooofix\Xmlupd\GenerateResult;

/** Контракт runtime генерации УПД (коробка / облако). */
interface GenerateRuntimeInterface
{
    public function runFromDto(GenerateRequestDto $request): GenerateResult;
}
