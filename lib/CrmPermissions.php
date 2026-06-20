<?php

namespace Vendor\Xmldoc;

use Bitrix\Crm\Service\Container;
use Bitrix\Main\Loader;

/** Проверка прав CRM перед генерацией УПД */
class CrmPermissions
{
    public static function canGenerate(string $entityType, int $entityId): bool
    {
        if ($entityId <= 0) {
            return false;
        }

        Loader::includeModule('crm');

        global $USER;
        if (!$USER instanceof \CUser || !$USER->IsAuthorized()) {
            return false;
        }

        if ($entityType === DataCollector::TYPE_DEAL) {
            return self::canUpdateDeal($entityId);
        }

        if ($entityType === DataCollector::TYPE_SMART_INVOICE) {
            return self::canUpdateSmartItem($entityId);
        }

        return false;
    }

    public static function getDenyMessage(): string
    {
        return 'Недостаточно прав для генерации УПД по этой карточке CRM';
    }

    private static function canUpdateDeal(int $dealId): bool
    {
        if (!class_exists(\CCrmDeal::class)) {
            return false;
        }

        if (method_exists(\CCrmDeal::class, 'CheckUpdatePermission')) {
            return (bool)\CCrmDeal::CheckUpdatePermission($dealId);
        }

        if (method_exists(\CCrmDeal::class, 'CheckReadPermission')) {
            return (bool)\CCrmDeal::CheckReadPermission($dealId);
        }

        return false;
    }

    private static function canUpdateSmartItem(int $itemId): bool
    {
        $typeId = Config::smartInvoiceTypeId();
        if ($typeId <= 0 || !class_exists(Container::class)) {
            return false;
        }

        $container = Container::getInstance();
        $userPermissions = $container->getUserPermissions();

        if (method_exists($userPermissions, 'checkUpdatePermissions')) {
            return (bool)$userPermissions->checkUpdatePermissions($typeId, $itemId);
        }

        if (method_exists($userPermissions, 'entityCanUpdate')) {
            return (bool)$userPermissions->entityCanUpdate($typeId, $itemId);
        }

        if (method_exists($userPermissions, 'checkReadPermissions')) {
            return (bool)$userPermissions->checkReadPermissions($typeId, $itemId);
        }

        return false;
    }
}
