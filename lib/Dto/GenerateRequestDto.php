<?php

namespace Vendor\Xmldoc\Dto;

/** Запрос на генерацию УПД. XMLDOC-27 */
final class GenerateRequestDto
{
    public function __construct(
        public readonly EntityContextDto $context,
        public readonly bool $checkPermissions = true,
    ) {
    }
}
