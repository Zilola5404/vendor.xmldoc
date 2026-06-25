<?php

namespace Ooofix\Xmlupd\Admin;

use Ooofix\Xmlupd\Logger;
use Ooofix\Xmlupd\Portal\PortalRoutes;

/** Публичная страница «Логи». */
final class LogPageRenderer
{
    public static function render(): void
    {
        if (!ModuleTableHealth::isLogTableReady()) {
            echo '<div class="ui-alert ui-alert-danger"><span class="ui-alert-message">'
                . 'Таблица журнала не найдена. Переустановите модуль или обновите до последней версии.'
                . '</span></div>';
            return;
        }

        $entityType = (string)($_GET['entity_type'] ?? '');
        $entityId = (int)($_GET['entity_id'] ?? 0);
        $limit = max(1, min(200, (int)($_GET['limit'] ?? 50)));

        $rows = Logger::fetchList(
            $limit,
            $entityType !== '' ? $entityType : null,
            $entityId > 0 ? $entityId : null
        );

        $formAction = PortalRoutes::logs();
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
                    <th>Статус</th>
                    <th>Сообщение</th>
                    <th>Дата</th>
                </tr>
                </thead>
                <tbody>
                <?php if ($rows === []): ?>
                    <tr><td colspan="6" class="ox-xml-grid__empty">Записей нет</td></tr>
                <?php else: ?>
                    <?php foreach ($rows as $row): ?>
                        <tr>
                            <td><?= (int)$row['ID'] ?></td>
                            <td><?= htmlspecialcharsbx((string)$row['ENTITY_TYPE']) ?></td>
                            <td><?= (int)$row['ENTITY_ID'] ?></td>
                            <td><?= htmlspecialcharsbx((string)$row['STATUS']) ?></td>
                            <td><?= htmlspecialcharsbx((string)$row['MESSAGE']) ?></td>
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
