<?php

namespace Vendor\Xmldoc\Cloud;

use Bitrix\Crm\Service\Container;
use Vendor\Xmldoc\DataCollector;

/**
 * Сбор данных CRM через Service API (Factory) — предпочтительно для облака.
 */
final class CloudDataCollector extends DataCollector
{
    /** @return array<string, mixed> */
    protected function fetchDeal(int $dealId): array
    {
        $entityTypeId = \CCrmOwnerType::Deal;
        $factory = Container::getInstance()->getFactory($entityTypeId);
        if ($factory === null) {
            throw new \RuntimeException('CRM Factory для сделок недоступен');
        }

        $item = $factory->getItem($dealId);
        if ($item === null) {
            throw new \RuntimeException('Сделка не найдена: ' . $dealId);
        }

        $data = $item->getData();
        $docDate = date('d.m.Y');
        $opportunity = (float)($data['OPPORTUNITY'] ?? 0);
        $taxValue = round((float)($data['TAX_VALUE'] ?? 0), 2);

        return [
            'ID'              => $dealId,
            'ENTITY_TYPE_ID'  => $entityTypeId,
            'ENTITY_TYPE'     => self::TYPE_DEAL,
            'CATEGORY_ID'     => (int)($data['CATEGORY_ID'] ?? 0),
            'COMPANY_ID'      => (int)($data['COMPANY_ID'] ?? 0),
            'UF_UPD_NUMBER'   => (string)($data['UF_UPD_NUMBER'] ?? ''),
            'DOC_DATE'        => $docDate,
            'OPPORTUNITY'     => $opportunity,
            'TAX_VALUE'       => $taxValue,
            'TOTAL_GROSS'     => round($opportunity, 2),
            'TOTAL_NET'       => round($opportunity - $taxValue, 2),
            'TOTAL_TAX'       => $taxValue,
            'USER_FIELDS'     => $this->extractUserFields($data, ['UF_UPD_NUMBER', 'UF_UPD_FILE']),
        ];
    }
}
