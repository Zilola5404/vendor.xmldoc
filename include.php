<?php

use Bitrix\Main\Loader;

Loader::registerNamespace('Vendor\\Xmldoc', __DIR__ . '/lib');

Loader::registerAutoLoadClasses('vendor.xmldoc', [
    'Vendor\\Xmldoc\\Controller\\Upd' => 'lib/Controller/upd.php',
]);
