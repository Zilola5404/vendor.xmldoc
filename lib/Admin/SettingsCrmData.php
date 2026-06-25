<?php

namespace Ooofix\Xmlupd\Admin;

use Bitrix\Crm\CompanyTable;
use Bitrix\Crm\Model\Dynamic\TypeTable;
use Bitrix\Crm\RequisiteTable;
use Bitrix\Main\Loader;
use Bitrix\Main\UserTable;

/** Данные CRM для полей настроек (реквизиты, пользователи). */
final class SettingsCrmData
{
    /**
     * Список реквизитов CRM (аналог crm.requisite.list).
     *
     * @return list<array{id: int, title: string, inn: string, entityTypeId: int, entityId: int, isMyCompany: bool}>
     */
    public static function requisites(): array
    {
        if (!Loader::includeModule('crm') || !class_exists(RequisiteTable::class)) {
            return [];
        }

        $myCompanyIds = self::myCompanyIds();
        $items = [];

        try {
            $rows = RequisiteTable::getList([
                'order'  => ['ID' => 'ASC'],
                'select' => [
                    'ID',
                    'NAME',
                    'RQ_COMPANY_NAME',
                    'RQ_INN',
                    'RQ_KPP',
                    'ENTITY_TYPE_ID',
                    'ENTITY_ID',
                ],
                'limit'  => 1000,
            ]);

            while ($row = $rows->fetch()) {
                $items[] = self::mapRequisiteRow($row, $myCompanyIds);
            }
        } catch (\Throwable) {
            $items = [];
        }

        if ($items === []) {
            $items = self::requisitesViaEntityClass($myCompanyIds);
        }

        if ($items === []) {
            $items = self::requisitesViaLegacyApi($myCompanyIds);
        }

        usort($items, static function (array $a, array $b): int {
            return ($b['isMyCompany'] <=> $a['isMyCompany']) ?: ($a['id'] <=> $b['id']);
        });

        return $items;
    }

    /**
     * Реквизиты только из «Мои реквизиты» Bitrix24.
     *
     * @return list<array{id: int, title: string, inn: string, entityTypeId: int, entityId: int, isMyCompany: bool}>
     */
    public static function myCompanyRequisites(): array
    {
        return array_values(array_filter(
            self::requisites(),
            static fn(array $item): bool => !empty($item['isMyCompany'])
        ));
    }

    /**
     * Поиск реквизита по ИНН в «Мои реквизиты».
     *
     * @return list<array{id: int, title: string, inn: string, entityTypeId: int, entityId: int, isMyCompany: bool}>
     */
    public static function requisitesByInn(string $inn, bool $myCompanyOnly = true): array
    {
        $inn = self::normalizeInn($inn);
        if ($inn === '') {
            return [];
        }

        $source = $myCompanyOnly ? self::myCompanyRequisites() : self::requisites();
        $exact = [];
        $partial = [];

        foreach ($source as $item) {
            $itemInn = self::normalizeInn((string)($item['inn'] ?? ''));
            if ($itemInn === '') {
                continue;
            }
            if ($itemInn === $inn) {
                $exact[] = $item;
                continue;
            }
            if (str_starts_with($itemInn, $inn) || str_starts_with($inn, $itemInn)) {
                $partial[] = $item;
            }
        }

        return $exact !== [] ? $exact : $partial;
    }

    /** @return array{id: int, title: string, inn: string}|null */
    public static function requisiteById(int $requisiteId): ?array
    {
        if ($requisiteId <= 0) {
            return null;
        }

        foreach (self::requisites() as $item) {
            if ((int)$item['id'] === $requisiteId) {
                return $item;
            }
        }

        if (!Loader::includeModule('crm') || !class_exists(RequisiteTable::class)) {
            return null;
        }

        try {
            $row = RequisiteTable::getById($requisiteId)->fetch();
            if (!is_array($row)) {
                return null;
            }

            return self::mapRequisiteRow($row, self::myCompanyIds());
        } catch (\Throwable) {
            return null;
        }
    }

    public static function innByRequisiteId(int $requisiteId): string
    {
        $requisite = self::requisiteById($requisiteId);

        return $requisite !== null ? (string)($requisite['inn'] ?? '') : '';
    }

    public static function normalizeInn(string $inn): string
    {
        return preg_replace('/\D+/', '', trim($inn)) ?? '';
    }

