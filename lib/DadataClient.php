<?php

namespace Vendor\Xmldoc;

use Bitrix\Crm\AddressTable;
use Bitrix\Main\Application;
use Bitrix\Main\Web\HttpClient;
use Bitrix\Main\Web\Json;
use Vendor\Xmldoc\Address\RequisiteAddressResolver;

/**
 * Обогащение реквизитов через DaData по ИНН.
 * Заполняет только пустые поля — данные B24 в приоритете.
 */
class DadataClient
{
    private const API_URL = 'https://suggestions.dadata.ru/suggestions/api/4_1/rs/findById/party';

    public function __construct(
        private readonly ?string $apiKey = null,
    ) {
    }

    /** @param array<string, mixed> $requisite */
    public function enrich(array $requisite): array
    {
        $apiKey = $this->apiKey ?? Config::dadataApiKey();
        $inn = trim((string)($requisite['INN'] ?? ''));

        if ($apiKey === '' || $inn === '') {
            return $requisite;
        }

        $party = $this->fetchParty($apiKey, $inn);
        if ($party === null) {
            return $requisite;
        }

        return $this->merge($requisite, $party);
    }

    /** @return array<string, mixed>|null */
    private function fetchParty(string $apiKey, string $inn): ?array
    {
        $client = new HttpClient(['socketTimeout' => 10, 'streamTimeout' => 10]);
        $client->setHeader('Content-Type', 'application/json');
        $client->setHeader('Accept', 'application/json');
        $client->setHeader('Authorization', 'Token ' . $apiKey);

        $response = $client->post(self::API_URL, Json::encode(['query' => $inn]));
        if ($response === false) {
            return null;
        }

        try {
            $data = Json::decode($response);
        } catch (\Throwable) {
            return null;
        }

        return $data['suggestions'][0]['data'] ?? null;
    }

    /**
     * @param array<string, mixed> $requisite
     * @param array<string, mixed> $party
     * @return array<string, mixed>
     */
    private function merge(array $requisite, array $party): array
    {
        $map = [
            'NAME'          => $party['name']['short_with_opf'] ?? $party['name']['full_with_opf'] ?? '',
            'KPP'           => $party['kpp'] ?? '',
            'OGRN'          => $party['ogrn'] ?? $party['ogrnip'] ?? '',
            'ADDRESS_FULL'  => $party['address']['unrestricted_value'] ?? '',
        ];

        foreach ($map as $key => $value) {
            $value = trim((string)$value);
            if ($value === '') {
                continue;
            }
            if (empty($requisite[$key])) {
                $requisite[$key] = $value;
            }
        }

        $addr = $party['address']['data'] ?? [];
        $regionKladr = trim((string)($addr['region_kladr_id'] ?? ''));
        $regionCode = $regionKladr !== '' ? substr($regionKladr, 0, 2) : '';

        $parts = [
            'ADDRESS_POSTAL_CODE' => $addr['postal_code'] ?? '',
            'ADDRESS_REGION_CODE' => $regionCode,
            'ADDRESS_REGION'      => $addr['region_with_type'] ?? '',
            'ADDRESS_CITY'        => $addr['city_with_type'] ?? $addr['settlement_with_type'] ?? '',
            'ADDRESS_STREET'      => $addr['street_with_type'] ?? '',
            'ADDRESS_HOUSE'       => $addr['house'] ?? '',
            'ADDRESS_BUILDING'    => $addr['block'] ?? '',
            'ADDRESS_FLAT'        => $addr['flat'] ?? '',
        ];

        foreach ($parts as $key => $value) {
            $value = trim((string)$value);
            if ($value !== '' && empty($requisite[$key])) {
                $requisite[$key] = $value;
            }
        }

        $requisite['_DADATA_ENRICHED'] = true;

        return $requisite;
    }

