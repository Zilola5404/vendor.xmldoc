<?php

namespace Ooofix\Xmlupd;

use Bitrix\Crm\BankDetailTable;
use Bitrix\Crm\ProductRowTable;
use Bitrix\Crm\RequisiteTable;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\Loader;
use Bitrix\Main\Type\Date;
use Bitrix\Main\UserTable;
use Ooofix\Xmlupd\Address\RequisiteAddressResolver;
use Ooofix\Xmlupd\Crm\ProductPriceNormalizer;

/**
 * Сбор всех данных для генерации УПД из CRM.
 * entityType: deal | smart_invoice
 */
class DataCollector
{
    public const TYPE_DEAL          = 'deal';
    public const TYPE_SMART_INVOICE = 'smart_invoice';

    public function __construct(
        private readonly DadataClient $dadata = new DadataClient(),
    ) {
    }

    /** @return array<string, mixed> */
    public function collect(string $entityType, int $entityId): array
    {
        Loader::includeModule('crm');

        $entity = $this->fetchEntity($entityType, $entityId);
        $companyId = (int)($entity['COMPANY_ID'] ?? 0);

        $buyer = $companyId > 0 ? $this->fetchBuyer($companyId) : [];
        if (!empty($buyer['REQUISITE_ID'])) {
            $buyer = $this->dadata->enrich($buyer);
        }

        $products = $this->fetchProducts($entityType, $entityId, (int)($entity['ENTITY_TYPE_ID'] ?? 0));

        return [
            'entity'    => $entity,
            'buyer'     => $buyer,
            'seller'    => $this->fetchSeller(),
            'products'  => $products,
            'signatory' => $this->fetchSignatory(),
            'user_fields' => $entity['USER_FIELDS'] ?? [],
        ];
    }

    /** @return array<string, mixed> */
    protected function fetchEntity(string $entityType, int $entityId): array
    {
        if ($entityType === self::TYPE_DEAL) {
            return $this->fetchDeal($entityId);
        }

        if ($entityType === self::TYPE_SMART_INVOICE) {
            return $this->fetchSmartInvoice($entityId);
        }

        throw new \InvalidArgumentException('Неизвестный тип сущности: ' . $entityType);
    }

