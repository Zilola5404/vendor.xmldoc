<?php

namespace Vendor\Xmldoc\Contract;

interface DocumentBuilderInterface
{
    public function getType(): string;

    /** @param array<string, mixed> $mapped */
    public function build(array $mapped): string;

    /** @param array<string, mixed> $mapped */
    public function validate(array $mapped): array;
}
