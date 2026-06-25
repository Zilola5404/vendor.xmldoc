<?php

namespace Ooofix\Xmlupd;

use Bitrix\Crm\CompanyTable;
use Bitrix\Crm\RequisiteTable;
use Bitrix\Main\Loader;

/** Определение ID реквизита продавца («Мои реквизиты») с учётом версии B24 */
class SellerRequisiteResolver
{
    public static function resolveRequisiteId(): int
    {
        $configured = Config::sellerRequisiteId();
        if ($configured > 0) {
            return $configured;
        }

        return self::resolveDefaultMyCompanyRequisiteId();
    }

    private static function resolveDefaultMyCompanyRequisiteId(): int
    {
        foreach (self::entityLinkClasses() as $className) {
            if (method_exists($className, 'getDefaultMyCompanyRequisiteId')) {
                $id = (int)$className::getDefaultMyCompanyRequisiteId();
                if ($id > 0) {
                    return $id;
                }
            }
        }

        $companyId = self::resolveMyCompanyId();
        if ($companyId <= 0) {
            return 0;
        }

        return self::fetchFirstRequisiteIdForCompany($companyId);
    }

    /** @return list<class-string> */
    private static function entityLinkClasses(): array
    {
        $candidates = [
            'Bitrix\\Crm\\Requisite\\EntityLink',
            'Bitrix\\Crm\\Entity\\Requisite\\EntityLink',
        ];

        return array_values(array_filter($candidates, 'class_exists'));
    }

    private static function resolveMyCompanyId(): int
    {
        foreach (self::entityLinkClasses() as $className) {
            if (method_exists($className, 'getDefaultMyCompanyId')) {
                $id = (int)$className::getDefaultMyCompanyId();
                if ($id > 0) {
                    return $id;
                }
            }
        }

        Loader::includeModule('crm');

        if (class_exists(CompanyTable::class)) {
            $row = CompanyTable::getList([
                'filter' => ['=IS_MY_COMPANY' => 'Y'],
                'order'  => ['ID' => 'ASC'],
                'limit'  => 1,
                'select' => ['ID'],
            ])->fetch();

            if ($row) {
                return (int)$row['ID'];
            }
        }

        if (class_exists(\CCrmCompany::class)) {
            $db = \CCrmCompany::GetListEx(
                ['ID' => 'ASC'],
                ['IS_MY_COMPANY' => 'Y', 'CHECK_PERMISSIONS' => 'N'],
                false,
                ['nTopCount' => 1],
                ['ID']
            );
            if ($row = $db->Fetch()) {
                return (int)$row['ID'];
            }
        }

        return 0;
    }

    private static function fetchFirstRequisiteIdForCompany(int $companyId): int
    {
        Loader::includeModule('crm');

        $entityTypeId = defined('\CCrmOwnerType::Company')
            ? \CCrmOwnerType::Company
            : 4;

        $row = RequisiteTable::getList([
            'filter' => [
                '=ENTITY_TYPE_ID' => $entityTypeId,
                '=ENTITY_ID'      => $companyId,
            ],
            'order'  => ['SORT' => 'ASC', 'ID' => 'ASC'],
            'limit'  => 1,
            'select' => ['ID'],
        ])->fetch();

        return $row ? (int)$row['ID'] : 0;
    }
}
