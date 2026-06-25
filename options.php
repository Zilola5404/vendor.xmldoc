<?php

use Bitrix\Main\Loader;
use Ooofix\Xmlupd\Admin\ModuleAccess;
use Ooofix\Xmlupd\Admin\SettingsPageController;

$module_id = 'ooofix.xmlupd';

Loader::includeModule('main');

global $APPLICATION;

if ($APPLICATION->GetGroupRight($module_id) < 'R') {
    $APPLICATION->AuthForm('Доступ запрещён');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $APPLICATION->GetGroupRight($module_id) < 'W') {
    $APPLICATION->AuthForm('Доступ запрещён');
}

ModuleAccess::ensureModuleLoaded();

SettingsPageController::renderPage([
    'layout'  => 'admin',
]);
