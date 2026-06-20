<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Bizproc\Activity\PropertiesDialog;
use Bitrix\Bizproc\FieldType;
use Bitrix\Main\Localization\Loc;
use Vendor\Xmldoc\Bp\EntityResolver;
use Vendor\Xmldoc\CrmPermissions;
use Vendor\Xmldoc\GenerateService;

IncludeModuleLangFile(__FILE__);

/** Базовый класс activity: BaseActivity (Б24 20+) или CBPActivity (legacy). XMLDOC-20, XMLDOC-21 */
if (class_exists(\Bitrix\Bizproc\Activity\BaseActivity::class)) {
    abstract class XmldocGenerateUpdBase extends \Bitrix\Bizproc\Activity\BaseActivity
    {
        protected static $requiredModules = ['vendor.xmldoc', 'crm'];
    }
} else {
    abstract class XmldocGenerateUpdBase extends CBPActivity
    {
    }
}

class CBPXmldocGenerateUpd extends XmldocGenerateUpdBase
{
    protected static function getFileName(): string
    {
        return __FILE__;
    }

    public function __construct($name)
    {
        parent::__construct($name);

        $this->arProperties = [
            'Title'           => '',
            'SkipPermissions' => 'N',
            'Success'         => false,
            'FileId'          => null,
            'FileName'        => '',
            'Version'         => null,
            'Message'         => '',
            'Errors'          => '',
        ];

        $this->SetPropertiesTypes([
            'SkipPermissions' => ['Type' => FieldType::BOOL],
            'Success'         => ['Type' => FieldType::BOOL],
            'FileId'          => ['Type' => FieldType::INT],
            'FileName'        => ['Type' => FieldType::STRING],
            'Version'         => ['Type' => FieldType::INT],
            'Message'         => ['Type' => FieldType::STRING],
            'Errors'          => ['Type' => FieldType::STRING],
        ]);
    }

    /**
     * Карта свойств робота / activity. XMLDOC-21
     * Пустая карта — робот добавляется одним кликом в CRM (без перехода в дизайнер БП).
     *
     * @return array<string, array<string, mixed>>
     */
    public static function getPropertiesDialogMap(?PropertiesDialog $dialog = null): array
    {
        return [];
    }

    /** Алиас для совместимости с дизайнером БП (PascalCase). */
    public static function GetPropertiesDialog(
        $documentType,
        $activityName,
        $arWorkflowTemplate,
        $arWorkflowParameters,
        $arWorkflowVariables,
        $arCurrentValues = null,
        $formName = '',
        $popupWindow = null,
        $siteId = ''
    ) {
        if (is_subclass_of(static::class, \Bitrix\Bizproc\Activity\BaseActivity::class)) {
            return parent::GetPropertiesDialog(
                $documentType,
                $activityName,
                $arWorkflowTemplate,
                $arWorkflowParameters,
                $arWorkflowVariables,
                $arCurrentValues,
                $formName,
                $popupWindow,
                $siteId
            );
        }

        return '';
    }

    public static function GetPropertiesDialogValues(
        $documentType,
        $activityName,
        &$arWorkflowTemplate,
        &$arWorkflowParameters,
        &$arWorkflowVariables,
        $arCurrentValues,
        &$errors
    ) {
        if (is_subclass_of(static::class, \Bitrix\Bizproc\Activity\BaseActivity::class)) {
            return parent::GetPropertiesDialogValues(
                $documentType,
                $activityName,
                $arWorkflowTemplate,
                $arWorkflowParameters,
                $arWorkflowVariables,
                $arCurrentValues,
                $errors
            );
        }

        return true;
    }

    protected function internalExecute(): int
    {
        return $this->executeGeneration();
    }

    public function Execute()
    {
        if (is_subclass_of(static::class, \Bitrix\Bizproc\Activity\BaseActivity::class)) {
            return parent::Execute();
        }

        return $this->executeGeneration();
    }

    private function executeGeneration(): int
    {
        if (!\Bitrix\Main\Loader::includeModule('vendor.xmldoc') || !\Bitrix\Main\Loader::includeModule('crm')) {
            return $this->fail('Модуль vendor.xmldoc или CRM не установлен', [], true);
        }

        try {
            [$entityType, $entityId] = EntityResolver::fromDocumentId($this->GetDocumentId());
        } catch (\Throwable $e) {
            return $this->fail($e->getMessage(), [], true);
        }

        $skipPermissions = in_array($this->SkipPermissions, ['Y', true, 1, '1'], true);

        if (!$skipPermissions && !CrmPermissions::canGenerate($entityType, $entityId)) {
            return $this->fail(CrmPermissions::getDenyMessage(), [], true);
        }

        $result = (new GenerateService())->run($entityType, $entityId, !$skipPermissions)->toArray();

        if (!empty($result['success'])) {
            $this->Success = true;
            $this->FileId = (int)($result['fileId'] ?? 0);
            $this->FileName = (string)($result['fileName'] ?? '');
            $this->Version = (int)($result['version'] ?? 0);
            $this->Message = 'УПД сформирован';
            $this->Errors = '';

            $this->WriteToTrackingService(
                Loc::getMessage('VENDOR_XMLDOC_BP_TRACK_OK', [
                    '#FILE#' => $this->FileName,
                    '#VER#'  => $this->Version,
                ]),
                0,
                CBPTrackingType::Report
            );

            return CBPActivityExecutionStatus::Closed;
        }

        $errors = $result['errors'] ?? [$result['message'] ?? 'Неизвестная ошибка'];
        $message = (string)($result['message'] ?? implode('; ', $errors));

        return $this->fail($message, $errors, true);
    }

    /** @param string[] $errors */
    private function fail(string $message, array $errors = [], bool $isFault = false): int
    {
        $this->Success = false;
        $this->FileId = null;
        $this->FileName = '';
        $this->Version = null;
        $this->Message = $message;
        $this->Errors = $errors !== [] ? implode("\n", $errors) : $message;

        $this->WriteToTrackingService(
            Loc::getMessage('VENDOR_XMLDOC_BP_TRACK_ERROR', ['#MSG#' => $message]),
            0,
            CBPTrackingType::Error
        );

        return $isFault ? CBPActivityExecutionStatus::Fault : CBPActivityExecutionStatus::Closed;
    }

    public static function ValidateProperties($testProperties = [], CBPWorkflowTemplateUser $user = null)
    {
        if (is_subclass_of(static::class, \Bitrix\Bizproc\Activity\BaseActivity::class)) {
            return parent::ValidateProperties($testProperties, $user);
        }

        return [];
    }
}
