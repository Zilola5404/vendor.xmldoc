<?php

/** @deprecated Используйте /bitrix/admin/settings.php?mid=ooofix.vendor.xml */
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_before.php';
LocalRedirect('/bitrix/admin/settings.php?mid=ooofix.vendor.xml&lang=' . LANGUAGE_ID);
