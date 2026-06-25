<?php

namespace Ooofix\Xmlupd\Install;

use Bitrix\Crm\Service\Container;
use Bitrix\Main\Loader;

/** Создание UF_UPD_* для сделок и смарт-процесса «Счета». */
final class UserFieldInstaller
{
    public static function installForSmartType(int $smartTypeId): void
    {
        if ($smartTypeId <= 0 || !Loader::includeModule('crm')) {
            return;
        }

        $entityId = self::resolveUserFieldEntityId($smartTypeId);
        self::ensureUserField($entityId, 'UF_UPD_NUMBER', 'string', 'Номер УПД (1С)');
        self::ensureUserField($entityId, 'UF_UPD_FILE', 'file', 'Файл УПД');
    }

    public static function installForDeals(): void
    {
        self::ensureUserField('CRM_DEAL', 'UF_UPD_NUMBER', 'string', 'Номер УПД (1С)');
        self::ensureUserField('CRM_DEAL', 'UF_UPD_FILE', 'file', 'Файл УПД');
    }

    public static function installAll(int $smartTypeId): void
    {
        self::installForDeals();
        self::installForSmartType($smartTypeId);
    }

    public static function resolveUserFieldEntityId(int $entityTypeId): string
    {
        $fallback = 'CRM_' . $entityTypeId;

        if (!Loader::includeModule('crm')) {
            return $fallback;
        }

        $factory = Container::getInstance()->getFactory($entityTypeId);
        if ($factory !== null && method_exists($factory, 'getUserFieldEntityId')) {
            $entityId = trim((string)$factory->getUserFieldEntityId());
            if ($entityId !== '') {
                return $entityId;
            }
        }

        return $fallback;
    }

    public static function fieldExists(string $entityId, string $fieldName): bool
    {
        if (!class_exists(\CUserTypeEntity::class)) {
            return false;
        }

        return (bool)\CUserTypeEntity::GetList([], [
            'ENTITY_ID'  => $entityId,
            'FIELD_NAME' => $fieldName,
        ])->Fetch();
    }

    private static function ensureUserField(string $entityId, string $fieldName, string $type, string $label): void
    {
        if (self::fieldExists($entityId, $fieldName)) {
            return;
        }

        $uf = new \CUserTypeEntity();
        $uf->Add([
            'ENTITY_ID'         => $entityId,
            'FIELD_NAME'        => $fieldName,
            'USER_TYPE_ID'      => $type,
            'MULTIPLE'          => 'N',
            'MANDATORY'         => 'N',
            'EDIT_FORM_LABEL'   => ['ru' => $label, 'en' => $label],
            'LIST_COLUMN_LABEL' => ['ru' => $label, 'en' => $label],
        ]);
    }
}
