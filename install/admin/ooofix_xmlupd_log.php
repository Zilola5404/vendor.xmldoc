<?php

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_before.php';

use Bitrix\Main\Loader;
use Ooofix\Xmlupd\Admin\ModuleTableHealth;
use Ooofix\Xmlupd\Logger;

$moduleId = 'ooofix.xmlupd';

if ($APPLICATION->GetGroupRight($moduleId) < 'R') {
    $APPLICATION->AuthForm('Доступ запрещён');
}

Loader::includeModule($moduleId);

$entityType = (string)($_GET['entity_type'] ?? '');
$entityId = (int)($_GET['entity_id'] ?? 0);
$limit = (int)($_GET['limit'] ?? 100);

$rows = [];
if (ModuleTableHealth::isLogTableReady()) {
    $rows = Logger::fetchList(
        $limit,
        $entityType !== '' ? $entityType : null,
        $entityId > 0 ? $entityId : null
    );
}

$APPLICATION->SetTitle('Журнал генерации УПД');

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_after.php';
?>

<form method="get" action="">
    <input type="hidden" name="lang" value="<?= LANGUAGE_ID ?>">
    Тип: <input type="text" name="entity_type" value="<?= htmlspecialcharsbx($entityType) ?>" size="12" placeholder="deal">
    ID: <input type="text" name="entity_id" value="<?= $entityId > 0 ? $entityId : '' ?>" size="8">
    <input type="submit" value="Фильтр" class="adm-btn">
    <a href="/bitrix/admin/ooofix_xmlupd_documents.php?lang=<?= LANGUAGE_ID ?>">История документов</a>
</form>
<br>

<?php if (!ModuleTableHealth::isLogTableReady()): ?>
    <div class="adm-info-message-wrap adm-info-message-red">
        <span class="adm-info-message-text">Таблица b_xmldoc_log не найдена. Переустановите модуль или выполните обновление до 1.4.0.</span>
    </div>
    <br>
<?php endif; ?>

<table class="adm-list-table">
    <thead>
    <tr class="adm-list-table-header">
        <td class="adm-list-table-cell">ID</td>
        <td class="adm-list-table-cell">Тип</td>
        <td class="adm-list-table-cell">Сущность</td>
        <td class="adm-list-table-cell">Статус</td>
        <td class="adm-list-table-cell">Сообщение</td>
        <td class="adm-list-table-cell">Дата</td>
    </tr>
    </thead>
    <tbody>
    <?php if ($rows === []): ?>
        <tr><td colspan="6">Записей нет</td></tr>
    <?php else: ?>
        <?php foreach ($rows as $row): ?>
            <tr>
                <td><?= (int)$row['ID'] ?></td>
                <td><?= htmlspecialcharsbx((string)$row['ENTITY_TYPE']) ?></td>
                <td><?= (int)$row['ENTITY_ID'] ?></td>
                <td><?= htmlspecialcharsbx((string)$row['STATUS']) ?></td>
                <td><?= nl2br(htmlspecialcharsbx((string)($row['MESSAGE'] ?? ''))) ?></td>
                <td><?= htmlspecialcharsbx((string)($row['CREATED_AT'] ?? '')) ?></td>
            </tr>
        <?php endforeach; ?>
    <?php endif; ?>
    </tbody>
</table>

<?php
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php';