    /** @return array<string, mixed> */
    protected function fetchDeal(int $dealId): array
    {
        $row = \CCrmDeal::GetByID($dealId, false);
        if (!$row) {
            throw new \RuntimeException('Сделка не найдена: ' . $dealId);
        }

        return $this->buildDealEntityFromRow($row, $dealId);
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    protected function buildDealEntityFromRow(array $row, int $dealId): array
    {
        $docDate = date('d.m.Y'); // по согласованию: дата генерации (позже — отдельное UF)
        $opportunity = (float)($row['OPPORTUNITY'] ?? 0);
        $taxValue = round((float)($row['TAX_VALUE'] ?? 0), 2);

        return [
            'ID'              => $dealId,
            'ENTITY_TYPE_ID'  => \CCrmOwnerType::Deal,
            'ENTITY_TYPE'     => self::TYPE_DEAL,
            'CATEGORY_ID'     => (int)($row['CATEGORY_ID'] ?? 0),
            'COMPANY_ID'      => (int)($row['COMPANY_ID'] ?? 0),
            'UF_UPD_NUMBER'   => (string)($row['UF_UPD_NUMBER'] ?? ''),
            'DOC_DATE'        => $docDate,
            'OPPORTUNITY'     => $opportunity,
            'TAX_VALUE'       => $taxValue,
            'TOTAL_GROSS'     => round($opportunity, 2),
            'TOTAL_NET'       => round($opportunity - $taxValue, 2),
            'TOTAL_TAX'       => $taxValue,
            'USER_FIELDS'     => $this->extractUserFields($row, ['UF_UPD_NUMBER', 'UF_UPD_FILE']),
        ];
    }

    /** @return array<string, mixed> */
    protected function fetchSmartInvoice(int $itemId): array
    {
        $typeId = Config::smartInvoiceTypeId();
        if ($typeId <= 0) {
            throw new \RuntimeException('Не задан entityTypeId СП «Счета» в настройках модуля');
        }

        $factory = Container::getInstance()->getFactory($typeId);
        if ($factory === null) {
            throw new \RuntimeException('Смарт-процесс не найден: ' . $typeId);
        }

        $item = $factory->getItem($itemId);
        if ($item === null) {
            throw new \RuntimeException('Элемент СП не найден: ' . $itemId);
        }

        $data = $item->getData();
        $companyId = (int)($data['COMPANY_ID'] ?? 0);

        $docDate = date('d.m.Y');

        // Номер счёта из СП (TITLE или стандартные поля)
        $invoiceNumber = trim((string)($data['TITLE'] ?? $data['ACCOUNT_NUMBER'] ?? $data['UF_CRM_ACCOUNT_NUMBER'] ?? ''));

        return [
            'ID'              => $itemId,
            'ENTITY_TYPE_ID'  => $typeId,
            'ENTITY_TYPE'     => self::TYPE_SMART_INVOICE,
            'CATEGORY_ID'     => (int)($data['CATEGORY_ID'] ?? 0),
            'COMPANY_ID'      => $companyId,
            'UF_UPD_NUMBER'   => (string)($data['UF_UPD_NUMBER'] ?? ''),
            'INVOICE_NUMBER'  => $invoiceNumber,
            'DOC_DATE'        => $docDate,
            'USER_FIELDS'     => $data,
        ];
    }

    /** @return array<string, mixed> */
    protected function fetchBuyer(int $companyId): array
    {
        $company = \CCrmCompany::GetByID($companyId, false);
        if (!$company) {
            return [];
        }

        $requisite = $this->fetchRequisite(\CCrmOwnerType::Company, $companyId);
        if ($requisite === []) {
            return [
                'COMPANY_ID' => $companyId,
                'NAME'       => (string)($company['TITLE'] ?? ''),
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
            'NAME'       => $requisite['NAME'] ?: (string)($company['TITLE'] ?? ''),
        ]);
    }

    /** @return array<string, mixed> */
    private function fetchSeller(): array
    {
        $requisiteId = SellerRequisiteResolver::resolveRequisiteId();

        if ($requisiteId <= 0) {
            return [];
        }

        $row = RequisiteTable::getById($requisiteId)->fetch();
        if (!$row) {
            return [];
        }

        $seller = $this->normalizeRequisite($row);
        $address = RequisiteAddressResolver::fetchLegalAddress(
            \CCrmOwnerType::Requisite,
            $requisiteId
        );

        return array_merge($seller, $address, $this->fetchBankDetails($requisiteId));
    }

    /**
     * @return array<string, mixed>
     */
    protected function fetchRequisite(int $entityTypeId, int $entityId): array
    {
        $row = RequisiteTable::getList([
            'filter' => [
                '=ENTITY_TYPE_ID' => $entityTypeId,
                '=ENTITY_ID'      => $entityId,
            ],
            'order'  => ['SORT' => 'ASC', 'ID' => 'ASC'],
            'limit'  => 1,
        ])->fetch();

        if (!$row) {
            return [];
        }

        return $this->normalizeRequisite($row);
    }

    /** @param array<string, mixed> $row */
    private function normalizeRequisite(array $row): array
    {
        $inn = trim((string)($row['RQ_INN'] ?? ''));
        $digits = preg_replace('/\D/', '', $inn);
        $isIp = strlen((string)$digits) === 12 || !empty($row['RQ_OGRNIP']);

        $lastName   = trim((string)($row['RQ_LAST_NAME'] ?? ''));
        $firstName  = trim((string)($row['RQ_FIRST_NAME'] ?? ''));
        $middleName = trim((string)($row['RQ_SECOND_NAME'] ?? ''));
        $companyName = trim((string)($row['RQ_COMPANY_NAME'] ?: $row['RQ_NAME'] ?: ''));

        $fio = trim(implode(' ', array_filter([$lastName, $firstName, $middleName])));
        $displayName = $isIp
            ? ('ИП ' . ($fio !== '' ? $fio : $companyName))
            : $companyName;

        return [
            'REQUISITE_ID' => (int)$row['ID'],
            'NAME'         => $companyName !== '' ? $companyName : $fio,
            'DISPLAY_NAME' => $displayName,
            'INN'          => $inn,
            'KPP'          => trim((string)($row['RQ_KPP'] ?? '')),
            'OGRN'         => trim((string)($row['RQ_OGRN'] ?? $row['RQ_OGRNIP'] ?? '')),
            'IS_IP'        => $isIp,
            'LAST_NAME'    => $lastName,
            'FIRST_NAME'   => $firstName,
            'MIDDLE_NAME'  => $middleName,
        ];
    }

    /** @return array<string, mixed> */
    protected function fetchBankDetails(int $requisiteId): array
    {
        $row = BankDetailTable::getList([
            'filter' => ['=ENTITY_ID' => $requisiteId],
            'order'  => ['SORT' => 'ASC', 'ID' => 'ASC'],
            'limit'  => 1,
        ])->fetch();

        if (!$row) {
            return [];
        }

        return [
            'BANK_NAME' => trim((string)($row['RQ_BANK_NAME'] ?? '')),
            'BANK_BIK'  => trim((string)($row['RQ_BIK'] ?? '')),
            'BANK_RS'   => trim((string)($row['RQ_ACC_NUM'] ?? '')),
            'BANK_KS'   => trim((string)($row['RQ_COR_ACC_NUM'] ?? '')),
        ];
    }

    /** @return list<array<string, mixed>> */
    private function fetchProducts(string $entityType, int $entityId, int $entityTypeId): array
    {
        $ownerType = $entityType === self::TYPE_DEAL
            ? \CCrmOwnerTypeAbbr::Deal
            : $this->resolveOwnerTypeAbbr($entityTypeId);

        $rows = ProductRowTable::getList([
            'filter' => [
                '=OWNER_TYPE' => $ownerType,
                '=OWNER_ID'   => $entityId,
            ],
            'order' => ['ID' => 'ASC'],
        ]);

        $products = [];
        $line = 0;

        while ($row = $rows->fetch()) {
            $line++;
            $amounts = ProductPriceNormalizer::normalize($row);

            $products[] = [
                'LINE'         => $line,
                'NAME'         => (string)($row['PRODUCT_NAME'] ?? ''),
                'QUANTITY'     => (float)($row['QUANTITY'] ?? 0),
                'PRICE'        => $amounts['PRICE'],
                'SUM_NET'      => $amounts['SUM_NET'],
                'SUM_GROSS'    => $amounts['SUM_GROSS'],
                'TAX_RATE'     => $amounts['TAX_RATE'],
                'TAX_SUM'      => $amounts['TAX_SUM'],
                'MEASURE'      => (string)($row['MEASURE_NAME'] ?? 'шт'),
                'MEASURE_CODE' => $this->resolveOkeiCode((string)($row['MEASURE_NAME'] ?? ''), $row),
            ];
        }

        return $products;
    }

    /** @param array<string, mixed> $productRow */
    private function resolveOkeiCode(string $measure, array $productRow = []): string
    {
        if (!empty($productRow['MEASURE_CODE'])) {
            return (string)$productRow['MEASURE_CODE'];
        }

        $map = [
            'шт'  => '796',
            'шт.' => '796',
            'л'   => '112',
            'л.'  => '112',
            'усл' => '876',
            'ч'   => '356',
            'час' => '356',
            'н/ч' => '356',
        ];

        return $map[mb_strtolower(trim($measure))] ?? '796';
    }

    /** @return array<string, mixed> */
    private function fetchSignatory(): array
    {
        $mode = Config::signatoryMode();
        $userId = 0;

        if ($mode === 'current_user') {
            global $USER;
            if ($USER instanceof \CUser && $USER->IsAuthorized()) {
                $userId = (int)$USER->GetID();
            }
        } elseif ($mode === 'by_position') {
            $users = \Ooofix\Xmlupd\Admin\SettingsCrmData::usersByPosition(Config::signatoryPosition());
            if ($users !== []) {
                $userId = (int)$users[0]['id'];
            }
        } else {
            $userId = Config::signatoryUserId();
        }

        if ($userId <= 0) {
            return [
                'NAME'     => '',
                'POSITION' => Config::signatoryPosition(),
            ];
        }

        $user = UserTable::getById($userId)->fetch();
        if (!$user) {
            return [
                'NAME'     => '',
                'POSITION' => Config::signatoryPosition(),
            ];
        }

        $position = Config::signatoryPosition();
        $workPosition = trim((string)($user['WORK_POSITION'] ?? ''));
        if ($mode === 'by_position' && $workPosition !== '') {
            $position = $workPosition;
        }

        return [
            'NAME'         => trim(implode(' ', array_filter([
                $user['LAST_NAME'] ?? '',
                $user['NAME'] ?? '',
                $user['SECOND_NAME'] ?? '',
            ]))),
            'LAST_NAME'    => (string)($user['LAST_NAME'] ?? ''),
            'FIRST_NAME'   => (string)($user['NAME'] ?? ''),
            'MIDDLE_NAME'  => (string)($user['SECOND_NAME'] ?? ''),
            'POSITION'     => $position,
            'USER_ID'      => $userId,
        ];
    }

    /** @param array<string, mixed> $row */
    protected function extractUserFields(array $row, array $keys): array
    {
        $result = [];
        foreach ($keys as $key) {
            if (array_key_exists($key, $row)) {
                $result[$key] = $row[$key];
            }
        }

        return $result;
    }

    private function formatDate(mixed $value): string
    {
        if ($value instanceof Date) {
            return $value->format('d.m.Y');
        }

        if (is_string($value) && $value !== '') {
            $ts = strtotime($value);
            return $ts ? date('d.m.Y', $ts) : date('d.m.Y');
        }

        return date('d.m.Y');
    }

    /** Аббревиатура владельца товарной позиции для смарт-процесса */
    private function resolveOwnerTypeAbbr(int $entityTypeId): string
    {
        if (method_exists(\CCrmOwnerTypeAbbr::class, 'ResolveByTypeID')) {
            return (string)\CCrmOwnerTypeAbbr::ResolveByTypeID($entityTypeId);
        }

        if (defined('\CCrmOwnerTypeAbbr::DynamicTypePrefix')) {
            return \CCrmOwnerTypeAbbr::DynamicTypePrefix . $entityTypeId;
        }

        return 'T' . $entityTypeId;
    }
}
