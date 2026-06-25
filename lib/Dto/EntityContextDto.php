<?php

namespace Ooofix\Xmlupd\Dto;

/** Контекст CRM-сущности. XMLDOC-27 */
final class EntityContextDto
{
    public function __construct(
        public readonly string $entityType,
        public readonly int $entityId,
        public readonly int $ownerTypeId,
    ) {
    }

    public static function from(string $entityType, int $entityId, int $ownerTypeId): self
    {
        return new self($entityType, $entityId, $ownerTypeId);
    }
}
