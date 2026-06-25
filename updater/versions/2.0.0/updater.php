<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

$moduleId = 'ooofix.xmlupd';

if (!class_exists(\Bitrix\Main\ModuleManager::class) || !\Bitrix\Main\ModuleManager::isModuleInstalled($moduleId)) {
    return;
}

$module = CModule::CreateModuleObject($moduleId);
if ($module === null) {
    return;
}

if (method_exists($module, 'DoUpdate')) {
    $module->DoUpdate();
}
