<?php

define('NO_KEEP_STATISTIC', true);
define('STOP_STATISTICS', true);
define('NO_AGENT_STATISTIC', true);
define('DisableEventsCheck', true);
define('PUBLIC_AJAX_MODE', true);

require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

$moduleId = 'ooofix.xmlupd';
$moduleInclude = getLocalPath('modules/' . $moduleId . '/include.php');
if (is_string($moduleInclude) && is_file($_SERVER['DOCUMENT_ROOT'] . $moduleInclude)) {
    require_once $_SERVER['DOCUMENT_ROOT'] . $moduleInclude;
}

use Bitrix\Main\Loader;
use Bitrix\Main\Web\Json;
use Ooofix\Xmlupd\Admin\ModuleAccess;
use Ooofix\Xmlupd\Admin\SettingsCrmData;

/**
 * @param array<string, mixed> $payload
 */
function ox_settings_api_json(array $payload): never
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

if (!check_bitrix_sessid()) {
    ox_settings_api_json(['success' => false, 'message' => 'Сессия истекла. Обновите страницу.']);
}

if (!Loader::includeModule('ooofix.xmlupd') || !ModuleAccess::ensureModuleLoaded() || !ModuleAccess::canRead()) {
    ox_settings_api_json(['success' => false, 'message' => 'Доступ запрещён']);
}

Loader::includeModule('crm');

$action = (string)($_REQUEST['action'] ?? '');

switch ($action) {
    case 'requisites':
        ox_settings_api_json([
            'success'    => true,
            'requisites' => SettingsCrmData::myCompanyRequisites(),
        ]);
        break;

    case 'requisites_by_inn':
        $inn = SettingsCrmData::normalizeInn((string)($_REQUEST['inn'] ?? ''));
        ox_settings_api_json([
            'success'    => true,
            'requisites' => SettingsCrmData::requisitesByInn($inn, true),
            'inn'        => $inn,
        ]);
        break;

    case 'users_by_position':
        $position = trim((string)($_REQUEST['position'] ?? ''));
        ox_settings_api_json([
            'success' => true,
            'users'   => SettingsCrmData::usersByPosition($position),
        ]);
        break;

    default:
        ox_settings_api_json(['success' => false, 'message' => 'Неизвестное действие']);
}
