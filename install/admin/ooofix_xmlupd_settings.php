<?php

/** @deprecated Используйте /bitrix/admin/settings.php?mid=ooofix.xmlupd */
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_before.php';
LocalRedirect('/bitrix/admin/settings.php?mid=ooofix.xmlupd&lang=' . LANGUAGE_ID);
