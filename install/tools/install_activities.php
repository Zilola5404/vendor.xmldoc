<?php
/**
 * Ручная установка activity «Сформировать УПД (XML)» для БП/роботов CRM.
 * Запуск один раз из браузера (под администратором):
 * /local/modules/vendor.xml/install/tools/install_activities.php
 *
 * После успеха удалите этот файл или ограничьте доступ.
 */

define('NO_KEEP_STATISTIC', true);
define('STOP_STATISTICS', true);
define('NOT_CHECK_PERMISSIONS', false);

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

global $USER, $APPLICATION;

if (!$USER->IsAdmin()) {
    $APPLICATION->AuthForm('Только для администратора');
}

$moduleId = 'vendor.xml';
$source = $_SERVER['DOCUMENT_ROOT'] . '/local/modules/vendor.xml/install/activities/xmldocgenerateupd';

header('Content-Type: text/html; charset=utf-8');

if (!is_dir($source)) {
    echo '<p style="color:red">Не найден исходник: ' . htmlspecialchars($source) . '</p>';
    echo '<p>Сначала скопируйте модуль vendor.xml на сервер.</p>';
    require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_after.php';
    return;
}

$targets = [
    $_SERVER['DOCUMENT_ROOT'] . '/local/activities/xmldocgenerateupd',
    $_SERVER['DOCUMENT_ROOT'] . '/bitrix/activities/xmldocgenerateupd',
];

echo '<h1>Установка activity xmldocgenerateupd</h1><ul>';

foreach ($targets as $target) {
    $parent = dirname($target);
    if (!is_dir($parent)) {
        @mkdir($parent, BX_DIR_PERMISSIONS, true);
    }

    if (!is_dir($parent)) {
        echo '<li style="color:orange">Пропуск (нет прав на каталог): ' . htmlspecialchars($parent) . '</li>';
        continue;
    }

    CopyDirFiles($source, $target, true, true);

    if (is_file($target . '/xmldocgenerateupd.php')) {
        echo '<li style="color:green">OK: ' . htmlspecialchars($target) . '</li>';
    } else {
        echo '<li style="color:red">Ошибка копирования в: ' . htmlspecialchars($target) . '</li>';
    }
}

echo '</ul>';
echo '<p>Очистите кеш Bitrix. В роботах CRM ищите действие <b>«Сформировать УПД (XML)»</b>.</p>';
echo '<p>Обновите модуль до 1.5.0 через partner_modules.php — activity и триггеры будут установлены автоматически.</p>';

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_after.php';
