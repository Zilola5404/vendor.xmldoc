<?php

namespace Ooofix\Xmlupd\Admin;

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

/** Контроллер страницы настроек (админка и портал). */
final class SettingsPageController
{
    /**
     * @param array{
     *   layout?: string,
     *   isSidePanel?: bool,
     *   useAjax?: bool
     * } $options
     */
    public static function renderPage(array $options = []): void
    {
        global $APPLICATION;

        Loader::includeModule('main');
        ModuleAccess::ensureModuleLoaded();

        if (!ModuleAccess::canRead()) {
            $APPLICATION->AuthForm(Loc::getMessage('ACCESS_DENIED') ?: 'Доступ запрещён');
        }

        $canWrite = ModuleAccess::canWrite();
        $layout = (string)($options['layout'] ?? 'portal');

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $canWrite && check_bitrix_sessid()) {
            $postResult = SettingsService::handlePost();
            if ($postResult !== null) {
                SettingsService::redirectAfterAction($postResult);
            }
        }

        SettingsPageRenderer::render([
            'canWrite'    => $canWrite,
            'layout'      => $layout,
            'isSidePanel' => (bool)($options['isSidePanel'] ?? SettingsPageRenderer::isSidePanelRequest()),
            'useAjax'     => false,
        ]);
    }
}
