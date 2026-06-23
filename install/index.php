<?php

use Bitrix\Main\EventManager;
use Bitrix\Main\Loader;
use Bitrix\Main\ModuleManager;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

IncludeModuleLangFile(__FILE__);

class vendor_xml extends CModule
{
    public $MODULE_ID = 'vendor.xml';
    public $MODULE_VERSION;
    public $MODULE_VERSION_DATE;
    public $MODULE_NAME;
    public $MODULE_DESCRIPTION;
    public $PARTNER_NAME = 'Vendor';
    public $PARTNER_URI = '';
    public $MODULE_GROUP_RIGHTS = 'N';

    public function __construct()
    {
        $arModuleVersion = [];
        include __DIR__ . '/version.php';

        $this->MODULE_VERSION      = $arModuleVersion['VERSION'];
        $this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];
        $this->MODULE_NAME         = GetMessage('VENDOR_XMLDOC_MODULE_NAME');
        $this->MODULE_DESCRIPTION  = GetMessage('VENDOR_XMLDOC_MODULE_DESCRIPTION');
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
        $this->InstallOptions();
        $this->InstallUserFields();
        $this->applyInstallEnvironment();

        return true;
    }

    public function DoUpdate()
    {
        $this->InstallEvents();
        $this->InstallFiles();
        $this->InstallActivities();
        $this->InstallTriggers();
        $this->InstallOptions();
        $this->InstallUserFields();
        $this->upgradeDocumentTable();
        $this->applyInstallEnvironment();

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
            'Vendor\\Xmldoc\\Event\\Ui',
            'onProlog'
        );

        EventManager::getInstance()->registerEventHandler(
            'main',
            'OnEpilog',
            $this->MODULE_ID,
            'Vendor\\Xmldoc\\Event\\Ui',
            'onEpilog'
        );

        EventManager::getInstance()->registerEventHandler(
            'main',
            'OnBuildGlobalMenu',
            $this->MODULE_ID,
            'Vendor\\Xmldoc\\Event\\AdminMenu',
            'onBuildGlobalMenu'
        );

        foreach ($this->getAutomationTriggerClasses() as $sort => $triggerClass) {
            EventManager::getInstance()->registerEventHandler(
                'crm',
                'OnAutomationTriggerList',
                $this->MODULE_ID,
                'Vendor\\Xmldoc\\Event\\CrmAutomation',
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

        if (class_exists(\Vendor\Xmldoc\Automation\TriggerRegistry::class)) {
            \Vendor\Xmldoc\Automation\TriggerRegistry::installAll();
        }

        return true;
    }

    public function UnInstallEvents()
    {
        $this->unRegisterUiEvents();
        $this->unRegisterAutomationEvents();

        return true;
    }

    private function unRegisterAutomationEvents(): void
    {
        foreach ($this->getAutomationTriggerClasses() as $triggerClass) {
            EventManager::getInstance()->unRegisterEventHandler(
                'crm',
                'OnAutomationTriggerList',
                $this->MODULE_ID,
                'Vendor\\Xmldoc\\Event\\CrmAutomation',
                'appendTrigger',
                '',
                [$triggerClass]
            );
        }
    }

    private function unRegisterUiEvents(): void
    {
        $events = [
            ['main', 'OnProlog', 'onProlog'],
            ['main', 'OnEpilog', 'onEpilog'],
            ['main', 'OnBuildGlobalMenu', 'onBuildGlobalMenu'],
        ];

        foreach ($events as [$module, $event, $method]) {
            $class = $method === 'onBuildGlobalMenu'
                ? 'Vendor\\Xmldoc\\Event\\AdminMenu'
                : 'Vendor\\Xmldoc\\Event\\Ui';

            EventManager::getInstance()->unRegisterEventHandler(
                $module,
                $event,
                $this->MODULE_ID,
                $class,
                $method
            );
        }
    }

    public function InstallOptions()
    {
        $defaultsFile = dirname(__DIR__) . '/default_option.php';
        if (!is_file($defaultsFile)) {
            return true;
        }

        include $defaultsFile;
        if (empty($vendor_xml_default_option) || !is_array($vendor_xml_default_option)) {
            return true;
        }

        foreach ($vendor_xml_default_option as $name => $value) {
            $marker = '__XMLDOC_UNSET__';
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
        $source = __DIR__ . '/activities/xmldocgenerateupd';
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
                $root . '/xmldocgenerateupd',
                true,
                true
            );
        }

        return true;
    }

    public function UnInstallActivities()
    {
        foreach ([
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
            $_SERVER['DOCUMENT_ROOT'] . '/bitrix/js/vendor/xmldoc',
            true,
            true
        );

        CopyDirFiles(
            __DIR__ . '/tools',
            $_SERVER['DOCUMENT_ROOT'] . '/bitrix/tools',
            true,
            false
        );

        return true;
    }

    public function UnInstallFiles()
    {
        DeleteDirFiles(
            __DIR__ . '/admin',
            $_SERVER['DOCUMENT_ROOT'] . '/bitrix/admin'
        );

        if (is_dir($_SERVER['DOCUMENT_ROOT'] . '/bitrix/js/vendor/xmldoc')) {
            DeleteDirFilesEx('/bitrix/js/vendor/xmldoc');
        }

        $toolsFile = $_SERVER['DOCUMENT_ROOT'] . '/bitrix/tools/vendor_xml_generate.php';
        if (is_file($toolsFile)) {
            @unlink($toolsFile);
        }

        return true;
    }

    public function InstallUserFields()
    {
        if (!CModule::IncludeModule('crm')) {
            return true;
        }

        $smartTypeId = (int)\Bitrix\Main\Config\Option::get('vendor.xml', 'smart_invoice_type_id', '31');

        if (class_exists(\Vendor\Xmldoc\Install\UserFieldInstaller::class)) {
            \Vendor\Xmldoc\Install\UserFieldInstaller::installAll($smartTypeId);

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

        if (!class_exists(\Vendor\Xmldoc\Install\InstallEnvironment::class)) {
            return;
        }

        \Vendor\Xmldoc\Install\InstallEnvironment::apply($this->MODULE_ID);
    }

    /** Подключает autoload модуля (install/index.php не загружает include.php автоматически). */
    private function ensureModuleAutoload(): void
    {
        if (class_exists(\Vendor\Xmldoc\Automation\TriggerRegistry::class, false)) {
            return;
        }

        Loader::includeModule($this->MODULE_ID);

        if (!class_exists(\Vendor\Xmldoc\Automation\TriggerRegistry::class, false)) {
            $includeFile = dirname(__DIR__) . '/include.php';
            if (is_file($includeFile)) {
                require_once $includeFile;
            }
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

        if (class_exists(\Vendor\Xmldoc\Automation\TriggerRegistry::class)) {
            return \Vendor\Xmldoc\Automation\TriggerRegistry::triggerClasses();
        }

        return [
            'Vendor\\Xmldoc\\Automation\\Trigger\\UpdGeneratedTrigger',
            'Vendor\\Xmldoc\\Automation\\Trigger\\EdoSentTrigger',
            'Vendor\\Xmldoc\\Automation\\Trigger\\EdoDeliveredTrigger',
            'Vendor\\Xmldoc\\Automation\\Trigger\\EdoAcceptedTrigger',
            'Vendor\\Xmldoc\\Automation\\Trigger\\EdoRejectedTrigger',
        ];
    }
}
