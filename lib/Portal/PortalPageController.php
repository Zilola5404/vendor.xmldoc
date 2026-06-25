<?php

namespace Ooofix\Xmlupd\Portal;

use Bitrix\Main\Loader;
use Ooofix\Xmlupd\Admin\ModuleAccess;
use Ooofix\Xmlupd\ModuleInfo;

/** Базовый контроллер публичных страниц раздела. */
final class PortalPageController
{
    public static function boot(string $activeSectionId, string $pageTitle, callable $bodyRenderer): void
    {
        global $APPLICATION, $USER;

        try {
            if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
                require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';
            }

            if (!is_object($USER) || !$USER->IsAuthorized()) {
                $backurl = (string)($_SERVER['REQUEST_URI'] ?? PortalRoutes::base());
                LocalRedirect('/auth/?backurl=' . urlencode($backurl));
            }

            if (!Loader::includeModule('crm')) {
                self::renderError('Модуль CRM не установлен');

                return;
            }

            if (!ModuleAccess::ensureModuleLoaded()) {
                self::renderError('Модуль ' . ModuleInfo::MODULE_ID . ' не установлен');

                return;
            }

            if (!ModuleAccess::canRead()) {
                self::renderError(
                    'Недостаточно прав. Обратитесь к администратору: Настройки → Права доступа → Права на модуль.'
                );

                return;
            }

            $APPLICATION->SetPageProperty(
                'BodyClass',
                'no-all-paddings pagetitle-toolbar-field-view no-background'
            );
            $APPLICATION->SetTitle(' ');

            require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php';

            PortalLayout::render($activeSectionId, $pageTitle, $bodyRenderer);

            require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php';
        } catch (\Throwable $e) {
            self::renderError($e->getMessage());
        }
    }

    private static function renderError(string $message): void
    {
        global $APPLICATION;

        if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
            require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';
        }

        require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php';
        $APPLICATION->SetTitle(ModuleInfo::MODULE_TITLE);
        ShowError($message);
        require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php';
        exit;
    }
}
