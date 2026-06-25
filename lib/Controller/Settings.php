<?php

namespace Ooofix\Xmlupd\Controller;

use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Engine\ActionFilter;
use Bitrix\Main\Loader;
use Ooofix\Xmlupd\Admin\ModuleAccess;
use Ooofix\Xmlupd\Admin\SettingsCrmLabels;
use Ooofix\Xmlupd\Admin\SettingsService;

/** AJAX API настроек: ooofix.xmlupd:settings.* */
class Settings extends Controller
{
    public function configureActions(): array
    {
        $auth = [
            new ActionFilter\Authentication(),
            new ActionFilter\Csrf(),
        ];

        return [
            'getAll' => ['prefilters' => $auth],
            'save' => ['prefilters' => $auth],
            'installUserFields' => ['prefilters' => $auth],
            'detectSmartInvoice' => ['prefilters' => $auth],
        ];
    }

    public function getAllAction(): array
    {
        if (!$this->ensureAccess(false)) {
            return ['success' => false, 'message' => 'Доступ запрещён'];
        }

        return [
            'success' => true,
            'values'  => SettingsService::getAll(),
            'canWrite'=> ModuleAccess::canWrite(),
        ];
    }

    /** @param array<string, mixed> $values */
    public function saveAction(array $values): array
    {
        if (!$this->ensureAccess(true)) {
            return ['success' => false, 'message' => 'Недостаточно прав для изменения настроек'];
        }

        $result = SettingsService::save($values);
        if (!$result['success']) {
            return [
                'success' => false,
                'message' => 'Проверьте значения полей',
                'errors'  => $result['errors'] ?? [],
            ];
        }

        return [
            'success' => true,
            'message' => 'Настройки сохранены',
            'values'  => $result['values'] ?? SettingsService::getAll(),
            'labels'  => $result['labels'] ?? SettingsCrmLabels::displayMap(SettingsService::getAll()),
        ];
    }

    public function installUserFieldsAction(): array
    {
        if (!$this->ensureAccess(true)) {
            return ['success' => false, 'message' => 'Недостаточно прав'];
        }

        return SettingsService::installUserFieldsAction();
    }

    public function detectSmartInvoiceAction(): array
    {
        if (!$this->ensureAccess(true)) {
            return ['success' => false, 'message' => 'Недостаточно прав'];
        }

        return SettingsService::detectSmartInvoiceAction();
    }

    private function ensureAccess(bool $write): bool
    {
        if (!Loader::includeModule('ooofix.xmlupd')) {
            return false;
        }

        ModuleAccess::ensureModuleLoaded();

        return $write ? ModuleAccess::canWrite() : ModuleAccess::canRead();
    }
}