    /**
     * Сохраняет обогащённые поля обратно в реквизит CRM.
     * @param array<string, mixed> $requisite
     */
    public function persistToCrm(int $requisiteId, array $requisite): void
    {
        if (empty($requisite['_DADATA_ENRICHED']) || !\CModule::IncludeModule('crm')) {
            return;
        }

        $fields = [];
        if (!empty($requisite['NAME'])) {
            $fields['RQ_COMPANY_NAME'] = $requisite['NAME'];
        }
        if (!empty($requisite['INN'])) {
            $fields['RQ_INN'] = $requisite['INN'];
        }
        if (!empty($requisite['KPP'])) {
            $fields['RQ_KPP'] = $requisite['KPP'];
        }
        if (!empty($requisite['OGRN'])) {
            $isIp = !empty($requisite['IS_IP']);
            $fields[$isIp ? 'RQ_OGRNIP' : 'RQ_OGRN'] = $requisite['OGRN'];
        }

        if ($fields !== [] && class_exists(\CCrmRequisite::class)) {
            $entity = new \CCrmRequisite();
            $entity->Update($requisiteId, $fields);
        }

        $this->persistAddress($requisiteId, $requisite);
    }

    /** @param array<string, mixed> $requisite */
    private function persistAddress(int $requisiteId, array $requisite): void
    {
        $hasAddress = false;
        foreach ([
            'ADDRESS_POSTAL_CODE', 'ADDRESS_REGION', 'ADDRESS_CITY',
            'ADDRESS_STREET', 'ADDRESS_HOUSE', 'ADDRESS_FLAT',
        ] as $key) {
            if (!empty($requisite[$key])) {
                $hasAddress = true;
                break;
            }
        }

        if (!$hasAddress) {
            return;
        }

        $entityTypeId = \CCrmOwnerType::Requisite;
        $typeId = RequisiteAddressResolver::TYPE_LEGAL;
        if (class_exists(\Bitrix\Crm\EntityAddressType::class)) {
            $registered = \Bitrix\Crm\EntityAddressType::Registered;
            if (is_numeric($registered)) {
                $typeId = (int)$registered;
            }
        }

        $fields = [
            'TYPE_ID'         => $typeId,
            'ENTITY_TYPE_ID'  => $entityTypeId,
            'ENTITY_ID'       => $requisiteId,
            'POSTAL_CODE'     => (string)($requisite['ADDRESS_POSTAL_CODE'] ?? ''),
            'PROVINCE'        => (string)($requisite['ADDRESS_REGION'] ?? ''),
            'PROVINCE_CODE'   => (string)($requisite['ADDRESS_REGION_CODE'] ?? ''),
            'CITY'            => (string)($requisite['ADDRESS_CITY'] ?? ''),
            'ADDRESS_1'       => (string)($requisite['ADDRESS_STREET'] ?? ''),
            'ADDRESS_2'       => (string)($requisite['ADDRESS_HOUSE'] ?? ''),
            'BUILDING'        => (string)($requisite['ADDRESS_BUILDING'] ?? ''),
            'APARTMENT'       => (string)($requisite['ADDRESS_FLAT'] ?? ''),
        ];

        if (!empty($requisite['ADDRESS_FULL'])) {
            $fields['ADDRESS_FULL'] = (string)$requisite['ADDRESS_FULL'];
        }

        $connection = Application::getConnection();
        $connection->startTransaction();

        try {
            $existing = AddressTable::getList([
                'filter' => [
                    '=ENTITY_TYPE_ID' => $entityTypeId,
                    '=ENTITY_ID'      => $requisiteId,
                    '=TYPE_ID'        => $typeId,
                ],
                'limit'  => 1,
            ])->fetch();

            if ($existing) {
                $updateResult = AddressTable::update([
                    'TYPE_ID'        => (int)($existing['TYPE_ID'] ?? $typeId),
                    'ENTITY_TYPE_ID' => (int)$entityTypeId,
                    'ENTITY_ID'      => $requisiteId,
                ], $fields);

                if (!$updateResult->isSuccess()) {
                    throw new \RuntimeException(implode('; ', $updateResult->getErrorMessages()));
                }
            } else {
                $addResult = AddressTable::add($fields);

                if (!$addResult->isSuccess()) {
                    throw new \RuntimeException(implode('; ', $addResult->getErrorMessages()));
                }
            }

            $connection->commitTransaction();
        } catch (\Throwable) {
            $connection->rollbackTransaction();
            // Сохранение адреса не должно блокировать генерацию УПД
        }
    }
}
