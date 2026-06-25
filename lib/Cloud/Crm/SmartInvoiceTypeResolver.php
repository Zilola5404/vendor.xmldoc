<?php

namespace Ooofix\Xmlupd\Cloud\Crm;

use Bitrix\Crm\Model\Dynamic\TypeTable;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Ooofix\Xmlupd\ModuleInfo;

/** Поиск entityTypeId СП «Счета» (на облаке ID часто ≠ 31). */
final class SmartInvoiceTypeResolver
{
    private const MODULE = ModuleInfo::MODULE_ID;
    private const TITLE_HINTS = ['счет', 'счета', 'invoice', 'smart_invoice'];

    public static function resolveConfiguredOrDetect(): int
    {
        $configured = (int)Option::get(self::MODULE, 'smart_invoice_type_id', '31');

        return self::resolveActiveTypeId($configured);
    }

    public static function resolveActiveTypeId(int $configured): int
    {
        if ($configured > 0 && self::factoryExists($configured)) {
            return $configured;
        }

        $detected = self::detectFromCrm();
        if ($detected > 0) {
            return $detected;
        }

        return $configured > 0 ? $configured : 31;
    }

    public static function detectFromCrm(): int
    {
        if (!Loader::includeModule('crm')) {
            return 0;
        }

        if (defined('\CCrmOwnerType::SmartInvoice')) {
            $smartInvoiceId = (int)\CCrmOwnerType::SmartInvoice;
            if ($smartInvoiceId > 0 && self::factoryExists($smartInvoiceId)) {
                return $smartInvoiceId;
            }
        }

        if (!class_exists(TypeTable::class)) {
            return 0;
        }

        try {
            $rows = TypeTable::getList([
                'order'  => ['ID' => 'ASC'],
                'select' => ['ENTITY_TYPE_ID', 'TITLE', 'CODE'],
            ]);

            while ($row = $rows->fetch()) {
                $typeId = (int)($row['ENTITY_TYPE_ID'] ?? 0);
                if ($typeId <= 0 || !self::factoryExists($typeId)) {
                    continue;
                }

                $haystack = mb_strtolower(implode(' ', array_filter([
                    (string)($row['TITLE'] ?? ''),
                    (string)($row['CODE'] ?? ''),
                ])));

                foreach (self::TITLE_HINTS as $hint) {
                    if (str_contains($haystack, $hint)) {
                        return $typeId;
                    }
                }
            }
        } catch (\Throwable) {
            return 0;
        }

        return 0;
    }

    private static function factoryExists(int $entityTypeId): bool
    {
        if (!class_exists(\Bitrix\Crm\Service\Container::class)) {
            return false;
        }

        return \Bitrix\Crm\Service\Container::getInstance()->getFactory($entityTypeId) !== null;
    }
}
