<?php

namespace Ooofix\Xmlupd\Install;

/** Подключение меню портала через /local/php_interface/init.php (резерв для Bitrix24). */
final class InitPhpInstaller
{
    public const MARKER = 'OOOFIX_XMLUPD_INIT';
    public const INCLUDE_FILE = '/local/php_interface/include/ooofix_xmlupd_init.php';

    public static function install(): void
    {
        self::writeIncludeFile();

        $initPath = $_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/init.php';
        if (!is_dir(dirname($initPath))) {
            mkdir(dirname($initPath), BX_DIR_PERMISSIONS, true);
        }

        if (!is_file($initPath)) {
            file_put_contents($initPath, "<?php\n");
        }

        $content = (string)file_get_contents($initPath);
        if (str_contains($content, self::MARKER)) {
            return;
        }

        $include = "\n// " . self::MARKER . "\n"
            . "if (is_file(\$_SERVER['DOCUMENT_ROOT'] . '" . self::INCLUDE_FILE . "')) {\n"
            . "    require_once \$_SERVER['DOCUMENT_ROOT'] . '" . self::INCLUDE_FILE . "';\n"
            . "}\n";

        file_put_contents($initPath, $content . $include);
    }

    public static function uninstall(): void
    {
        $includePath = $_SERVER['DOCUMENT_ROOT'] . self::INCLUDE_FILE;
        if (is_file($includePath)) {
            @unlink($includePath);
        }

        $initPath = $_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/init.php';
        if (!is_file($initPath)) {
            return;
        }

        $content = (string)file_get_contents($initPath);
        $pattern = '/\n\/\/ ' . preg_quote(self::MARKER, '/') . '.*?(?=\n\/\/ |\z)/s';
        $content = preg_replace($pattern, '', $content, 1) ?? $content;
        file_put_contents($initPath, $content);
    }

    private static function writeIncludeFile(): void
    {
        $dir = $_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/include';
        if (!is_dir($dir)) {
            mkdir($dir, BX_DIR_PERMISSIONS, true);
        }

        $php = <<<'PHP'
<?php

use Bitrix\Main\EventManager;
use Ooofix\Xmlupd\Event\PublicMenu;

if (!class_exists(EventManager::class)) {
    return;
}

$manager = EventManager::getInstance();
$manager->registerEventHandler(
    'main',
    'OnBuildMenu',
    'ooofix.xmlupd',
    PublicMenu::class,
    'onBuildMenu'
);
PHP;

        file_put_contents($_SERVER['DOCUMENT_ROOT'] . self::INCLUDE_FILE, $php);
    }
}
