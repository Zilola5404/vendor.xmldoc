<?php

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

$moduleRel = 'modules/ooofix.xmlupd/install/public/xml_documents';
$moduleBase = getLocalPath($moduleRel);
if (!is_string($moduleBase) || $moduleBase === '') {
    $moduleBase = '/local/' . $moduleRel;
}

$bootstrapPath = $_SERVER['DOCUMENT_ROOT'] . $moduleBase . '/bootstrap.php';
if (!is_file($bootstrapPath)) {
    require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php';
    ShowError('Модуль ooofix.xmlupd не найден. Установите модуль в админке.');
    require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php';
    return;
}

require $bootstrapPath;

$target = $_SERVER['DOCUMENT_ROOT'] . $moduleBase . '/settings/index.php';
if (!is_file($target)) {
    require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php';
    ShowError('Обновите модуль ooofix.xmlupd до последней версии.');
    require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php';
    return;
}

require $target;
