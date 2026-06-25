<?php

use Bitrix\Main\Loader;

Loader::registerNamespace('Ooofix\\Xmlupd', __DIR__ . '/lib');
Loader::registerNamespace('Ooofix\\Xmlupd\\Cloud', __DIR__ . '/lib/Cloud');

Loader::registerAutoLoadClasses('ooofix.xmlupd', [
    'Ooofix\\Xmlupd\\Controller\\Upd' => 'lib/Controller/upd.php',
    'Ooofix\\Xmlupd\\Controller\\Settings' => 'lib/Controller/Settings.php',
]);
