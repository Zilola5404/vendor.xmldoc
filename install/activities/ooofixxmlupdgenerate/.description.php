<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Ooofix\Xmlupd\DocumentTypeRegistry;

Loader::includeModule('ooofix.xmlupd');

Loc::loadMessages(__FILE__);

$arActivityDescription = [
    'NAME'        => Loc::getMessage('OOOFIX_XMLUPD_BP_NAME'),
    'DESCRIPTION' => Loc::getMessage('OOOFIX_XMLUPD_BP_DESC'),
    // XMLDOC-20: robot_activity для роботов/триггеров CRM + activity для дизайнера БП
    'TYPE'        => ['activity', 'robot_activity'],
    'CLASS'       => 'OoofixXmlupdGenerate',
    'JSCLASS'     => 'BizProcActivity',
    'CATEGORY'    => [
        'ID'       => 'other',
        'OWN_ID'   => 'ooofix.xmlupd',
        'OWN_NAME' => Loc::getMessage('OOOFIX_XMLUPD_BP_CATEGORY'),
    ],
    // XMLDOC-22: группа «Другие роботы»
    'ROBOT_SETTINGS' => [
        'GROUP' => DocumentTypeRegistry::robotGroup(),
        'SORT'  => 500,
    ],
    'FILTER'      => [
        'INCLUDE' => DocumentTypeRegistry::buildBpFilterInclude(),
    ],
    'RETURN'      => [
        'Success'  => [
            'NAME' => Loc::getMessage('OOOFIX_XMLUPD_BP_RET_SUCCESS'),
            'TYPE' => 'bool',
        ],
        'FileId'   => [
            'NAME' => Loc::getMessage('OOOFIX_XMLUPD_BP_RET_FILE_ID'),
            'TYPE' => 'int',
        ],
        'FileName' => [
            'NAME' => Loc::getMessage('OOOFIX_XMLUPD_BP_RET_FILE_NAME'),
            'TYPE' => 'string',
        ],
        'Version'  => [
            'NAME' => Loc::getMessage('OOOFIX_XMLUPD_BP_RET_VERSION'),
            'TYPE' => 'int',
        ],
        'Message'  => [
            'NAME' => Loc::getMessage('OOOFIX_XMLUPD_BP_RET_MESSAGE'),
            'TYPE' => 'string',
        ],
        'Errors'   => [
            'NAME' => Loc::getMessage('OOOFIX_XMLUPD_BP_RET_ERRORS'),
            'TYPE' => 'string',
        ],
    ],
];
