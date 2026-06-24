<?php

/**
 * Страница настроек модуля.
 * Открывается: Настройки → Настройки продукта → Настройки модулей → Генерация XML (УПД)
 * URL: /bitrix/admin/settings.php?mid=ooofix.vendor.xml&lang=ru
 */

use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

$module_id = 'ooofix.vendor.xml';

Loader::includeModule('main');
Loc::loadMessages(__FILE__);

if ($APPLICATION->GetGroupRight($module_id) < 'R') {
    $APPLICATION->AuthForm(Loc::getMessage('ACCESS_DENIED'));
}

$optionCodes = [
    'dadata_api_key',
    'seller_requisite_id',
    'signatory_mode',
    'signatory_user_id',
    'signatory_position',
    'smart_invoice_type_id',
    'publish_timeline',
    'xsd_path',
    'xml_format_version',
    'xsd_schema_revision',
    'upd_function',
    'file_encoding',
    'crm_adapter',
    'cloud_rest_webhook',
    'calculation_mode',
];

$portalLabel = 'не определён';
$runtimePathLabel = 'не определён';
$runtimeReady = true;
if (Loader::includeModule('ooofix.vendor.xml') && class_exists(\Vendor\Xmldoc\Environment\PortalEnvironment::class)) {
    $portalLabel = \Vendor\Xmldoc\Environment\PortalEnvironment::label();
    $runtimePathLabel = \Vendor\Xmldoc\Environment\PortalEnvironment::runtimePathLabel();
    $runtimeReady = \Vendor\Xmldoc\Environment\PortalEnvironment::isCloudRuntimeReady();
}

// Принудительное создание UF_UPD_* (сделки + СП «Счета»)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && check_bitrix_sessid() && isset($_POST['install_uf'])) {
    Loader::includeModule('ooofix.vendor.xml');
    $module = CModule::CreateModuleObject('ooofix.vendor.xml');
    if ($module !== null && method_exists($module, 'InstallUserFields')) {
        $module->InstallUserFields();
    }
    LocalRedirect($APPLICATION->GetCurPage() . '?mid=' . urlencode($module_id) . '&lang=' . LANGUAGE_ID . '&uf=Y');
}

// Автоопределение entityTypeId СП «Счета» (актуально для облака)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && check_bitrix_sessid() && isset($_POST['detect_smart_invoice'])) {
    Loader::includeModule('ooofix.vendor.xml');
    $detected = 0;
    if (class_exists(\Vendor\Xmldoc\Cloud\Crm\SmartInvoiceTypeResolver::class)) {
        $detected = \Vendor\Xmldoc\Cloud\Crm\SmartInvoiceTypeResolver::detectFromCrm();
        if ($detected > 0) {
            Option::set($module_id, 'smart_invoice_type_id', (string)$detected);
            if (class_exists(\Vendor\Xmldoc\DocumentTypeRegistry::class)) {
                \Vendor\Xmldoc\DocumentTypeRegistry::resetCache();
            }
            $module = CModule::CreateModuleObject('ooofix.vendor.xml');
            if ($module !== null && method_exists($module, 'InstallUserFields')) {
                $module->InstallUserFields();
            }
        }
    }
    $query = $detected > 0 ? '&sp_detected=' . $detected : '&sp_not_found=Y';
    LocalRedirect($APPLICATION->GetCurPage() . '?mid=' . urlencode($module_id) . '&lang=' . LANGUAGE_ID . $query);
}

// Сохранение
if ($_SERVER['REQUEST_METHOD'] === 'POST' && check_bitrix_sessid()) {
    foreach ($optionCodes as $code) {
        if ($code === 'publish_timeline') {
            $value = isset($_POST[$code]) && $_POST[$code] === 'Y' ? 'Y' : 'N';
        } else {
            $value = (string)($_POST[$code] ?? '');
        }
        Option::set($module_id, $code, $value);
    }

    Loader::includeModule('ooofix.vendor.xml');
    $module = CModule::CreateModuleObject('ooofix.vendor.xml');
    if ($module !== null && method_exists($module, 'InstallUserFields')) {
        $module->InstallUserFields();
    }
    if (class_exists(\Vendor\Xmldoc\DocumentTypeRegistry::class)) {
        \Vendor\Xmldoc\DocumentTypeRegistry::resetCache();
    }

    LocalRedirect($APPLICATION->GetCurPage() . '?mid=' . urlencode($module_id) . '&lang=' . LANGUAGE_ID . '&saved=Y');
}

$aTabs = [
    [
        'DIV'   => 'edit1',
        'TAB'   => Loc::getMessage('VENDOR_XMLDOC_OPTIONS_TAB'),
        'TITLE' => Loc::getMessage('VENDOR_XMLDOC_OPTIONS_TITLE'),
    ],
];
$tabControl = new CAdminTabControl('tabControl', $aTabs);

