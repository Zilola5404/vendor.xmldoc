<?php

use Bitrix\Main\Loader;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';
}

$moduleId = 'ooofix.xmlupd';

if (!Loader::includeModule($moduleId)) {
    $candidates = [
        getLocalPath('modules/' . $moduleId . '/include.php'),
        '/local/modules/' . $moduleId . '/include.php',
    ];

    foreach ($candidates as $relative) {
        if (!is_string($relative) || $relative === '') {
            continue;
        }

        $include = $_SERVER['DOCUMENT_ROOT'] . $relative;
        if (is_file($include)) {
            require_once $include;
            Loader::includeModule($moduleId);
            break;
        }
    }
}
