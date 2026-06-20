<?php

namespace Vendor\Xmldoc\Documents\Upd;

use Vendor\Xmldoc\ValidationMessages;

/** Проверка обязательных полей и арифметики сумм перед генерацией XML */
class UpdValidator
{
    private const MONEY_EPS = 0.02;

    public function __construct(
        private readonly UpdMapper $mapper = new UpdMapper(),
    ) {
    }

    /**
     * @param array<string, mixed> $mapped
     * @return string[] сообщения для пользователя
     */
    public function validate(array $mapped): array
    {
        $errors = [];
        $map = $this->mapper->getMap();

        foreach ($map as $key => $rule) {
            if (empty($rule['required'])) {
                continue;
            }

            $value = $mapped[$key] ?? null;

            if ($key === 'products') {
                if (!is_array($value) || $value === []) {
                    $errors[] = ValidationMessages::fromMapKey('products');
                }
                continue;
            }

            if ($value === null || $value === '' || $value === []) {
                $label = (string)($rule['label'] ?? $key);
                $errors[] = ValidationMessages::fromLabel($label);
            }
        }

        $inn = preg_replace('/\D/', '', (string)($mapped['buyer_inn'] ?? ''));
        if (strlen($inn) === 10 && empty($mapped['buyer_kpp'])) {
            $errors[] = ValidationMessages::fromMapKey('buyer_kpp');
        }

        if (is_array($mapped['products'] ?? null) && ($mapped['products'] ?? []) !== []) {
            $errors = array_merge($errors, $this->validateProductSums($mapped));
        }

        return array_values(array_unique($errors));
    }

    /**
     * @param array<string, mixed> $mapped
     * @return string[]
     */
    private function validateProductSums(array $mapped): array
    {
        $errors = [];
        $products = $mapped['products'] ?? [];
        $sumNet = 0.0;
        $sumTax = 0.0;
        $sumGross = 0.0;
        $hasSumError = false;

        foreach ($products as $row) {
            $line = (int)($row['LINE'] ?? 0);
            $productName = trim((string)($row['NAME'] ?? ''));

            $qty = (float)($row['QUANTITY'] ?? 0);
            $price = (float)($row['PRICE'] ?? 0);
            $taxRate = (float)($row['TAX_RATE'] ?? 0);

            if ($qty <= 0) {
                $errors[] = ValidationMessages::productQuantity($line, $productName);
            }
            if ($price < 0) {
                $errors[] = ValidationMessages::productPrice($line, $productName);
            }

            $expectedNet = round($qty * $price, 2);
            $actualNet = round((float)($row['SUM_NET'] ?? 0), 2);
            $expectedTax = $taxRate > 0 ? round($expectedNet * $taxRate / 100, 2) : 0.0;
            $actualTax = round((float)($row['TAX_SUM'] ?? 0), 2);
            $expectedGross = round($expectedNet + $expectedTax, 2);
            $actualGross = round((float)($row['SUM_GROSS'] ?? 0), 2);

            if (
                !$this->moneyEquals($expectedNet, $actualNet)
                || !$this->moneyEquals($expectedTax, $actualTax)
                || !$this->moneyEquals($expectedGross, $actualGross)
            ) {
                $hasSumError = true;
                $errors[] = ValidationMessages::productSumMismatch($line, $productName);
            }

            $sumNet += $actualNet;
            $sumTax += $actualTax;
            $sumGross += $actualGross;
        }

        $totals = $mapped['totals'] ?? [];
        $totalNet = round((float)($totals['SUM_NET'] ?? 0), 2);
        $totalTax = round((float)($totals['TAX_SUM'] ?? 0), 2);
        $totalGross = round((float)($totals['SUM_GROSS'] ?? 0), 2);

        $sumNet = round($sumNet, 2);
        $sumTax = round($sumTax, 2);
        $sumGross = round($sumGross, 2);

        if (
            !$hasSumError
            && (
                !$this->moneyEquals($sumNet, $totalNet)
                || !$this->moneyEquals($sumTax, $totalTax)
                || !$this->moneyEquals($sumGross, $totalGross)
            )
        ) {
            $errors[] = ValidationMessages::productTotalsMismatch();
        }

        return $errors;
    }

    private function moneyEquals(float $a, float $b): bool
    {
        return abs($a - $b) <= self::MONEY_EPS;
    }
}
