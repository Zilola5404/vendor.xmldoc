# Миграция на ooofix.xmlupd 2.0.0

## Идентификаторы

| Параметр | Значение Marketplace |
|----------|----------------------|
| MODULE_ID | `ooofix.xmlupd` |
| Namespace | `Ooofix\Xmlupd` |
| Каталог | `/local/modules/ooofix.xmlupd/` |
| CRM-раздел | `/crm/ooofix_xmlupd/` |

## Автоматическая миграция (2.0.0)

При установке или обновлении до 2.0.0 модуль (`LegacyModuleMigration`):

1. Копирует настройки из `b_option` предыдущих MODULE_ID в `ooofix.xmlupd` (если ключ ещё не задан).
2. Удаляет устаревшие файлы (старые JS/CSS, activity `xmldocgenerateupd`, прежние CRM-разделы).
3. Снимает регистрацию устаревших MODULE_ID в `b_module`.

## База данных

Таблицы **`b_xmldoc_log`** и **`b_xmldoc_document`** **не переименовываются** — данные сохраняются.

Переименование в `b_ooofix_xmlupd_*` возможно вручную на крупных инсталляциях (только с резервной копией БД). После переименования потребуется правка `Logger.php` и `DocumentRegistry.php`.

## Бизнес-процессы и роботы

Activity: **`ooofixxmlupdgenerate`** (класс `CBPOoofixXmlupdGenerate`).

Старые шаблоны с `xmldocgenerateupd` замените в дизайнере БП/роботах.

Триггеры CRM: префикс `ooofix.xmlupd.*` (например `ooofix.xmlupd.upd.generated`).

## Ручная установка с заменой модуля

1. Резервная копия сайта и БД.
2. Удалите каталог предыдущей версии модуля в `/local/modules/`.
3. Разместите `/local/modules/ooofix.xmlupd/`.
4. Установите или обновите модуль в админке.
