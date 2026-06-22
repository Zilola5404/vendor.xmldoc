<?php

/**
 * Страница настроек модуля.
 * Открывается: Настройки → Настройки продукта → Настройки модулей → vendor.xmldoc
 * URL: /bitrix/admin/settings.php?mid=vendor.xmldoc&lang=ru
 */

use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

$module_id = 'vendor.xmldoc';

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
    'upd_function',
    'file_encoding',
    'crm_adapter',
    'cloud_rest_webhook',
];

$portalLabel = 'не определён';
$runtimeModuleId = 'vendor.xmldoc';
$runtimeReady = true;
if (Loader::includeModule('vendor.xmldoc') && class_exists(\Vendor\Xmldoc\Environment\PortalEnvironment::class)) {
    $portalLabel = \Vendor\Xmldoc\Environment\PortalEnvironment::label();
    $runtimeModuleId = \Vendor\Xmldoc\Environment\PortalEnvironment::activeRuntimeModuleId();
    $runtimeReady = \Vendor\Xmldoc\Environment\PortalEnvironment::isCloudRuntimeReady();
}

// Принудительное создание UF_UPD_* (сделки + СП «Счета»)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && check_bitrix_sessid() && isset($_POST['install_uf'])) {
    Loader::includeModule('vendor.xmldoc');
    $module = CModule::CreateModuleObject('vendor.xmldoc');
    if ($module !== null && method_exists($module, 'InstallUserFields')) {
        $module->InstallUserFields();
    }
    LocalRedirect($APPLICATION->GetCurPage() . '?mid=' . urlencode($module_id) . '&lang=' . LANGUAGE_ID . '&uf=Y');
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

    Loader::includeModule('vendor.xmldoc');
    $module = CModule::CreateModuleObject('vendor.xmldoc');
    if ($module !== null && method_exists($module, 'InstallUserFields')) {
        $module->InstallUserFields();
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
            <br><small>По умолчанию: 31 (СП «Счета»)</small>
        </td>
    </tr>
    <tr>
        <td>Функция документа (Функция):</td>
        <td>
            <input type="text" name="upd_function" size="20" value="<?= htmlspecialcharsbx(Option::get($module_id, 'upd_function', 'СЧФДОП')) ?>">
        </td>
    </tr>
    <tr>
        <td>Путь к XSD-схеме:</td>
        <td>
            <input type="text" name="xsd_path" size="60" value="<?= htmlspecialcharsbx(Option::get($module_id, 'xsd_path')) ?>">
            <br><small>Абсолютный путь на сервере, например /home/bitrix/www/local/modules/vendor.xmldoc/config/schemas/upd.xsd</small>
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
        <td colspan="2"><b>Окружение</b> (тип портала: <?= htmlspecialcharsbx($portalLabel) ?>)</td>
    </tr>
    <tr>
        <td>Активный runtime:</td>
        <td>
            <code><?= htmlspecialcharsbx($runtimeModuleId) ?></code>
            <?php if (!$runtimeReady): ?>
                <br><span style="color:#c00">На облаке требуется модуль vendor.xmldoc.cloud (переустановите vendor.xmldoc или установите вручную).</span>
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
    <?php $tabControl->End(); ?>
</form>

<p style="margin-top:16px">
    <a href="/bitrix/admin/vendor_xmldoc_documents.php?lang=<?= LANGUAGE_ID ?>">История документов УПД</a>
    &nbsp;|&nbsp;
    <a href="/bitrix/admin/vendor_xmldoc_log.php?lang=<?= LANGUAGE_ID ?>">Журнал генерации</a>
</p>
