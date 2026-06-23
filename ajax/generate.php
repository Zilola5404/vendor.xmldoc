<?php

define('NO_KEEP_STATISTIC', true);
define('STOP_STATISTICS', true);
define('NO_AGENT_STATISTIC', true);
define('DisableEventsCheck', true);
define('PUBLIC_AJAX_MODE', true);
define('NOT_CHECK_PERMISSIONS', false);

require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

use Bitrix\Main\Loader;
use Bitrix\Main\Web\Json;
use Vendor\Xmldoc\Http\GenerateEndpoint;

/**
 * @param array<string, mixed> $payload
 */
function xmldoc_send_json(array $payload): never
{
    if (function_exists('ob_get_level')) {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
    }

    header('Content-Type: application/json; charset=utf-8');
    echo Json::encode($payload, JSON_UNESCAPED_UNICODE);
    die();
}

try {
    Loader::includeModule('vendor.xml');
    xmldoc_send_json(GenerateEndpoint::execute());
} catch (\Throwable $e) {
    xmldoc_send_json([
        'success' => false,
        'message' => $e->getMessage(),
        'errors'  => [$e->getMessage()],
    ]);
}
