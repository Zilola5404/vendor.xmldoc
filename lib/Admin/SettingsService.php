<?php

namespace Ooofix\Xmlupd\Admin;

use Bitrix\Main\Config\Option;
use Ooofix\Xmlupd\DocumentTypeRegistry;
use Ooofix\Xmlupd\ModuleInfo;
use Ooofix\Xmlupd\Portal\PortalRoutes;

/** Единый сервис настроек модуля для админки и портала. */
final class SettingsService
{
    public const MODULE_ID = ModuleInfo::MODULE_ID;

    public static function get(string $code): string
    {
        $field = SettingsFieldRegistry::fields()[$code] ?? null;
        $default = $field !== null ? (string)($field['default'] ?? '') : '';

        return (string)Option::get(self::MODULE_ID, $code, $default);
    }

    /** @return array<string, string> */
    public static function getAll(): array
    {
        $values = [];
        foreach (SettingsFieldRegistry::codes() as $code) {
            $values[$code] = self::get($code);
        }

        return $values;
    }

    /**
     * @param array<string, mixed> $data
     * @return array{success: bool, errors?: array<string, string>, values?: array<string, string>}
     */
    public static function save(array $data): array
    {
        $validation = self::validate($data);
        if (!$validation['success']) {
            return $validation;
        }

        foreach (SettingsFieldRegistry::codes() as $code) {
            $field = SettingsFieldRegistry::fields()[$code];
            if (($field['type'] ?? '') === 'checkbox') {
                $value = !empty($data[$code]) && $data[$code] !== 'N' ? 'Y' : 'N';
            } else {
                $value = (string)($data[$code] ?? '');
            }
            Option::set(self::MODULE_ID, $code, $value);
        }

        self::installUserFields();

        if (class_exists(DocumentTypeRegistry::class)) {
            DocumentTypeRegistry::resetCache();
        }

        return [
            'success' => true,
            'values'  => self::getAll(),
            'labels'  => SettingsCrmLabels::displayMap(self::getAll()),
        ];
    }

    /**
     * @param array<string, mixed> $data
     * @return array{success: bool, errors: array<string, string>}
     */
    public static function validate(array $data): array
    {
        $errors = [];

        foreach (SettingsFieldRegistry::fields() as $code => $field) {
            $raw = $data[$code] ?? null;
            $type = (string)($field['type'] ?? 'string');
            $rules = (array)($field['validate'] ?? []);

            if ($type === 'checkbox') {
                continue;
            }

            $value = is_scalar($raw) || $raw === null ? (string)$raw : '';

            if (!empty($rules['integer'])) {
                if ($value === '') {
                    continue;
                }
                if (!ctype_digit($value) && !preg_match('/^-?\d+$/', $value)) {
                    $errors[$code] = 'Укажите целое число';
                    continue;
                }
                if ($value !== '' && isset($rules['min']) && (int)$value < (int)$rules['min']) {
                    $errors[$code] = 'Значение не может быть меньше ' . (int)$rules['min'];
                }
            }

            if (!empty($rules['enum']) && $value !== '' && !in_array($value, $rules['enum'], true)) {
                $errors[$code] = 'Недопустимое значение';
            }

            if (!empty($rules['maxLength']) && mb_strlen($value) > (int)$rules['maxLength']) {
                $errors[$code] = 'Слишком длинное значение';
            }
        }

        return [
            'success' => $errors === [],
            'errors'  => $errors,
        ];
    }

    /** @return array{success: bool, message: string, sp_id?: int} */
    public static function installUserFieldsAction(): array
    {
        self::installUserFields();

        return [
            'success' => true,
            'message' => 'Поля UF_UPD_NUMBER и UF_UPD_FILE проверены/созданы',
        ];
    }

    /** @return array{success: bool, message: string, sp_id?: int} */
    public static function detectSmartInvoiceAction(): array
    {
        if (!class_exists(\Ooofix\Xmlupd\Cloud\Crm\SmartInvoiceTypeResolver::class)) {
            return [
                'success' => false,
                'message' => 'Автоопределение СП недоступно в этом окружении',
            ];
        }

        $detected = \Ooofix\Xmlupd\Cloud\Crm\SmartInvoiceTypeResolver::detectFromCrm();
        if ($detected > 0) {
            Option::set(self::MODULE_ID, 'smart_invoice_type_id', (string)$detected);
            self::installUserFields();
            if (class_exists(DocumentTypeRegistry::class)) {
                DocumentTypeRegistry::resetCache();
            }

            return [
                'success' => true,
                'message' => 'СП «Счета» определён: entityTypeId = ' . $detected,
                'sp_id'   => $detected,
            ];
        }

        return [
            'success' => false,
            'message' => 'Не удалось автоматически определить СП «Счета»',
        ];
    }

    public static function installUserFields(): void
    {
        $module = \CModule::CreateModuleObject(self::MODULE_ID);
        if ($module !== null && method_exists($module, 'InstallUserFields')) {
            $module->InstallUserFields();
        }
    }

    /** @deprecated Используйте save() через AJAX */
    public static function handlePost(): ?array
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !check_bitrix_sessid()) {
            return null;
        }

        if (isset($_POST['save'])) {
            $result = self::save($_POST);
            if ($result['success']) {
                return ['action' => 'saved'];
            }
        }

        return null;
    }

    /** @param array{action: string, sp_id?: int} $result */
    public static function redirectAfterAction(array $result): void
    {
        global $APPLICATION;

        $base = (string)($APPLICATION->GetCurPageParam('', ['saved', 'uf', 'sp_detected', 'sp_not_found']));
        $query = match ($result['action']) {
            'saved'        => 'saved=Y',
            'uf'           => 'uf=Y',
            'sp_detected'  => 'sp_detected=' . (int)($result['sp_id'] ?? 0),
            'sp_not_found' => 'sp_not_found=Y',
            default        => '',
        };

        if ($query === '') {
            return;
        }

        $separator = str_contains($base, '?') ? '&' : '?';
        LocalRedirect($base . $separator . $query);
    }

    public static function settingsAdminUrl(): string
    {
        return '/bitrix/admin/settings.php?mid=' . urlencode(self::MODULE_ID) . '&lang=' . LANGUAGE_ID;
    }

    public static function settingsPortalUrl(): string
    {
        return PortalRoutes::settings();
    }
}
