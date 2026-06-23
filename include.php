<?php

use Bitrix\Main\Loader;

Loader::registerNamespace('Vendor\\Xmldoc', __DIR__ . '/lib');
Loader::registerNamespace('Vendor\\Xmldoc\\Cloud', __DIR__ . '/lib/Cloud');

Loader::registerAutoLoadClasses('vendor.xml', [
    'Vendor\\Xmldoc\\Controller\\Upd' => 'lib/Controller/upd.php',
]);
