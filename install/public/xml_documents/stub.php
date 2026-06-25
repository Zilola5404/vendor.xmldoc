<?php

/** Прокси-страница раздела /xml_documents/{section}/ */
$moduleBase = getLocalPath('modules/ooofix.xmlupd/install/public/xml_documents');
$bootstrap = is_string($moduleBase) ? $moduleBase . '/bootstrap.php' : '';
$bootstrapPath = $bootstrap !== '' ? $_SERVER['DOCUMENT_ROOT'] . $bootstrap : '';

if ($bootstrapPath === '' || !is_file($bootstrapPath)) {
    require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';
    require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php';
    ShowError('Страница модуля не найдена. Обновите модуль ooofix.xmlupd.');
    require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php';
    return;
}

require $bootstrapPath;

$section = basename(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')));
$modulePage = is_string($moduleBase) ? $moduleBase . '/' . $section . '/index.php' : '';
$fullPath = $modulePage !== '' ? $_SERVER['DOCUMENT_ROOT'] . $modulePage : '';

if ($fullPath === '' || !is_file($fullPath)) {
    require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php';
    ShowError('Страница модуля не найдена. Обновите модуль ooofix.xmlupd.');
    require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php';
    return;
}

require $fullPath;
