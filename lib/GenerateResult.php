<?php

namespace Vendor\Xmldoc;

use Vendor\Xmldoc\Contract\GenerateResultInterface;

class GenerateResult implements GenerateResultInterface
{
    /** @param string[] $errors */
    public function __construct(
        private readonly bool $success,
        private readonly array $errors = [],
        private readonly ?int $fileId = null,
        private readonly ?string $fileName = null,
        private readonly ?int $version = null,
        private readonly ?string $encoding = null,
        private readonly ?string $docStatus = null,
    ) {
    }

    public static function ok(
        int $fileId,
        string $fileName,
        ?int $version = null,
        ?string $encoding = null,
        ?string $docStatus = null
    ): self {
        return new self(true, [], $fileId, $fileName, $version, $encoding, $docStatus);
    }

    /** @param string[] $errors */
    public static function fail(array $errors): self
    {
        return new self(false, $errors);
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getFileId(): ?int
    {
        return $this->fileId;
    }

    public function getFileName(): ?string
    {
        return $this->fileName;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        if ($this->success) {
            return [
                'success'  => true,
                'fileId'   => $this->fileId,
                'fileName' => $this->fileName,
                'fileUrl'  => $this->fileId ? \CFile::GetPath($this->fileId) : null,
                'version'   => $this->version,
                'encoding'  => $this->encoding,
                'docStatus' => $this->docStatus,
            ];
        }

        return [
            'success' => false,
            'errors'  => $this->errors,
            'message' => self::formatMessage($this->errors),
            'hints'   => $this->errors,
        ];
    }

    /** @param string[] $errors */
    public static function formatMessage(array $errors): string
    {
        return ValidationMessages::formatList($errors);
    }
}
