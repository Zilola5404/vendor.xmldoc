<?php

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_before.php';

use Bitrix\Main\Loader;
use Vendor\Xmldoc\DocumentRegistry;

$moduleId = 'vendor.xmldoc';

if ($APPLICATION->GetGroupRight($moduleId) < 'R') {
    $APPLICATION->AuthForm('Доступ запрещён');
}

Loader::includeModule($moduleId);

$entityType = (string)($_GET['entity_type'] ?? '');
$entityId = (int)($_GET['entity_id'] ?? 0);
$limit = (int)($_GET['limit'] ?? 100);

$rows = DocumentRegistry::fetchList(
    $limit,
    $entityType !== '' ? $entityType : null,
    $entityId > 0 ? $entityId : null
);

$APPLICATION->SetTitle('История документов УПД');

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_after.php';
?>

<form method="get" action="">
    <input type="hidden" name="lang" value="<?= LANGUAGE_ID ?>">
    Тип: <input type="text" name="entity_type" value="<?= htmlspecialcharsbx($entityType) ?>" size="12" placeholder="deal">
    ID: <input type="text" name="entity_id" value="<?= $entityId > 0 ? $entityId : '' ?>" size="8">
    <input type="submit" value="Фильтр" class="adm-btn">
    <a href="/bitrix/admin/settings.php?mid=vendor.xmldoc&lang=<?= LANGUAGE_ID ?>">Настройки модуля</a>
</form>
<br>

<table class="adm-list-table">
    <thead>
    <tr class="adm-list-table-header">
        <td class="adm-list-table-cell">ID</td>
        <td class="adm-list-table-cell">Тип</td>
        <td class="adm-list-table-cell">Сущность</td>
        <td class="adm-list-table-cell">Номер</td>
        <td class="adm-list-table-cell">Файл</td>
        <td class="adm-list-table-cell">Версия</td>
        <td class="adm-list-table-cell">Статус</td>
        <td class="adm-list-table-cell">Кодировка</td>
        <td class="adm-list-table-cell">Дата</td>
    </tr>
    </thead>
    <tbody>
    <?php if ($rows === []): ?>
        <tr><td colspan="9">Записей нет</td></tr>
    <?php else: ?>
        <?php foreach ($rows as $row): ?>
            <?php
            $fileUrl = !empty($row['FILE_ID']) ? (string)\CFile::GetPath((int)$row['FILE_ID']) : '';
            ?>
            <tr>
                <td><?= (int)$row['ID'] ?></td>
                <td><?= htmlspecialcharsbx((string)$row['ENTITY_TYPE']) ?></td>
                <td><?= (int)$row['ENTITY_ID'] ?></td>
                <td><?= htmlspecialcharsbx((string)($row['DOC_NUMBER'] ?? '')) ?></td>
                <td>
                    <?php if ($fileUrl !== ''): ?>
                        <a href="<?= htmlspecialcharsbx($fileUrl) ?>" target="_blank"><?= htmlspecialcharsbx((string)$row['FILE_NAME']) ?></a>
                    <?php else: ?>
                        <?= htmlspecialcharsbx((string)$row['FILE_NAME']) ?>
                    <?php endif; ?>
                </td>
                <td><?= (int)$row['VERSION'] ?></td>
                <td><?= htmlspecialcharsbx((string)($row['DOC_STATUS'] ?? '')) ?></td>
                <td><?= htmlspecialcharsbx((string)($row['ENCODING'] ?? '')) ?></td>
                <td><?= htmlspecialcharsbx((string)($row['CREATED_AT'] ?? '')) ?></td>
            </tr>
        <?php endforeach; ?>
    <?php endif; ?>
    </tbody>
</table>

<?php
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php';
