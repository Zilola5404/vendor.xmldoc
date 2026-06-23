<?php

namespace Vendor\Xmldoc\Crm;

/**
 * Нормализация цен товарной строки CRM → суммы для УПД.
 *
 * Как в карточке сделки B24:
 * - PRICE — цена за единицу с НДС
 * - СтТовУчНал = QUANTITY × PRICE (скидка сделки уже в итоге OPPORTUNITY, не в строке)
 * - СтТовБезНДС = round(СтТовУчНал / (1 + НДС%), 2)
 * - ЦенаТов = round(СтТовБезНДС / QUANTITY, 2)
 */
final class ProductPriceNormalizer
{
    /**
     * @param array<string, mixed> $row строка ProductRowTable
     * @return array{PRICE: float, SUM_NET: float, TAX_SUM: float, SUM_GROSS: float, TAX_RATE: float}
     */
    public static function normalize(array $row): array
    {
        $qty = (float)($row['QUANTITY'] ?? 0);
        $taxRate = (float)($row['TAX_RATE'] ?? 0);
        $unitGross = self::resolveUnitGrossPrice($row, $taxRate);

        if ($unitGross !== null && $qty > 0 && $taxRate > 0) {
            $sumGross = round($qty * $unitGross, 2);
            $sumNet = round($sumGross / (1 + $taxRate / 100), 2);
            $taxSum = round($sumGross - $sumNet, 2);
            $unitNet = round($sumNet / $qty, 2);

            return [
                'PRICE'     => $unitNet,
                'SUM_NET'   => $sumNet,
                'TAX_SUM'   => $taxSum,
                'SUM_GROSS' => $sumGross,
                'TAX_RATE'  => $taxRate,
            ];
        }

        $unitNet = self::resolveUnitNetPrice($row, $taxRate);
        $sumNet = round($qty * $unitNet, 2);
        $taxSum = $taxRate > 0 ? round($sumNet * $taxRate / 100, 2) : 0.0;
        $sumGross = round($sumNet + $taxSum, 2);

        return [
            'PRICE'     => round($unitNet, 2),
            'SUM_NET'   => $sumNet,
            'TAX_SUM'   => $taxSum,
            'SUM_GROSS' => $sumGross,
            'TAX_RATE'  => $taxRate,
        ];
    }

    /**
     * Цена за единицу без НДС — для атрибута ЦенаТов.
     *
     * @param array<string, mixed> $row
     */
    public static function resolveUnitNetPrice(array $row, float $taxRate): float
    {
        $exclusive = (float)($row['PRICE_EXCLUSIVE'] ?? 0);
        if ($exclusive > 0) {
            return $exclusive;
        }

        $price = (float)($row['PRICE'] ?? 0);
        if ($price <= 0) {
            return 0.0;
        }

        if (self::isTaxIncluded($row, $taxRate) && $taxRate > 0) {
            return round($price / (1 + $taxRate / 100), 2);
        }

        $netto = (float)($row['PRICE_NETTO'] ?? 0);
        if ($netto > 0) {
            return $netto;
        }

        return $price;
    }

    /**
     * @param array<string, mixed> $row
     */
    private static function resolveUnitGrossPrice(array $row, float $taxRate): ?float
    {
        if (!self::isTaxIncluded($row, $taxRate)) {
            return null;
        }

        $price = (float)($row['PRICE'] ?? 0);
        if ($price > 0) {
            return $price;
        }

        $brutto = (float)($row['PRICE_BRUTTO'] ?? 0);

        return $brutto > 0 ? $brutto : null;
    }

    /**
     * @param array<string, mixed> $row
     */
    private static function isTaxIncluded(array $row, float $taxRate): bool
    {
        $flag = strtoupper(trim((string)($row['TAX_INCLUDED'] ?? '')));
        if ($flag === 'Y' || $flag === '1') {
            return true;
        }
        if ($flag === 'N' || $flag === '0') {
            return false;
        }

        if ($taxRate <= 0) {
            return false;
        }

        $price = (float)($row['PRICE'] ?? 0);
        $exclusive = (float)($row['PRICE_EXCLUSIVE'] ?? 0);
        $brutto = (float)($row['PRICE_BRUTTO'] ?? 0);

        if ($brutto > 0) {
            return true;
        }

        if ($exclusive > 0 && $price > $exclusive) {
            return true;
        }

        return $price > 0;
    }
}
