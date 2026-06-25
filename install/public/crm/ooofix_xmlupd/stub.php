<?php

$bootstrap = getLocalPath('modules/ooofix.xmlupd/install/public/xml_documents/bootstrap.php');
$bootstrapPath = is_string($bootstrap) ? $_SERVER['DOCUMENT_ROOT'] . $bootstrap : '';

if ($bootstrapPath === '' || !is_file($bootstrapPath)) {
    require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';
    require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php';
    ShowError('Обновите модуль ooofix.xmlupd до последней версии.');
    require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php';
    return;
}

require $bootstrapPath;

use Ooofix\Xmlupd\Portal\PortalRoutes;

LocalRedirect(PortalRoutes::settings());
