<?php

namespace Vendor\Xmldoc;

use Vendor\Xmldoc\Automation\TriggerService;
use Vendor\Xmldoc\Documents\Upd\UpdBuilder;
use Vendor\Xmldoc\Dto\EntityContextDto;
use Vendor\Xmldoc\Dto\GenerateRequestDto;

/** Оркестратор: сбор данных → валидация → XML → сохранение */
class GenerateService
{
    public function __construct(
        private readonly DataCollector $collector = new DataCollector(),
        private readonly UpdBuilder $builder = new UpdBuilder(),
        private readonly XmlValidator $xmlValidator = new XmlValidator(),
        private readonly FileSaver $fileSaver = new FileSaver(),
        private readonly DadataClient $dadata = new DadataClient(),
    ) {
    }

    public function runFromDto(GenerateRequestDto $request): GenerateResult
    {
        $entityType = $request->context->entityType;
        $entityId = $request->context->entityId;

        Logger::write($entityType, $entityId, Logger::STATUS_STARTED, 'Запуск генерации УПД');

        try {
            \Bitrix\Main\Loader::includeModule('crm');

            if ($request->checkPermissions && !CrmPermissions::canGenerate($entityType, $entityId)) {
                $msg = CrmPermissions::getDenyMessage();
                Logger::write($entityType, $entityId, Logger::STATUS_ERROR, $msg);

                return GenerateResult::fail([$msg]);
            }

            $crmData = $this->collector->collect($entityType, $entityId);

            $preErrors = ValidationMessages::preValidate($crmData, $entityType);
            if ($preErrors !== []) {
                $msg = ValidationMessages::formatList($preErrors);
                Logger::write($entityType, $entityId, Logger::STATUS_VALIDATE, $msg);

                return GenerateResult::fail($preErrors);
            }

            $result = $this->builder->process($crmData);

            if (!$result['success']) {
                $errors = $result['errors'] ?? ['Неизвестная ошибка'];
                $msg = GenerateResult::formatMessage($errors);
                Logger::write($entityType, $entityId, Logger::STATUS_VALIDATE, $msg);

                return GenerateResult::fail($errors);
            }

            $xml = (string)$result['xml'];
            $xmlCheck = $this->xmlValidator->validateDetailed($xml);
            if (!$xmlCheck['valid']) {
                $details = $xmlCheck['errors'] !== []
                    ? implode('; ', $xmlCheck['errors'])
                    : 'структура некорректна';
                $msg = 'XML не прошёл проверку: ' . $details;
                Logger::write($entityType, $entityId, Logger::STATUS_ERROR, $msg);

                return GenerateResult::fail([$msg]);
            }

            $mapped = $result['mapped'] ?? [];
            $entityTypeId = (int)($crmData['entity']['ENTITY_TYPE_ID'] ?? \CCrmOwnerType::Deal);
            $docNumber = (string)($mapped['doc_number'] ?? $entityId);

            $file = $this->fileSaver->save(
                $entityType,
                $entityId,
                $entityTypeId,
                $xml,
                $docNumber
            );

            $this->persistBuyerEnrichment($crmData);

            Logger::write(
                $entityType,
                $entityId,
                Logger::STATUS_SUCCESS,
                sprintf(
                    'УПД сформирован: %s, версия %d, %s',
                    $file['fileName'],
                    $file['version'] ?? 1,
                    $file['encoding'] ?? 'windows-1251'
                )
            );

            $generateResult = GenerateResult::ok(
                (int)$file['fileId'],
                (string)$file['fileName'],
                (int)($file['version'] ?? 1),
                (string)($file['encoding'] ?? 'windows-1251'),
                (string)($file['docStatus'] ?? DocumentStatus::GENERATED)
            );

            TriggerService::fireUpdGenerated($entityType, $entityId);

            return $generateResult;
        } catch (\Throwable $e) {
            Logger::write($entityType, $entityId, Logger::STATUS_ERROR, $e->getMessage());

            return GenerateResult::fail([$e->getMessage()]);
        }
    }

    /** @param array<string, mixed> $crmData */
    private function persistBuyerEnrichment(array $crmData): void
    {
        $buyer = $crmData['buyer'] ?? [];
        if (empty($buyer['REQUISITE_ID']) || empty($buyer['_DADATA_ENRICHED'])) {
            return;
        }

        try {
            $this->dadata->persistToCrm((int)$buyer['REQUISITE_ID'], $buyer);
        } catch (\Throwable) {
            // Обогащение CRM не должно отменять успешную генерацию
        }
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