if (!empty($_GET['saved'])) {
    CAdminMessage::ShowMessage(['MESSAGE' => 'Настройки сохранены', 'TYPE' => 'OK']);
}

if (!empty($_GET['uf'])) {
    CAdminMessage::ShowMessage(['MESSAGE' => 'Поля UF_UPD_NUMBER и UF_UPD_FILE проверены/созданы', 'TYPE' => 'OK']);
}

if (!empty($_GET['sp_detected'])) {
    CAdminMessage::ShowMessage([
        'MESSAGE' => 'СП «Счета» определён: entityTypeId = ' . (int)$_GET['sp_detected'],
        'TYPE'    => 'OK',
    ]);
}

if (!empty($_GET['sp_not_found'])) {
    CAdminMessage::ShowMessage([
        'MESSAGE' => 'Не удалось автоматически определить СП «Счета». Укажите entityTypeId вручную.',
        'TYPE'    => 'ERROR',
    ]);
}
?>

<form method="post" action="<?= $APPLICATION->GetCurPage() ?>?mid=<?= htmlspecialcharsbx($module_id) ?>&lang=<?= LANGUAGE_ID ?>">
    <?= bitrix_sessid_post() ?>
    <?php $tabControl->Begin(); ?>
    <?php $tabControl->BeginNextTab(); ?>

    <tr>
        <td width="40%">Ключ DaData API:</td>
        <td width="60%">
            <input type="text" name="dadata_api_key" size="60" value="<?= htmlspecialcharsbx(Option::get($module_id, 'dadata_api_key')) ?>">
        </td>
    </tr>
    <tr>
        <td>ID реквизита продавца:</td>
        <td>
            <input type="text" name="seller_requisite_id" size="20" value="<?= htmlspecialcharsbx(Option::get($module_id, 'seller_requisite_id')) ?>">
            <br><small>Пусто = автоматически «Мои реквизиты» B24</small>
        </td>
    </tr>
    <tr>
        <td>Подписант:</td>
        <td>
            <select name="signatory_mode">
                <option value="settings" <?= Option::get($module_id, 'signatory_mode', 'settings') === 'settings' ? 'selected' : '' ?>>Из настроек (ID пользователя)</option>
                <option value="current_user" <?= Option::get($module_id, 'signatory_mode') === 'current_user' ? 'selected' : '' ?>>Текущий пользователь</option>
            </select>
        </td>
    </tr>
    <tr>
        <td>ID подписанта (если режим «Из настроек»):</td>
        <td>
            <input type="text" name="signatory_user_id" size="20" value="<?= htmlspecialcharsbx(Option::get($module_id, 'signatory_user_id')) ?>">
        </td>
    </tr>
    <tr>
        <td>Должность подписанта:</td>
        <td>
            <input type="text" name="signatory_position" size="40" value="<?= htmlspecialcharsbx(Option::get($module_id, 'signatory_position', 'Сотрудник')) ?>">
        </td>
    </tr>
    <tr>
        <td>entityTypeId СП «Счета»:</td>
        <td>
            <input type="text" name="smart_invoice_type_id" size="20" value="<?= htmlspecialcharsbx(Option::get($module_id, 'smart_invoice_type_id', '31')) ?>">
            <br><small>По умолчанию: 31 (коробка). На облаке ID часто другой — нажмите «Определить СП».</small>
        </td>
    </tr>
    <tr>
        <td>Функция документа (Функция):</td>
        <td>
            <input type="text" name="upd_function" size="20" value="<?= htmlspecialcharsbx(Option::get($module_id, 'upd_function', 'СЧФДОП')) ?>">
        </td>
    </tr>
    <tr>
        <td>Версия формата XML (ВерсФорм):</td>
        <td>
            <?php $fmtVer = Option::get($module_id, 'xml_format_version', '5.03'); ?>
            <select name="xml_format_version">
                <option value="5.03" <?= $fmtVer === '5.03' ? 'selected' : '' ?>>5.03 (приказ ФНС №970, актуальная)</option>
                <option value="5.02" <?= $fmtVer === '5.02' ? 'selected' : '' ?>>5.02</option>
            </select>
            <br><small>Схемы: config/schemas/{версия}/ON_NSCHFDOPPR_*.xsd</small>
        </td>
    </tr>
    <tr>
        <td>Ревизия XSD (5.03):</td>
        <td>
            <?php $xsdRev = Option::get($module_id, 'xsd_schema_revision', 'auto'); ?>
            <select name="xsd_schema_revision">
                <option value="auto" <?= $xsdRev === 'auto' ? 'selected' : '' ?>>Авто (последняя в config/schemas)</option>
                <option value="03_05" <?= $xsdRev === '03_05' ? 'selected' : '' ?>>03_05 (с 01.08.2025)</option>
                <option value="03_04" <?= $xsdRev === '03_04' ? 'selected' : '' ?>>03_04</option>
            </select>
        </td>
    </tr>
    <tr>
        <td>Путь к XSD-схеме (override):</td>
        <td>
            <input type="text" name="xsd_path" size="60" value="<?= htmlspecialcharsbx(Option::get($module_id, 'xsd_path')) ?>">
            <br><small>Пусто = автоматически из config/schemas. Абсолютный путь для ручной подмены.</small>
        </td>
    </tr>
    <tr>
        <td>Публиковать в таймлайн:</td>
        <td>
            <input type="checkbox" name="publish_timeline" value="Y"
                <?= Option::get($module_id, 'publish_timeline', 'Y') === 'Y' ? 'checked' : '' ?>>
        </td>
    </tr>
    <tr>
        <td>Кодировка сохраняемого файла:</td>
        <td>
            <select name="file_encoding">
                <option value="windows-1251" <?= Option::get($module_id, 'file_encoding', 'windows-1251') === 'windows-1251' ? 'selected' : '' ?>>windows-1251 (Диадок)</option>
                <option value="UTF-8" <?= Option::get($module_id, 'file_encoding', 'windows-1251') === 'UTF-8' ? 'selected' : '' ?>>UTF-8</option>
            </select>
        </td>
    </tr>
    <tr>
        <td>Режим расчёта сумм УПД:</td>
        <td>
            <?php $calcMode = Option::get($module_id, 'calculation_mode', '1C'); ?>
            <select name="calculation_mode">
                <option value="1C" <?= $calcMode === '1C' ? 'selected' : '' ?>>1С / ЭДО — деление суммы с НДС, итоги из сделки</option>
                <option value="BITRIX24" <?= $calcMode === 'BITRIX24' ? 'selected' : '' ?>>Битрикс24 — цена без НДС × кол-во, итоги = сумма строк</option>
            </select>
            <br><small>
                <b>1С:</b> для Диадoc/СБИС; строки и шапка совпадают с OPPORTUNITY/TAX_VALUE.<br>
                <b>Битрикс24:</b> строки как в таблице товаров сделки; «ВсегоОпл» = сумма строк
                (шапка CRM может отличаться на копейки по НДС). Подробнее: docs/CALCULATION_MODES.md
            </small>
        </td>
    </tr>
    <tr>
        <td colspan="2"><b>Окружение</b> (тип портала: <?= htmlspecialcharsbx($portalLabel) ?>)</td>
    </tr>
    <tr>
        <td>Активный runtime:</td>
        <td>
            <code><?= htmlspecialcharsbx(\Vendor\Xmldoc\ModuleInfo::MODULE_TITLE) ?></code> — <?= htmlspecialcharsbx($runtimePathLabel) ?>
            <?php if (!$runtimeReady): ?>
                <br><span style="color:#c00">Cloud-runtime не найден. Обновите модуль ooofix.vendor.xml до версии 2.0+.</span>
            <?php endif; ?>
        </td>
    </tr>
    <tr>
        <td>Режим CRM-адаптера:</td>
        <td>
            <?php $crmAdapter = Option::get($module_id, 'crm_adapter', 'auto'); ?>
            <select name="crm_adapter">
                <option value="auto" <?= $crmAdapter === 'auto' ? 'selected' : '' ?>>Авто</option>
                <option value="cloud" <?= $crmAdapter === 'cloud' ? 'selected' : '' ?>>Облако</option>
                <option value="onprem" <?= $crmAdapter === 'onprem' ? 'selected' : '' ?>>Коробка</option>
            </select>
            <br><small>Авто: облако определяется по bitrix24 / BX24_HOST_NAME</small>
        </td>
    </tr>
    <tr>
        <td>REST webhook (облако):</td>
        <td>
            <input type="text" name="cloud_rest_webhook" size="60" value="<?= htmlspecialcharsbx(Option::get($module_id, 'cloud_rest_webhook')) ?>">
            <br><small>Входящий webhook для триггеров CRM, если внутренний API недоступен. Пример: https://portal.bitrix24.ru/rest/1/xxx/</small>
        </td>
    </tr>

    <?php $tabControl->Buttons(); ?>
    <input type="submit" name="save" value="Сохранить" class="adm-btn-save">
    <input type="submit" name="install_uf" value="Создать поля УПД" class="adm-btn" title="UF_UPD_NUMBER и UF_UPD_FILE для сделок и СП «Счета»">
    <input type="submit" name="detect_smart_invoice" value="Определить СП «Счета»" class="adm-btn" title="Автоопределение entityTypeId смарт-процесса «Счета» (облако)">
    <?php $tabControl->End(); ?>
</form>

<p style="margin-top:16px">
    <a href="/bitrix/admin/ooofix_vendor_xml_documents.php?lang=<?= LANGUAGE_ID ?>">История документов УПД</a>
    &nbsp;|&nbsp;
    <a href="/bitrix/admin/ooofix_vendor_xml_log.php?lang=<?= LANGUAGE_ID ?>">Журнал генерации</a>
</p>
