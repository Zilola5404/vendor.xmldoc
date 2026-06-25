<?php

namespace Ooofix\Xmlupd\Install;

/** Пункт «XML Документы» в .left.menu_ext.php портала. */
final class LeftMenuExtInstaller
{
    public const MARKER = 'OOOFIX_XMLUPD_MENU_EXT';
    public const INCLUDE_FILE = '/local/php_interface/include/ooofix_xmlupd_menu.php';

    public static function install(): void
    {
        self::writeIncludeFile();

        $menuExtPath = $_SERVER['DOCUMENT_ROOT'] . '/.left.menu_ext.php';
        if (!is_file($menuExtPath)) {
            return;
        }

        $content = (string)file_get_contents($menuExtPath);
        if (str_contains($content, self::MARKER)) {
            return;
        }

        $include = "\n// " . self::MARKER . "\n"
            . "if (is_file(\$_SERVER['DOCUMENT_ROOT'] . '" . self::INCLUDE_FILE . "')) {\n"
            . "    require \$_SERVER['DOCUMENT_ROOT'] . '" . self::INCLUDE_FILE . "';\n"
            . "}\n";

        file_put_contents($menuExtPath, $content . $include);
    }

    public static function uninstall(): void
    {
        $includePath = $_SERVER['DOCUMENT_ROOT'] . self::INCLUDE_FILE;
        if (is_file($includePath)) {
            @unlink($includePath);
        }

        $menuExtPath = $_SERVER['DOCUMENT_ROOT'] . '/.left.menu_ext.php';
        if (!is_file($menuExtPath)) {
            return;
        }

        $content = (string)file_get_contents($menuExtPath);
        $pattern = '/\n\/\/ ' . preg_quote(self::MARKER, '/') . '.*?(?=\n\/\/ |\z)/s';
        $content = preg_replace($pattern, '', $content, 1) ?? $content;
        file_put_contents($menuExtPath, $content);
    }

    private static function writeIncludeFile(): void
    {
        $dir = $_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/include';
        if (!is_dir($dir)) {
            mkdir($dir, BX_DIR_PERMISSIONS, true);
        }

        $php = <<<'PHP'
<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    return;
}

if (!\Bitrix\Main\Loader::includeModule('ooofix.xmlupd')) {
    return;
}

use Ooofix\Xmlupd\Admin\ModuleAccess;
use Ooofix\Xmlupd\Portal\PortalRoutes;

if (!ModuleAccess::canRead()) {
    return;
}

global $aMenuLinksExt;
if (!is_array($aMenuLinksExt)) {
    $aMenuLinksExt = [];
}

foreach ($aMenuLinksExt as $item) {
    if (($item[0] ?? '') === 'XML Документы') {
        return;
    }
}

$aMenuLinksExt[] = [
    'XML Документы',
    PortalRoutes::base(),
    [],
    ['ICON' => 'fileman_menu_icon'],
    '',
    [
        ['Документы', PortalRoutes::documents()],
        ['Настройки', PortalRoutes::settings()],
        ['Логи', PortalRoutes::logs()],
    ],
];
PHP;

        file_put_contents($_SERVER['DOCUMENT_ROOT'] . self::INCLUDE_FILE, $php);
    }
}
