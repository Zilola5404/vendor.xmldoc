<?php

/** Значения настроек модуля по умолчанию */
$ooofix_xmlupd_default_option = [
    'dadata_api_key'        => '',
    'seller_requisite_id'   => '',
    'signatory_mode'        => 'settings', // settings | by_position | current_user
    'signatory_user_id'     => '',
    'signatory_position'    => 'Сотрудник',
    'smart_invoice_type_id' => '31',
    'publish_timeline'      => 'Y',
    'xsd_path'              => '',
    'upd_function'          => 'СЧФДОП',
    'file_encoding'         => 'windows-1251',
    'crm_adapter'           => 'auto', // auto | onprem | cloud
    'cloud_rest_webhook'    => '',
    'xml_format_version'    => '5.03', // 5.02 | 5.03
    'xsd_schema_revision'   => 'auto', // auto | 03_04 | 03_05 …
    'calculation_mode'      => '1C', // 1C | BITRIX24
    'address_source'        => 'requisite', // requisite | dadata | text
];
