# Аудит облачного Bitrix24 — «Генерация XML (УПД)» (ooofix.vendor.xml)

**Версия модуля:** 2.2.9  
**Дата:** 2026-06-17  
**Цель:** функциональный паритет с коробочной установкой (`BoxGenerateRuntime`).

---

## Сводка

| Компонент | Коробка | Облако | Статус |
|-----------|---------|--------|--------|
| Runtime генерации | `BoxGenerateRuntime` | `CloudGenerateRuntime` | ✅ |
| Сбор данных CRM | `DataCollector` (legacy API) | `CloudDataCollector` (Factory API) | ✅ |
| Запись UF_UPD_FILE | `CrmEntityWriter` | `CloudCrmEntityWriter` + `saveFilePersistently` | ✅ |
| Права CRM (сделка) | `CCrmDeal::CheckUpdatePermission` | `Container::getUserPermissions()` | ✅ v2.2.8 |
| Права CRM (СП) | Service API | Service API | ✅ |
| СП «Счета» entityTypeId | опция `31` | авто + runtime-валидация | ✅ v2.2.8 |
| Режимы расчёта 1C / BITRIX24 | ✅ | ✅ (общий pipeline) | ✅ |
| Кнопка в карточке CRM | ✅ | ✅ | ✅ |
| Activity / робот CRM | ✅ | ✅ | ✅ |
| REST webhook (триггеры) | — | ✅ с логированием | ✅ v2.2.8 |
| DaData, адреса, XSD | ✅ | ✅ | ✅ |
| ЭДО (Diadoc/SBIS) | ❌ вне MVP | ❌ вне MVP | — |

---

## Архитектура облака

```
GenerateService
  └─ RuntimeFactory → CloudGenerateRuntime (если PortalEnvironment::isCloud())
       ├─ CloudDataCollector   — Factory: сделки, компании
       ├─ UpdBuilder           — общий с коробкой
       └─ FileSaver → CloudCrmEntityWriter
```

Определение облака: `PortalEnvironment` (`bitrix24` в домене, `BX24_HOST_NAME`, опция `crm_adapter=cloud`).

---

## Что реализовано в v2.2.8

| # | Пробел | Решение | Файл |
|---|--------|---------|------|
| 1 | `CloudDataCollector` — только `fetchDeal` | + `fetchBuyer` через Factory; общий `buildDealEntityFromRow` | `CloudDataCollector.php`, `DataCollector.php` |
| 2 | `fetchBuyer` / реквизиты — `private`, cloud не мог переопределить | `protected` для `fetchBuyer`, `fetchRequisite`, `fetchBankDetails` | `DataCollector.php` |
| 3 | Права сделок на облаке через legacy `CCrmDeal` | `CrmPermissions` → Factory API при `isCloud()` | `CrmPermissions.php` |
| 4 | `smart_invoice_type_id` фиксирован; на облаке ID ≠ 31 | `ModuleConfig::smartInvoiceTypeId()` → `resolveActiveTypeId`; кнопка «Определить СП» | `ModuleConfig.php`, `options.php` |
| 5 | REST webhook — тихие ошибки, один метод триггера | Лог в `b_xmldoc_log`; `trigger.execute` + `trigger` | `RestWebhookClient.php` |
| 6 | Кеш `DocumentTypeRegistry` после смены SP id | `resetCache()` при сохранении / детекте | `DocumentTypeRegistry.php` |

---

## Общий pipeline (идентичен коробке)

1. `CrmPermissions::canGenerate`
2. `DataCollector::collect` / `CloudDataCollector::collect`
3. `ValidationMessages::preValidate`
4. `UpdBuilder::process` → `ProductAmountCalculator` (режим `calculation_mode`)
5. `XmlValidator` (XSD при наличии пути)
6. `FileSaver` → UF + реестр `b_xmldoc_document`
7. `TriggerService::fireUpdGenerated` → `CloudCrmAdapter` / webhook

---

## Настройка на облачном портале

1. Установить модуль в `/local/modules/ooofix.vendor.xml`
2. **Marketplace → Обновить** до **2.2.9**
3. Настройки модуля:
   - Нажать **«Определить СП «Счета»»** (если работаете со смарт-счетами)
   - Нажать **«Создать поля УПД»**
   - При необходимости указать **REST webhook** для роботов
   - Выбрать **режим расчёта** (1C / BITRIX24)
4. Очистить кеш
5. Проверить: кнопка «Сформировать УПД» в сделке / СП

---

## Чек-лист E2E (облако)

- [ ] Сделка с компанией, реквизитами, товарами → кнопка → `success: true`
- [ ] Файл в `UF_UPD_FILE`, кодировка windows-1251
- [ ] Итоги шапки = `OPPORTUNITY` / `TAX_VALUE` (режим 1C)
- [ ] Юр. адрес покупателя в XML
- [ ] Смарт-счёт: корректный `entityTypeId`, UF записывается
- [ ] Пользователь без прав — отказ с понятным сообщением
- [ ] Робот CRM «Сформировать УПД (XML)»
- [ ] Журнал и история в админке
- [ ] XSD без критичных ошибок (при указанном пути)

---

## Ограничения (не блокируют MVP)

| Пункт | Примечание |
|-------|------------|
| Marketplace REST-приложение | Модуль — D7 local module, не отдельное app |
| Полная XSD ФНС 5.03 | Нужен файл схемы от заказчика |
| ЭДО Diadoc / SBIS | Этап 2+, `NullEdoGateway` |
| Очередь / потоковая запись XML | Не в scope |

---

## Отличия облака от коробки (технические)

| Аспект | Коробка | Облако |
|--------|---------|--------|
| Чтение сделки | `CCrmDeal::GetByID` | `Factory::getItem` |
| Чтение компании | `CCrmCompany::GetByID` | `Factory::getItem` |
| Запись файла в UF | `CrmEntityWriter` | `CloudCrmEntityWriter` + persistent upload |
| Автоматизация | Factory trigger | Factory → REST Execute → webhook |
| SP id | 31 по умолчанию | Автодетект при install + runtime |

Функциональный результат генерации УПД — **одинаковый**.
