<?php

use Bitrix\Main\EventManager;
use Bitrix\Main\Loader;
use Bitrix\Main\ModuleManager;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

IncludeModuleLangFile(__FILE__);

class ooofix_xmlupd extends CModule
{
    public $MODULE_ID = 'ooofix.xmlupd';
    public $MODULE_VERSION;
    public $MODULE_VERSION_DATE;
    public $MODULE_NAME;
    public $MODULE_DESCRIPTION;
    public $PARTNER_NAME = 'ООО "РЕШЕНИЕ"';
    public $PARTNER_URI = 'https://ooofix.ru';
    public $MODULE_GROUP_RIGHTS = 'Y';

    public function __construct()
    {
        $arModuleVersion = [];
        include __DIR__ . '/version.php';

        $this->MODULE_VERSION      = $arModuleVersion['VERSION'];
        $this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];
        $this->MODULE_NAME         = GetMessage('OOOFIX_XMLUPD_MODULE_NAME');
        $this->MODULE_DESCRIPTION  = GetMessage('OOOFIX_XMLUPD_MODULE_DESCRIPTION');
    }

    public function DoInstall()
    {
        global $APPLICATION;

        if ($this->isVersionD7() === false) {
            $APPLICATION->ThrowException('Требуется главный модуль D7 (Битрикс 17+)');
            return false;
        }

        if (!IsModuleInstalled('crm')) {
            $APPLICATION->ThrowException('Для работы модуля требуется установленный модуль CRM');
            return false;
        }

        if (!extension_loaded('dom')) {
            $APPLICATION->ThrowException('Требуется PHP-расширение ext-dom (DOMDocument)');
            return false;
        }

        if (!function_exists('iconv')) {
            $APPLICATION->ThrowException('Требуется PHP-функция iconv (конвертация windows-1251)');
            return false;
        }

        $isNew = !IsModuleInstalled($this->MODULE_ID);
        if ($isNew) {
            ModuleManager::registerModule($this->MODULE_ID);
        }

        if (!$this->InstallDB()) {
            if ($isNew) {
                ModuleManager::unRegisterModule($this->MODULE_ID);
            }
            return false;
        }

        $this->InstallEvents();
        $this->InstallFiles();
        $this->InstallActivities();
        $this->InstallTriggers();
        $this->migrateFromLegacySolution();
        $this->cleanupLegacyPortalPaths();
        $this->InstallOptions();
        $this->InstallUserFields();
        $this->applyInstallEnvironment();
        $this->installInfrastructure();
        $this->installPortalPublicPages();

        return true;
    }

    public function DoUpdate()
    {
        $this->InstallEvents();
        $this->InstallFiles();
        $this->InstallActivities();
        $this->InstallTriggers();
        $this->migrateFromLegacySolution();
        $this->cleanupLegacyPortalPaths();
        $this->InstallOptions();
        $this->InstallUserFields();
        $this->upgradeDocumentTable();
        $this->applyInstallEnvironment();
        $this->installInfrastructure();
        $this->installPortalPublicPages();

        return true;
    }

    public function DoUninstall()
    {
        $this->UnInstallEvents();
        $this->UnInstallActivities();
        $this->UnInstallDB();
        $this->UnInstallFiles();
        ModuleManager::unRegisterModule($this->MODULE_ID);

        return true;
    }

    public function InstallDB()
    {
        global $DB, $APPLICATION;

        $errors = $DB->RunSQLBatch(__DIR__ . '/db/install.sql');
        if ($errors !== false) {
            $APPLICATION->ThrowException(implode('<br>', $errors));
            return false;
        }

        $this->upgradeDocumentTable();

        return true;
    }

    /** Добавляет колонки версионирования для уже установленных модулей */
    private function upgradeDocumentTable(): void
    {
        global $DB;

        if (!$DB->TableExists('b_xmldoc_document')) {
            return;
        }

        $this->addColumnIfMissing('b_xmldoc_document', 'VERSION', 'VERSION int NOT NULL DEFAULT 1');
        $this->addColumnIfMissing('b_xmldoc_document', 'ENCODING', "ENCODING varchar(16) DEFAULT 'windows-1251'");
        $this->addColumnIfMissing('b_xmldoc_document', 'FILE_HASH', 'FILE_HASH varchar(64) DEFAULT NULL');
        $this->addColumnIfMissing('b_xmldoc_document', 'DOC_STATUS', "DOC_STATUS varchar(32) NOT NULL DEFAULT 'generated'");
        $this->addColumnIfMissing('b_xmldoc_document', 'XML_FORMAT_VERSION', "XML_FORMAT_VERSION varchar(8) DEFAULT '5.03'");
    }

    private function addColumnIfMissing(string $table, string $column, string $ddl): void
    {
        global $DB;

        $res = $DB->Query("SHOW COLUMNS FROM `{$table}` LIKE '{$column}'");
        if ($res && !$res->Fetch()) {
            $DB->Query("ALTER TABLE `{$table}` ADD {$ddl}");
        }
    }

    public function UnInstallDB()
    {
        global $DB;
        $DB->RunSQLBatch(__DIR__ . '/db/uninstall.sql');

        return true;
    }

    public function InstallEvents()
    {
        $this->unRegisterUiEvents();
        $this->unRegisterAutomationEvents();

        EventManager::getInstance()->registerEventHandler(
            'main',
            'OnProlog',
            $this->MODULE_ID,
            'Ooofix\\Xmlupd\\Event\\Ui',
            'onProlog'
        );

        EventManager::getInstance()->registerEventHandler(
            'main',
            'OnEpilog',
            $this->MODULE_ID,
            'Ooofix\\Xmlupd\\Event\\Ui',
            'onEpilog'
        );

        EventManager::getInstance()->registerEventHandler(
            'main',
            'OnBuildGlobalMenu',
            $this->MODULE_ID,
            'Ooofix\\Xmlupd\\Event\\AdminMenu',
            'onBuildGlobalMenu'
        );

        EventManager::getInstance()->registerEventHandler(
            'main',
            'OnBuildMenu',
            $this->MODULE_ID,
            'Ooofix\\Xmlupd\\Event\\PublicMenu',
            'onBuildMenu'
        );

        EventManager::getInstance()->registerEventHandler(
            'crm',
            'OnAfterCrmControlPanelBuild',
            $this->MODULE_ID,
            'Ooofix\\Xmlupd\\Event\\CrmMenu',
            'onAfterCrmControlPanelBuild'
        );

        EventManager::getInstance()->registerEventHandlerCompatible(
            'crm',
            'OnAfterCrmControlPanelBuild',
            $this->MODULE_ID,
            'Ooofix\\Xmlupd\\Event\\CrmMenu',
            'onAfterCrmControlPanelBuild'
        );

        foreach ($this->getAutomationTriggerClasses() as $sort => $triggerClass) {
            EventManager::getInstance()->registerEventHandler(
                'crm',
                'OnAutomationTriggerList',
                $this->MODULE_ID,
                'Ooofix\\Xmlupd\\Event\\CrmAutomation',
                'appendTrigger',
                100 + $sort,
                '',
                [$triggerClass]
            );
        }

        return true;
    }

    public function InstallTriggers(): bool
    {
        if (!Loader::includeModule('crm')) {
            return true;
        }

        $this->ensureModuleAutoload();

        if (class_exists(\Ooofix\Xmlupd\Automation\TriggerRegistry::class)) {
            \Ooofix\Xmlupd\Automation\TriggerRegistry::installAll();
        }

        return true;
    }

    public function UnInstallEvents()
    {
        $this->unRegisterAutomationEvents();
        $this->unRegisterUiEvents();

        $lib = dirname(__DIR__) . '/lib/Install/EventInstaller.php';
        if (is_file($lib)) {
            require_once $lib;
            \Ooofix\Xmlupd\Install\EventInstaller::uninstallAll($this->MODULE_ID);
        }

        return true;
    }

    private function unRegisterAutomationEvents(): void
    {
        foreach ($this->getAutomationTriggerClasses() as $triggerClass) {
            EventManager::getInstance()->unRegisterEventHandler(
                'crm',
                'OnAutomationTriggerList',
                $this->MODULE_ID,
                'Ooofix\\Xmlupd\\Event\\CrmAutomation',
                'appendTrigger',
                '',
                [$triggerClass]
            );
        }
    }

    private function unRegisterUiEvents(): void
    {
        $events = [
            ['main', 'OnProlog', 'Ooofix\\Xmlupd\\Event\\Ui', 'onProlog'],
            ['main', 'OnEpilog', 'Ooofix\\Xmlupd\\Event\\Ui', 'onEpilog'],
            ['main', 'OnBuildGlobalMenu', 'Ooofix\\Xmlupd\\Event\\AdminMenu', 'onBuildGlobalMenu'],
            ['main', 'OnBuildMenu', 'Ooofix\\Xmlupd\\Event\\PublicMenu', 'onBuildMenu'],
            ['crm', 'OnAfterCrmControlPanelBuild', 'Ooofix\\Xmlupd\\Event\\CrmMenu', 'onAfterCrmControlPanelBuild'],
        ];

        foreach ($events as [$module, $event, $class, $method]) {
            EventManager::getInstance()->unRegisterEventHandler(
                $module,
                $event,
                $this->MODULE_ID,
                $class,
                $method
            );

            if ($event === 'OnAfterCrmControlPanelBuild'
                && method_exists(EventManager::getInstance(), 'unRegisterEventHandlerCompatible')
            ) {
                EventManager::getInstance()->unRegisterEventHandlerCompatible(
                    $module,
                    $event,
                    $this->MODULE_ID,
                    $class,
                    $method
                );
            }
        }
    }

    public function InstallOptions()
    {
        $defaultsFile = dirname(__DIR__) . '/default_option.php';
        if (!is_file($defaultsFile)) {
            return true;
        }

        include $defaultsFile;
        if (empty($ooofix_xmlupd_default_option) || !is_array($ooofix_xmlupd_default_option)) {
            return true;
        }

        foreach ($ooofix_xmlupd_default_option as $name => $value) {
            $marker = '__OOOFIX_XMLUPD_UNSET__';
            if (\Bitrix\Main\Config\Option::get($this->MODULE_ID, $name, $marker) === $marker) {
                \Bitrix\Main\Config\Option::set($this->MODULE_ID, $name, (string)$value);
            }
        }

        if ((int)\Bitrix\Main\Config\Option::get($this->MODULE_ID, 'smart_invoice_type_id', '0') <= 0) {
            \Bitrix\Main\Config\Option::set($this->MODULE_ID, 'smart_invoice_type_id', '31');
        }

        return true;
    }

    public function InstallActivities()
    {
        $source = __DIR__ . '/activities/ooofixxmlupdgenerate';
        if (!is_dir($source)) {
            return true;
        }

        $roots = [
            $_SERVER['DOCUMENT_ROOT'] . '/local/activities',
            $_SERVER['DOCUMENT_ROOT'] . '/bitrix/activities',
        ];

        foreach ($roots as $root) {
            if (!is_dir($root)) {
                @mkdir($root, BX_DIR_PERMISSIONS, true);
            }
            if (!is_dir($root)) {
                continue;
            }

            CopyDirFiles(
                $source,
                $root . '/ooofixxmlupdgenerate',
                true,
                true
            );
        }

        return true;
    }

    public function UnInstallActivities()
    {
        foreach ([
            '/local/activities/ooofixxmlupdgenerate',
            '/bitrix/activities/ooofixxmlupdgenerate',
            '/local/activities/xmldocgenerateupd',
            '/bitrix/activities/xmldocgenerateupd',
        ] as $relPath) {
            $path = $_SERVER['DOCUMENT_ROOT'] . $relPath;
            if (is_dir($path)) {
                DeleteDirFilesEx($relPath);
            }
        }

        return true;
    }

    public function InstallFiles()
    {
        CopyDirFiles(
            __DIR__ . '/admin',
            $_SERVER['DOCUMENT_ROOT'] . '/bitrix/admin',
            true,
            true
        );

        CopyDirFiles(
            __DIR__ . '/js',
            $_SERVER['DOCUMENT_ROOT'] . '/bitrix/js/ooofix/xmlupd',
            true,
            true
        );

        CopyDirFiles(
            __DIR__ . '/tools',
            $_SERVER['DOCUMENT_ROOT'] . '/bitrix/tools',
            true,
            false
        );

        $cssTarget = $_SERVER['DOCUMENT_ROOT'] . '/bitrix/css/ooofix/xmlupd';
        if (!is_dir($cssTarget)) {
            mkdir($cssTarget, BX_DIR_PERMISSIONS, true);
        }

        CopyDirFiles(
            __DIR__ . '/css',
            $cssTarget,
            true,
            true
        );

        $crmPublic = __DIR__ . '/public/crm/ooofix_xmlupd';
        if (is_dir($crmPublic)) {
            CopyDirFiles(
                $crmPublic,
                $_SERVER['DOCUMENT_ROOT'] . '/crm/ooofix_xmlupd',
                true,
                true
            );
        }

        $this->installPortalPublicPages();

        return true;
    }

    /**
     * Публичные страницы раздела в DOCUMENT_ROOT (/crm/ooofix_xmlupd/*).
     * Логика — в install/public/xml_documents/ модуля.
     */
    private function installPortalPublicPages(): void
    {
        $installer = dirname(__DIR__) . '/lib/Install/SiteSectionInstaller.php';
        if (is_file($installer)) {
            require_once $installer;
            \Ooofix\Xmlupd\Install\SiteSectionInstaller::install();
        }
    }

    private function installInfrastructure(): void
    {
        $this->ensureModuleAutoload();

        if (class_exists(\Ooofix\Xmlupd\Install\UrlRewriteInstaller::class)) {
            \Ooofix\Xmlupd\Install\UrlRewriteInstaller::install($this->MODULE_ID);
        }

        if (class_exists(\Ooofix\Xmlupd\Install\ModuleRightsInstaller::class)) {
            \Ooofix\Xmlupd\Install\ModuleRightsInstaller::install($this->MODULE_ID);
        }

        if (class_exists(\Ooofix\Xmlupd\Install\LeftMenuExtInstaller::class)) {
            \Ooofix\Xmlupd\Install\LeftMenuExtInstaller::install();
        }

        if (class_exists(\Ooofix\Xmlupd\Install\InitPhpInstaller::class)) {
            \Ooofix\Xmlupd\Install\InitPhpInstaller::install();
        }
    }

    private function uninstallInfrastructure(): void
    {
        $libDir = dirname(__DIR__) . '/lib/Install';
        $urlRewrite = $libDir . '/UrlRewriteInstaller.php';
        if (is_file($urlRewrite)) {
            require_once $urlRewrite;
            \Ooofix\Xmlupd\Install\UrlRewriteInstaller::uninstall();
        }
    }

    public function UnInstallFiles()
    {
        DeleteDirFiles(
            __DIR__ . '/admin',
            $_SERVER['DOCUMENT_ROOT'] . '/bitrix/admin'
        );

        if (is_dir($_SERVER['DOCUMENT_ROOT'] . '/bitrix/js/ooofix/xmlupd')) {
            DeleteDirFilesEx('/bitrix/js/ooofix/xmlupd');
        }

        if (is_dir($_SERVER['DOCUMENT_ROOT'] . '/bitrix/js/vendor/xmldoc')) {
            DeleteDirFilesEx('/bitrix/js/vendor/xmldoc');
        }

        $toolsFile = $_SERVER['DOCUMENT_ROOT'] . '/bitrix/tools/ooofix_xmlupd_generate.php';
        if (is_file($toolsFile)) {
            @unlink($toolsFile);
        }

        foreach ([
            'ooofix_xmlupd_settings_api.php',
            'vendor_xml_generate.php',
            'ooofix_vendor_xml_generate.php',
            'ooofix_vendor_xml_settings_api.php',
        ] as $toolName) {
            $path = $_SERVER['DOCUMENT_ROOT'] . '/bitrix/tools/' . $toolName;
            if (is_file($path)) {
                @unlink($path);
            }
        }

        if (is_dir($_SERVER['DOCUMENT_ROOT'] . '/bitrix/css/ooofix/xmlupd')) {
            DeleteDirFilesEx('/bitrix/css/ooofix/xmlupd');
        }

        if (is_dir($_SERVER['DOCUMENT_ROOT'] . '/bitrix/css/ooofix/vendorxml')) {
            DeleteDirFilesEx('/bitrix/css/ooofix/vendorxml');
        }

        $portalInstaller = dirname(__DIR__) . '/lib/Install/SiteSectionInstaller.php';
        if (is_file($portalInstaller)) {
            require_once $portalInstaller;
            \Ooofix\Xmlupd\Install\SiteSectionInstaller::uninstall();
        } else {
            foreach ([
                '/crm/ooofix_xmlupd',
                '/crm/ooofix_vendor_xml',
                '/crm/xml_documents',
                '/xml_documents',
            ] as $relPath) {
                if (is_dir($_SERVER['DOCUMENT_ROOT'] . $relPath)) {
                    DeleteDirFilesEx($relPath);
                }
            }
        }

        if (class_exists(\Ooofix\Xmlupd\Install\LeftMenuExtInstaller::class)) {
            \Ooofix\Xmlupd\Install\LeftMenuExtInstaller::uninstall();
        }

        if (class_exists(\Ooofix\Xmlupd\Install\InitPhpInstaller::class)) {
            \Ooofix\Xmlupd\Install\InitPhpInstaller::uninstall();
        }

        $this->uninstallInfrastructure();

        return true;
    }

    public function InstallUserFields()
    {
        if (!CModule::IncludeModule('crm')) {
            return true;
        }

        $smartTypeId = (int)\Bitrix\Main\Config\Option::get('ooofix.xmlupd', 'smart_invoice_type_id', '31');

        if (class_exists(\Ooofix\Xmlupd\Install\UserFieldInstaller::class)) {
            \Ooofix\Xmlupd\Install\UserFieldInstaller::installAll($smartTypeId);

            return true;
        }

        $this->ensureUserField('CRM_DEAL', 'UF_UPD_NUMBER', 'string', 'Номер УПД (1С)');
        $this->ensureUserField('CRM_DEAL', 'UF_UPD_FILE', 'file', 'Файл УПД');

        if ($smartTypeId > 0) {
            $entityId = 'CRM_' . $smartTypeId;
            $this->ensureUserField($entityId, 'UF_UPD_NUMBER', 'string', 'Номер УПД (1С)');
            $this->ensureUserField($entityId, 'UF_UPD_FILE', 'file', 'Файл УПД');
        }

        return true;
    }

    private function ensureUserField($entityId, $fieldName, $type, $label)
    {
        $exists = CUserTypeEntity::GetList([], [
            'ENTITY_ID'  => $entityId,
            'FIELD_NAME' => $fieldName,
        ])->Fetch();

        if ($exists) {
            return;
        }

        $uf = new CUserTypeEntity();
        $uf->Add([
            'ENTITY_ID'         => $entityId,
            'FIELD_NAME'        => $fieldName,
            'USER_TYPE_ID'      => $type,
            'MULTIPLE'          => 'N',
            'MANDATORY'         => 'N',
            'EDIT_FORM_LABEL'   => ['ru' => $label, 'en' => $label],
            'LIST_COLUMN_LABEL' => ['ru' => $label, 'en' => $label],
        ]);
    }

    private function isVersionD7()
    {
        return CheckVersion(ModuleManager::getVersion('main'), '17.0.0');
    }

    /** Определяет окружение (коробка/облако) и применяет профиль установки. */
    private function applyInstallEnvironment(): void
    {
        $this->ensureModuleAutoload();

        if (!class_exists(\Ooofix\Xmlupd\Install\InstallEnvironment::class)) {
            return;
        }

        \Ooofix\Xmlupd\Install\InstallEnvironment::apply($this->MODULE_ID);
    }

    /** Подключает autoload модуля (install/index.php не загружает include.php автоматически). */
    private function ensureModuleAutoload(): void
    {
        if (class_exists(\Ooofix\Xmlupd\Automation\TriggerRegistry::class, false)) {
            return;
        }

        Loader::includeModule($this->MODULE_ID);

        if (!class_exists(\Ooofix\Xmlupd\Automation\TriggerRegistry::class, false)) {
            $includeFile = dirname(__DIR__) . '/include.php';
            if (is_file($includeFile)) {
                require_once $includeFile;
            }
        }
    }

    private function cleanupLegacyPortalPaths(): void
    {
        $this->ensureModuleAutoload();

        if (class_exists(\Ooofix\Xmlupd\Install\LegacyModuleMigration::class)) {
            \Ooofix\Xmlupd\Install\LegacyModuleMigration::cleanupLegacyArtifacts();
        }
    }

    private function migrateFromLegacySolution(): void
    {
        $this->ensureModuleAutoload();

        if (class_exists(\Ooofix\Xmlupd\Install\LegacyModuleMigration::class)) {
            \Ooofix\Xmlupd\Install\LegacyModuleMigration::run($this->MODULE_ID);
        }
    }

    /**
     * Список классов триггеров для регистрации/снятия обработчиков.
     * Fallback без TriggerRegistry — для корректного удаления модуля.
     *
     * @return list<class-string>
     */
    private function getAutomationTriggerClasses(): array
    {
        $this->ensureModuleAutoload();

        if (class_exists(\Ooofix\Xmlupd\Automation\TriggerRegistry::class)) {
            return \Ooofix\Xmlupd\Automation\TriggerRegistry::triggerClasses();
        }

        return [
            'Ooofix\\Xmlupd\\Automation\\Trigger\\UpdGeneratedTrigger',
            'Ooofix\\Xmlupd\\Automation\\Trigger\\EdoSentTrigger',
            'Ooofix\\Xmlupd\\Automation\\Trigger\\EdoDeliveredTrigger',
            'Ooofix\\Xmlupd\\Automation\\Trigger\\EdoAcceptedTrigger',
            'Ooofix\\Xmlupd\\Automation\\Trigger\\EdoRejectedTrigger',
        ];
    }
}

/**
 * Права на модуль (Настройки → Права доступа → Права на модуль).
 *
 * @return array<string, string>
 */
function ooofix_xmlupd_GetModuleRightList(): array
{
    return [
        'D' => GetMessage('OOOFIX_XMLUPD_RIGHT_DENIED'),
        'R' => GetMessage('OOOFIX_XMLUPD_RIGHT_READ'),
        'W' => GetMessage('OOOFIX_XMLUPD_RIGHT_WRITE'),
    ];
}
