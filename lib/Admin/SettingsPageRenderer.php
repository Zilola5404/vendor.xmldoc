<?php

namespace Ooofix\Xmlupd\Admin;

use Bitrix\Main\Page\Asset;
use Bitrix\Main\UI\Extension;
use Ooofix\Xmlupd\ModuleInfo;

/** UI настроек (схема + AJAX) для портала и админки. */
final class SettingsPageRenderer
{
    /**
     * @param array{
     *   canWrite: bool,
     *   layout?: string,
     *   isSidePanel?: bool,
     *   useAjax?: bool
     * } $context
     */
    public static function render(array $context): void
    {
        self::loadAssets($context);

        $values = SettingsService::getAll();
        $displayLabels = SettingsCrmLabels::displayMap($values);
        $canWrite = (bool)$context['canWrite'];
        $layout = (string)($context['layout'] ?? 'portal');
        $isSidePanel = (bool)($context['isSidePanel'] ?? self::isSidePanelRequest());
        $useAjax = (bool)($context['useAjax'] ?? $layout !== 'admin');

        $rootClass = 'ox-upd-settings'
            . ($layout === 'portal' ? ' ox-upd-settings--portal' : '')
            . ($layout === 'admin' ? ' ox-upd-settings--admin' : '')
            . ($isSidePanel ? ' ox-upd-settings--sidepanel' : '');

        $sections = SettingsFieldRegistry::sections();
        uasort($sections, static fn($a, $b) => ($a['sort'] ?? 0) <=> ($b['sort'] ?? 0));
        $fields = SettingsFieldRegistry::fields();
        ?>
        <div class="<?= htmlspecialcharsbx($rootClass) ?>" id="ox-upd-settings"
            data-ajax="<?= $useAjax ? 'Y' : 'N' ?>"
            data-can-write="<?= $canWrite ? 'Y' : 'N' ?>">
            <?php if (!$canWrite): ?>
                <div class="ui-alert ui-alert-warning ox-upd-settings__flash">
                    <span class="ui-alert-message">Право «Чтение» — изменение настроек недоступно.</span>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['saved']) && $_GET['saved'] === 'Y'): ?>
                <div class="ui-alert ui-alert-success ox-upd-settings__flash">
                    <span class="ui-alert-message">Настройки сохранены.</span>
                </div>
            <?php endif; ?>

            <form class="ox-upd-settings__form" id="ox-upd-settings-form" method="post" action="">
                <?= bitrix_sessid_post() ?>
                <div class="ox-upd-settings__grid">
                    <?php foreach ($sections as $sectionId => $section): ?>
                        <section class="ox-upd-settings__card" data-section="<?= htmlspecialcharsbx($sectionId) ?>">
                            <h2 class="ox-upd-settings__card-title"><?= htmlspecialcharsbx($section['title']) ?></h2>
                            <?php foreach ($fields as $code => $field): ?>
                                <?php if (($field['section'] ?? '') !== $sectionId) {
                                    continue;
                                } ?>
                                <?php self::renderField(
                                    $code,
                                    $field,
                                    $values[$code] ?? '',
                                    $canWrite,
                                    (string)($displayLabels[$code] ?? '')
                                ); ?>
                            <?php endforeach; ?>
                        </section>
                    <?php endforeach; ?>
                </div>

                <?php if ($canWrite): ?>
                    <div class="ox-upd-settings__actions">
                        <button type="submit" name="save" value="Y" class="ui-btn ui-btn-success">Сохранить</button>
                    </div>
                <?php endif; ?>
            </form>
        </div>
        <?php
    }

    /**
     * @param array<string, mixed> $field
     */
    private static function renderField(
        string $code,
        array $field,
        string $value,
        bool $canWrite,
        string $displayLabel = ''
    ): void {
        $type = (string)($field['type'] ?? 'string');
        $label = (string)($field['label'] ?? $code);
        $hint = (string)($field['hint'] ?? '');
        $disabled = $canWrite ? '' : 'disabled';
        $readonly = $canWrite ? '' : 'readonly';
        $display = $displayLabel !== '' ? $displayLabel : ($value !== '' ? $value : '—');
        ?>
        <div class="ox-upd-settings__field" data-field="<?= htmlspecialcharsbx($code) ?>">
            <label class="ox-upd-settings__label" for="ox-field-<?= htmlspecialcharsbx($code) ?>">
                <?= htmlspecialcharsbx($label) ?>
            </label>

            <?php if ($type === 'select'): ?>
                <div class="ui-ctl ui-ctl-dropdown ui-ctl-w100 ox-upd-settings__select">
                    <select class="ui-ctl-element" id="ox-field-<?= htmlspecialcharsbx($code) ?>"
                        name="<?= htmlspecialcharsbx($code) ?>" <?= $disabled ?>>
                        <?php foreach ((array)($field['options'] ?? []) as $optValue => $optLabel): ?>
                            <option value="<?= htmlspecialcharsbx((string)$optValue) ?>"
                                <?= (string)$optValue === $value ? 'selected' : '' ?>>
                                <?= htmlspecialcharsbx((string)$optLabel) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php elseif ($type === 'checkbox'): ?>
                <label class="ox-upd-settings__checkbox">
                    <input type="checkbox" id="ox-field-<?= htmlspecialcharsbx($code) ?>"
                        name="<?= htmlspecialcharsbx($code) ?>" value="Y"
                        <?= $value === 'Y' ? 'checked' : '' ?> <?= $disabled ?>>
                    <?= htmlspecialcharsbx($label) ?>
                </label>
            <?php elseif ($type === 'crm_dynamic_type'): ?>
                <div class="ui-ctl ui-ctl-dropdown ui-ctl-w100 ox-upd-settings__select">
                    <select class="ui-ctl-element" id="ox-field-<?= htmlspecialcharsbx($code) ?>"
                        name="<?= htmlspecialcharsbx($code) ?>" <?= $disabled ?>>
                        <option value="">— выберите —</option>
                        <?php foreach (SettingsCrmLabels::dynamicTypes() as $typeId => $typeTitle): ?>
                            <option value="<?= (int)$typeId ?>"
                                <?= (string)$typeId === $value ? 'selected' : '' ?>>
                                <?= htmlspecialcharsbx($typeTitle) ?> [<?= (int)$typeId ?>]
                            </option>
                        <?php endforeach; ?>
                        <?php if ($value !== '' && !isset(SettingsCrmLabels::dynamicTypes()[(int)$value])): ?>
                            <option value="<?= htmlspecialcharsbx($value) ?>" selected>
                                entityTypeId <?= htmlspecialcharsbx($value) ?>
                            </option>
                        <?php endif; ?>
                    </select>
                </div>
            <?php elseif ($type === 'integer'): ?>
                <div class="ui-ctl ui-ctl-textbox ui-ctl-w100">
                    <input class="ui-ctl-element" type="number" min="0" step="1"
                        id="ox-field-<?= htmlspecialcharsbx($code) ?>"
                        name="<?= htmlspecialcharsbx($code) ?>"
                        value="<?= $value !== '' && (int)$value > 0 ? (int)$value : '' ?>"
                        placeholder="<?= htmlspecialcharsbx((string)($field['placeholder'] ?? '')) ?>"
                        <?= $readonly ?>>
                </div>
            <?php elseif ($type === 'user'): ?>
                <div class="ox-upd-settings__crm-row" data-signatory-user-block="Y">
                    <input type="hidden" id="ox-field-<?= htmlspecialcharsbx($code) ?>"
                        name="<?= htmlspecialcharsbx($code) ?>" value="<?= htmlspecialcharsbx($value) ?>">
                    <div class="ox-upd-settings__crm-chip" data-display-for="<?= htmlspecialcharsbx($code) ?>">
                        <span class="ox-upd-settings__crm-chip-text"><?= htmlspecialcharsbx($display) ?></span>
                    </div>
                    <?php if ($canWrite): ?>
                        <div class="ox-upd-settings__crm-actions">
                            <button type="button" class="ui-btn ui-btn-primary ui-btn-xs ox-upd-settings__crm-select"
                                data-crm-select="<?= htmlspecialcharsbx($code) ?>"
                                data-crm-select-type="user">Изменить</button>
                            <button type="button" class="ui-btn ui-btn-link ui-btn-xs ox-upd-settings__crm-clear"
                                data-crm-clear="<?= htmlspecialcharsbx($code) ?>"
                                <?= $value === '' ? 'hidden' : '' ?>>Очистить</button>
                        </div>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="ui-ctl ui-ctl-textbox ui-ctl-w100">
                    <input class="ui-ctl-element" type="<?= $type === 'password' ? 'password' : 'text' ?>"
                        id="ox-field-<?= htmlspecialcharsbx($code) ?>"
                        name="<?= htmlspecialcharsbx($code) ?>"
                        value="<?= htmlspecialcharsbx($value) ?>" <?= $readonly ?>>
                </div>
            <?php endif; ?>

            <?php if ($hint !== '' && $type !== 'checkbox'): ?>
                <p class="ox-upd-settings__hint"><?= htmlspecialcharsbx($hint) ?></p>
            <?php endif; ?>
            <p class="ox-upd-settings__field-error" data-error-for="<?= htmlspecialcharsbx($code) ?>"></p>
        </div>
        <?php
    }

    public static function isSidePanelRequest(): bool
    {
        return (isset($_GET['IFRAME']) && $_GET['IFRAME'] === 'Y')
            || (isset($_REQUEST['IFRAME_TYPE']) && $_REQUEST['IFRAME_TYPE'] === 'SIDE_SLIDER');
    }

    /** @param array<string, mixed> $context */
    private static function loadAssets(array $context): void
    {
        $asset = Asset::getInstance();
        $version = ModuleInfo::version();

        \CJSCore::Init(['ui.buttons', 'ui.forms', 'ui.alerts', 'ui.notification']);
        Extension::load([
            'ui.entity-selector',
            'ui.forms',
            'ui.dialogs',
            'ui.notification',
        ]);

        foreach (['/bitrix/css/ooofix/xmlupd/settings.css'] as $css) {
            if (is_file($_SERVER['DOCUMENT_ROOT'] . $css)) {
                $asset->addCss($css . '?v=' . rawurlencode($version));
            }
        }

        $jsPath = self::resolveJsPath();
        $asset->addJs($jsPath);

        $dynamicTypes = [];
        foreach (SettingsCrmData::dynamicTypes() as $typeId => $title) {
            $dynamicTypes[] = ['id' => $typeId, 'title' => $title];
        }

        $siteDir = defined('SITE_DIR') ? (string)SITE_DIR : '/';
        if (!str_ends_with($siteDir, '/')) {
            $siteDir .= '/';
        }

        $asset->addString(
            '<script>window.OX_UPD_SETTINGS_CONFIG = ' . \CUtil::PhpToJSObject([
                'moduleId'     => SettingsService::MODULE_ID,
                'useAjax'      => false,
                'dynamicTypes' => $dynamicTypes,
                'apiUrl'       => $siteDir . 'bitrix/tools/ooofix_xmlupd_settings_api.php',
                'sessid'       => bitrix_sessid(),
            ]) . ';</script>'
            . '<script>BX.ready(function(){if(window.OX_UPD_SETTINGS_BOOT){window.OX_UPD_SETTINGS_BOOT();}});</script>',
            true,
            \Bitrix\Main\Page\AssetLocation::AFTER_JS
        );
    }

    private static function resolveJsPath(): string
    {
        $siteDir = defined('SITE_DIR') ? (string)SITE_DIR : '/';
        if (!str_ends_with($siteDir, '/')) {
            $siteDir .= '/';
        }
        $version = ModuleInfo::version();

        if (is_file($_SERVER['DOCUMENT_ROOT'] . '/bitrix/js/ooofix/xmlupd/settings.js')) {
            return $siteDir . 'bitrix/js/ooofix/xmlupd/settings.js?v=' . rawurlencode($version);
        }

        return $siteDir . 'local/modules/ooofix.xmlupd/install/js/settings.js?v=' . rawurlencode($version);
    }
}
