<?php

namespace Vendor\Xmldoc\Cloud;

use Bitrix\Crm\Service\Container;
use Vendor\Xmldoc\Crm\RequisiteAddressResolver;
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

        return $this->buildDealEntityFromRow($item->getData(), $dealId);
    }

    /** @return array<string, mixed> */
    protected function fetchBuyer(int $companyId): array
    {
        $entityTypeId = \CCrmOwnerType::Company;
        $factory = Container::getInstance()->getFactory($entityTypeId);
        if ($factory === null) {
            return parent::fetchBuyer($companyId);
        }

        $item = $factory->getItem($companyId);
        if ($item === null) {
            return [];
        }

        $data = $item->getData();
        $title = (string)($data['TITLE'] ?? '');

        $requisite = $this->fetchRequisite($entityTypeId, $companyId);
        if ($requisite === []) {
            return [
                'COMPANY_ID' => $companyId,
                'NAME'       => $title,
            ];
        }

        $requisiteId = (int)($requisite['REQUISITE_ID'] ?? 0);
        $bank = $this->fetchBankDetails($requisiteId);
        $address = RequisiteAddressResolver::fetchLegalAddress(
            \CCrmOwnerType::Requisite,
            $requisiteId
        );

        return array_merge($requisite, $bank, $address, [
            'COMPANY_ID' => $companyId,
            'NAME'       => $requisite['NAME'] ?: $title,
        ]);
    }
}
