<?php

namespace Vendor\Xmldoc;

use Bitrix\Crm\Integration\BizProc\Document\Dynamic;
use Bitrix\Crm\Integration\BizProc\Document\SmartInvoice;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;

/**
 * Реестр поддерживаемых типов CRM-сущностей для генерации УПД и автоматизации.
 * XMLDOC-25
 */
final class DocumentTypeRegistry
{
    public const TYPE_DEAL          = DataCollector::TYPE_DEAL;
    public const TYPE_SMART_INVOICE = DataCollector::TYPE_SMART_INVOICE;

    /** @var array<string, array<string, mixed>>|null */
    private static ?array $definitions = null;

    public static function resetCache(): void
    {
        self::$definitions = null;
    }

    /** @return array<string, array<string, mixed>> */
    public static function all(): array
    {
        if (self::$definitions !== null) {
            return self::$definitions;
        }

        $smartTypeId = Config::smartInvoiceTypeId();

        self::$definitions = [
            self::TYPE_DEAL => [
                'entityType'    => self::TYPE_DEAL,
                'ownerTypeId'   => \CCrmOwnerType::Deal,
                'ownerTypeName' => 'DEAL',
                'label'         => 'Сделка',
                'bpDocument'    => 'CCrmDocumentDeal',
                'automation'    => true,
            ],
            self::TYPE_SMART_INVOICE => [
                'entityType'    => self::TYPE_SMART_INVOICE,
                'ownerTypeId'   => $smartTypeId > 0 ? $smartTypeId : \CCrmOwnerType::SmartInvoice,
                'ownerTypeName' => $smartTypeId > 0 ? 'DYNAMIC_' . $smartTypeId : 'SMART_INVOICE',
                'label'         => 'Смарт-процесс «Счета»',
                'bpDocument'    => 'CCrmDocumentDynamic',
                'automation'    => true,
            ],
        ];

        return self::$definitions;
    }

    /** @return array<string, mixed>|null */
    public static function get(string $entityType): ?array
    {
        return self::all()[$entityType] ?? null;
    }

    public static function exists(string $entityType): bool
    {
        return isset(self::all()[$entityType]);
    }

    public static function getOwnerTypeId(string $entityType): int
    {
        $def = self::get($entityType);
        if ($def === null) {
            throw new \InvalidArgumentException('Неподдерживаемый тип сущности: ' . $entityType);
        }

        return (int)$def['ownerTypeId'];
    }

    public static function isAutomationSupported(int $ownerTypeId): bool
    {
        foreach (self::all() as $def) {
            if ((int)$def['ownerTypeId'] === $ownerTypeId && !empty($def['automation'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * FILTER для activity/robot (.description.php).
     *
     * @return list<array{0: string, 1?: string, 2?: string}>
     */
    public static function buildBpFilterInclude(): array
    {
        Loader::includeModule('crm');

        $smartTypeId = Config::smartInvoiceTypeId();

        $filter = [
            ['crm', 'CCrmDocumentDeal', 'DEAL'],
            ['crm', 'CCrmDocumentLead', 'LEAD'],
            ['crm', 'CCrmDocumentContact', 'CONTACT'],
            ['crm', 'CCrmDocumentCompany', 'COMPANY'],
            ['crm', Dynamic::class],
            ['crm', 'CCrmDocumentDynamic'],
        ];

        if ($smartTypeId > 0) {
            $dynamicKey = 'DYNAMIC_' . $smartTypeId;
            $filter[] = ['crm', Dynamic::class, $dynamicKey];
            $filter[] = ['crm', 'CCrmDocumentDynamic', $dynamicKey];
        }

        if (class_exists(SmartInvoice::class)) {
            $filter[] = ['crm', SmartInvoice::class, 'SMART_INVOICE'];
        }

        return $filter;
    }

    /** Группа робота CRM — «Другие роботы» (совместимо с актуальным UI). XMLDOC-22 */
    public static function robotGroup(): array
    {
        return ['other'];
    }
}