    /**
     * @return list<array{id: int, title: string, position: string}>
     */
    public static function usersByPosition(string $position): array
    {
        $position = trim($position);
        if ($position === '') {
            return [];
        }

        $items = [];
        try {
            $filter = ['=ACTIVE' => 'Y'];
            if (class_exists(UserTable::class)) {
                $rows = UserTable::getList([
                    'filter' => array_merge($filter, ['%WORK_POSITION' => $position]),
                    'select' => ['ID', 'NAME', 'LAST_NAME', 'SECOND_NAME', 'WORK_POSITION'],
                    'order'  => ['LAST_NAME' => 'ASC', 'NAME' => 'ASC'],
                    'limit'  => 20,
                ]);
                while ($row = $rows->fetch()) {
                    $items[] = self::mapUserRow($row);
                }
            }
        } catch (\Throwable) {
            return [];
        }

        if ($items !== []) {
            return $items;
        }

        $rs = \CUser::GetList(
            'last_name',
            'asc',
            ['ACTIVE' => 'Y', 'WORK_POSITION' => $position],
            ['FIELDS' => ['ID', 'NAME', 'LAST_NAME', 'SECOND_NAME', 'WORK_POSITION']]
        );
        while ($row = $rs->Fetch()) {
            $items[] = self::mapUserRow($row);
        }

        return $items;
    }

    /** @return array<int, string> */
    public static function dynamicTypes(): array
    {
        return SettingsCrmLabels::dynamicTypes();
    }

    /** @param array<string, mixed> $row */
    private static function mapRequisiteRow(array $row, array $myCompanyIds): array
    {
        $entityId = (int)($row['ENTITY_ID'] ?? 0);

        return [
            'id'           => (int)$row['ID'],
            'title'        => self::formatRequisiteTitle($row),
            'inn'          => trim((string)($row['RQ_INN'] ?? '')),
            'entityTypeId' => (int)($row['ENTITY_TYPE_ID'] ?? 0),
            'entityId'     => $entityId,
            'isMyCompany'  => in_array($entityId, $myCompanyIds, true),
        ];
    }

    /** @param list<int> $myCompanyIds */
    private static function requisitesViaEntityClass(array $myCompanyIds): array
    {
        if (!class_exists(\Bitrix\Crm\EntityRequisite::class)) {
            return [];
        }

        $items = [];
        try {
            $entity = \Bitrix\Crm\EntityRequisite::getSingleInstance();
            $rows = $entity->getList([
                'order'  => ['ID' => 'ASC'],
                'select' => ['ID', 'NAME', 'RQ_COMPANY_NAME', 'RQ_INN', 'RQ_KPP', 'ENTITY_TYPE_ID', 'ENTITY_ID'],
                'limit'  => 1000,
            ]);

            while ($row = $rows->fetch()) {
                $items[] = self::mapRequisiteRow($row, $myCompanyIds);
            }
        } catch (\Throwable) {
            return [];
        }

        return $items;
    }

    /** @param list<int> $myCompanyIds */
    private static function requisitesViaLegacyApi(array $myCompanyIds): array
    {
        if (!class_exists(\CCrmRequisite::class)) {
            return [];
        }

        $items = [];
        $rs = \CCrmRequisite::GetList(
            ['ID' => 'ASC'],
            [],
            false,
            ['nTopCount' => 1000],
            ['ID', 'NAME', 'RQ_COMPANY_NAME', 'RQ_INN', 'RQ_KPP', 'ENTITY_TYPE_ID', 'ENTITY_ID']
        );

        while (is_object($rs) && ($row = $rs->Fetch())) {
            $items[] = self::mapRequisiteRow($row, $myCompanyIds);
        }

        return $items;
    }

    /** @param array<string, mixed> $row */
    private static function formatRequisiteTitle(array $row): string
    {
        $name = trim((string)($row['RQ_COMPANY_NAME'] ?? $row['NAME'] ?? ''));
        $inn = trim((string)($row['RQ_INN'] ?? ''));
        $title = $name !== '' ? $name : 'Реквизит #' . (int)($row['ID'] ?? 0);

        if ($inn !== '') {
            $title .= ' (ИНН ' . $inn . ')';
        }

        return $title;
    }

    /** @return list<int> */
    private static function myCompanyIds(): array
    {
        if (!Loader::includeModule('crm') || !class_exists(CompanyTable::class)) {
            return [];
        }

        $ids = [];
        try {
            $rows = CompanyTable::getList([
                'filter' => ['=IS_MY_COMPANY' => 'Y'],
                'select' => ['ID'],
            ]);
            while ($row = $rows->fetch()) {
                $ids[] = (int)$row['ID'];
            }
        } catch (\Throwable) {
            return [];
        }

        return $ids;
    }

    /** @param array<string, mixed> $row */
    private static function mapUserRow(array $row): array
    {
        $id = (int)($row['ID'] ?? 0);
        $name = trim(implode(' ', array_filter([
            (string)($row['LAST_NAME'] ?? ''),
            (string)($row['NAME'] ?? ''),
            (string)($row['SECOND_NAME'] ?? ''),
        ])));

        return [
            'id'       => $id,
            'title'    => $name !== '' ? $name : 'ID ' . $id,
            'position' => trim((string)($row['WORK_POSITION'] ?? '')),
        ];
    }
}
