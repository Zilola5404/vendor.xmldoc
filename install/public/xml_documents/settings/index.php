<?php

require dirname(__DIR__) . '/bootstrap.php';

use Bitrix\Main\Loader;
use Ooofix\Xmlupd\Admin\ModuleAccess;
use Ooofix\Xmlupd\Admin\SettingsPageController;
use Ooofix\Xmlupd\Admin\SettingsPageRenderer;
use Ooofix\Xmlupd\ModuleInfo;
use Ooofix\Xmlupd\Portal\PortalPageController;

if (SettingsPageRenderer::isSidePanelRequest()) {
    if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
        require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';
    }

    if (!Loader::includeModule('crm') || !ModuleAccess::ensureModuleLoaded() || !ModuleAccess::canRead()) {
        require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php';
        ShowError('Недостаточно прав для просмотра настроек.');
        require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php';
        return;
    }

    global $APPLICATION;
    require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php';
    $APPLICATION->SetTitle(' ');

    SettingsPageController::renderPage([
        'layout'      => 'sidepanel',
        'isSidePanel' => true,
    ]);

    require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php';
    return;
}

PortalPageController::boot('settings', 'Настройки', static function (): void {
    SettingsPageController::renderPage([
        'layout' => 'portal',
    ]);
});
