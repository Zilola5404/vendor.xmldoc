<?php

namespace Ooofix\Xmlupd\Admin;

use Ooofix\Xmlupd\DocumentRegistry;
use Ooofix\Xmlupd\Portal\PortalRoutes;

/** Публичная страница «Документы». */
final class DocumentsPageRenderer
{
    public static function render(): void
    {
        if (!ModuleTableHealth::isDocumentTableReady()) {
            echo '<div class="ui-alert ui-alert-danger"><span class="ui-alert-message">'
                . 'Таблица реестра документов не найдена. Переустановите модуль или обновите до последней версии.'
                . '</span></div>';
            return;
        }

        $entityType = (string)($_GET['entity_type'] ?? '');
        $entityId = (int)($_GET['entity_id'] ?? 0);
        $limit = max(1, min(200, (int)($_GET['limit'] ?? 50)));

        $rows = DocumentRegistry::fetchList(
            $limit,
            $entityType !== '' ? $entityType : null,
            $entityId > 0 ? $entityId : null
        );

        $formAction = PortalRoutes::documents();
        ?>
        <form method="get" action="<?= htmlspecialcharsbx($formAction) ?>" class="ox-xml-grid-filter">
            <div class="ox-xml-grid-filter__row">
                <label class="ui-ctl ui-ctl-textbox ui-ctl-xs">
                    <input class="ui-ctl-element" type="text" name="entity_type" placeholder="Тип (deal)"
                        value="<?= htmlspecialcharsbx($entityType) ?>">
                </label>
                <label class="ui-ctl ui-ctl-textbox ui-ctl-xs">
                    <input class="ui-ctl-element" type="number" name="entity_id" placeholder="ID сущности"
                        value="<?= $entityId > 0 ? $entityId : '' ?>">
                </label>
                <button type="submit" class="ui-btn ui-btn-light-border ui-btn-xs">Фильтр</button>
            </div>
        </form>

        <div class="ox-xml-grid-wrap">
            <table class="ox-xml-grid">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Тип</th>
                    <th>Сущность</th>
                    <th>Номер</th>
                    <th>Файл</th>
                    <th>Версия</th>
                    <th>Статус</th>
                    <th>Дата</th>
                </tr>
                </thead>
                <tbody>
                <?php if ($rows === []): ?>
                    <tr><td colspan="8" class="ox-xml-grid__empty">Записей нет</td></tr>
                <?php else: ?>
                    <?php foreach ($rows as $row): ?>
                        <?php $fileUrl = !empty($row['FILE_ID']) ? (string)\CFile::GetPath((int)$row['FILE_ID']) : ''; ?>
                        <tr>
                            <td><?= (int)$row['ID'] ?></td>
                            <td><?= htmlspecialcharsbx((string)$row['ENTITY_TYPE']) ?></td>
                            <td><?= (int)$row['ENTITY_ID'] ?></td>
                            <td><?= htmlspecialcharsbx((string)($row['DOC_NUMBER'] ?? '')) ?></td>
                            <td>
                                <?php if ($fileUrl !== ''): ?>
                                    <a href="<?= htmlspecialcharsbx($fileUrl) ?>" target="_blank" rel="noopener">
                                        <?= htmlspecialcharsbx((string)$row['FILE_NAME']) ?>
                                    </a>
                                <?php else: ?>
                                    <?= htmlspecialcharsbx((string)$row['FILE_NAME']) ?>
                                <?php endif; ?>
                            </td>
                            <td><?= (int)$row['VERSION'] ?></td>
                            <td><?= htmlspecialcharsbx((string)($row['DOC_STATUS'] ?? '')) ?></td>
                            <td><?= htmlspecialcharsbx((string)($row['CREATED_AT'] ?? '')) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
}
