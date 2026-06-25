<?php

namespace Ooofix\Xmlupd\Portal;

use Bitrix\Main\Page\Asset;
use Bitrix\Main\UI\Extension;
use Ooofix\Xmlupd\ModuleInfo;

/** Оболочка публичных страниц раздела в CRM. */
final class PortalLayout
{
    public static function render(string $activeSectionId, string $pageTitle, callable $bodyRenderer): void
    {
        self::loadAssets();

        global $APPLICATION;
        $APPLICATION->SetPageProperty('BodyClass', 'no-all-paddings pagetitle-toolbar-field-view no-background');
        ?>
        <div class="ui-page-slider-wrapper ox-xml-portal-wrapper" id="ox-xml-portal-wrapper">
            <div class="ui-page-slider-workarea">
                <div class="ui-slider-page ox-xml-portal__page">
                    <div class="ox-xml-portal" id="ox-xml-portal">
                        <div class="ox-xml-portal__content">
                            <?php $bodyRenderer(); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    private static function loadAssets(): void
    {
        $asset = Asset::getInstance();
        $version = ModuleInfo::version();

        Extension::load([
            'ui.buttons',
            'ui.forms',
            'ui.alerts',
            'ui.notification',
        ]);

        try {
            Extension::load(['ui.design-tokens']);
        } catch (\Throwable) {
            // Не критично для отображения раздела
        }

        foreach (['/bitrix/css/ooofix/xmlupd/portal.css', '/bitrix/css/ooofix/xmlupd/settings.css'] as $css) {
            if (is_file($_SERVER['DOCUMENT_ROOT'] . $css)) {
                $asset->addCss($css . '?v=' . rawurlencode($version));
            }
        }
    }
}
