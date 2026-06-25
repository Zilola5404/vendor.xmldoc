<?php

namespace Ooofix\Xmlupd\Admin;

use Bitrix\Crm\Model\Dynamic\TypeTable;
use Bitrix\Crm\RequisiteTable;
use Bitrix\Main\Loader;

/** Подписи CRM-сущностей для полей настроек. */
final class SettingsCrmLabels
{
  /** @return array<string, string> */
    public static function displayMap(array $values): array
    {
        $map = [];

        $userId = (int)($values['signatory_user_id'] ?? 0);
        if ($userId > 0) {
            $map['signatory_user_id'] = self::userLabel($userId);
        }

        $requisiteId = (int)($values['seller_requisite_id'] ?? 0);
        if ($requisiteId > 0) {
            $map['seller_requisite_id'] = self::requisiteLabel($requisiteId);
        }

        $typeId = (int)($values['smart_invoice_type_id'] ?? 0);
        if ($typeId > 0) {
            $map['smart_invoice_type_id'] = self::dynamicTypeLabel($typeId);
        }

        return $map;
    }

    /** @return array<int, string> */
    public static function dynamicTypes(): array
    {
        if (!Loader::includeModule('crm') || !class_exists(TypeTable::class)) {
            return [];
        }

        $types = [];
        try {
            $rows = TypeTable::getList([
                'order'  => ['TITLE' => 'ASC'],
                'select' => ['ENTITY_TYPE_ID', 'TITLE', 'CODE'],
            ]);

            while ($row = $rows->fetch()) {
                $typeId = (int)($row['ENTITY_TYPE_ID'] ?? 0);
                if ($typeId <= 0) {
                    continue;
                }

                $title = trim((string)($row['TITLE'] ?? ''));
                $code = trim((string)($row['CODE'] ?? ''));
                $types[$typeId] = $title !== '' ? $title : ($code !== '' ? $code : 'Тип ' . $typeId);
            }
        } catch (\Throwable) {
            return [];
        }

        return $types;
    }

    private static function userLabel(int $userId): string
    {
        $rs = \CUser::GetByID($userId);
        $user = is_object($rs) ? $rs->Fetch() : false;
        if (!is_array($user)) {
            return 'ID ' . $userId;
        }

        $name = trim(implode(' ', array_filter([
            (string)($user['LAST_NAME'] ?? ''),
            (string)($user['NAME'] ?? ''),
            (string)($user['SECOND_NAME'] ?? ''),
        ])));

        return $name !== '' ? $name . ' [' . $userId . ']' : 'ID ' . $userId;
    }

    private static function requisiteLabel(int $requisiteId): string
    {
        if (!Loader::includeModule('crm') || !class_exists(RequisiteTable::class)) {
            return 'ID ' . $requisiteId;
        }

        $row = RequisiteTable::getById($requisiteId)->fetch();
        if (!is_array($row)) {
            return 'ID ' . $requisiteId;
        }

        $name = trim((string)($row['RQ_COMPANY_NAME'] ?? $row['NAME'] ?? ''));
        $inn = trim((string)($row['RQ_INN'] ?? ''));

        $label = $name !== '' ? $name : 'Реквизит';
        if ($inn !== '') {
            $label .= ' (ИНН ' . $inn . ')';
        }

        return $label . ' [' . $requisiteId . ']';
    }

    private static function dynamicTypeLabel(int $typeId): string
    {
        foreach (self::dynamicTypes() as $id => $title) {
            if ($id === $typeId) {
                return $title . ' [' . $typeId . ']';
            }
        }

        return 'entityTypeId ' . $typeId;
    }
}
