(function (window, document) {
    'use strict';

    var SCRIPT_VERSION = '1.4.0';

    var TOOLBAR_SELECTORS = [
        '.ui-toolbar-right-container',
        '.ui-toolbar-right-buttons',
        '.ui-toolbar-right',
        '#uiToolbarContainer .ui-toolbar-right',
        '.pagetitle-container .ui-btn-container',
        '.pagetitle-wrap .ui-btn-container',
        '#pagetitle-menu',
        '.crm-entity-widget-content-block-actions',
        '.crm-entity-widget-content-block .ui-btn-container',
        '.main-buttons-inner-container',
    ];

    var OBSERVE_MS = 15000;
    var RETRY_INTERVAL_MS = 500;

    function getSiteDir() {
        if (window.BX && BX.message && BX.message('SITE_DIR')) {
            var dir = BX.message('SITE_DIR');
            return dir.charAt(dir.length - 1) === '/' ? dir : dir + '/';
        }
        return '/';
    }

    function getDefaultAjaxUrl() {
        return getSiteDir() + 'local/modules/ooofix.xmlupd/ajax/generate.php';
    }

    function getSessid() {
        if (window.OOOFIX_XMLUPD_CONFIG && window.OOOFIX_XMLUPD_CONFIG.sessid) {
            return window.OOOFIX_XMLUPD_CONFIG.sessid;
        }
        if (window.BX && typeof BX.bitrix_sessid === 'function') {
            return BX.bitrix_sessid();
        }
        return '';
    }

    function parseEntityFromUrl() {
        var path = window.location.pathname || '';
        var search = window.location.search || '';
        var full = path + search;

        var dealMatch = full.match(/\/crm\/deal\/details\/(\d+)/);
        if (dealMatch && parseInt(dealMatch[1], 10) > 0) {
            return { entityType: 'deal', entityId: parseInt(dealMatch[1], 10) };
        }

        var smartTypeId = (window.OOOFIX_XMLUPD_CONFIG && window.OOOFIX_XMLUPD_CONFIG.smartTypeId) || 31;
        var smartRe = new RegExp('/crm/type/' + smartTypeId + '/details/(\\d+)');
        var smartMatch = full.match(smartRe);
        if (smartMatch && parseInt(smartMatch[1], 10) > 0) {
            return { entityType: 'smart_invoice', entityId: parseInt(smartMatch[1], 10) };
        }

        return null;
    }

    function parseEntityFromCrmEditor() {
        if (!window.BX || !BX.Crm || !BX.Crm.EntityDetailManager || !BX.Crm.EntityDetailManager.items) {
            return null;
        }

        var items = BX.Crm.EntityDetailManager.items;
        var key;
        for (key in items) {
            if (!Object.prototype.hasOwnProperty.call(items, key)) {
                continue;
            }
            var manager = items[key];
            if (!manager || typeof manager.getEntityId !== 'function') {
                continue;
            }
            var entityId = parseInt(manager.getEntityId(), 10);
            if (!entityId || entityId <= 0) {
                continue;
            }
            var entityTypeId = typeof manager.getEntityTypeId === 'function'
                ? parseInt(manager.getEntityTypeId(), 10)
                : 0;

            if (entityTypeId === 2) {
                return { entityType: 'deal', entityId: entityId };
            }

            var smartTypeId = (window.OOOFIX_XMLUPD_CONFIG && window.OOOFIX_XMLUPD_CONFIG.smartTypeId) || 0;
            if (smartTypeId > 0 && entityTypeId === smartTypeId) {
                return { entityType: 'smart_invoice', entityId: entityId };
            }
        }

        return null;
    }

    function resolveConfig() {
        var base = window.OOOFIX_XMLUPD_CONFIG || {};
        var parsed = parseEntityFromUrl() || parseEntityFromCrmEditor();

        if (!parsed && base.entityType && base.entityId > 0) {
            parsed = { entityType: base.entityType, entityId: base.entityId };
        }

        if (!parsed || !parsed.entityId) {
            return null;
        }

        return {
            entityType: parsed.entityType,
            entityId: parsed.entityId,
            ajaxUrl: base.ajaxUrl || getDefaultAjaxUrl(),
            sessid: base.sessid || getSessid(),
            smartTypeId: base.smartTypeId || 31,
            canGenerate: base.canGenerate !== false,
            canSettings: base.canSettings === true,
            settingsUrl: base.settingsUrl || '',
            settingsLabel: base.settingsLabel || 'Настройки УПД',
            generateLabel: base.generateLabel || 'Сформировать УПД',
        };
    }

    function openSettings(url) {
        if (!url) {
            return;
        }
        if (window.BX && BX.SidePanel && typeof BX.SidePanel.Instance.open === 'function') {
            BX.SidePanel.Instance.open(url, { width: 900, cacheable: false });
            return;
        }
        window.location.href = url.replace(/[&?]IFRAME=Y.*$/, '');
    }

    function createSettingsButton(cfg) {
        var label = cfg.settingsLabel || 'Настройки УПД';
        if (window.BX && typeof BX.create === 'function') {
            return BX.create('button', {
                props: {
                    id: 'ooofix-xmlupd-settings-btn',
                    className: 'ui-btn ui-btn-light-border ui-btn-sm ooofix-xmlupd-settings-toolbar',
                    type: 'button',
                    title: label,
                },
                text: label,
                events: {
                    click: function () {
                        openSettings(cfg.settingsUrl);
                    },
                },
            });
        }

        var btn = document.createElement('button');
        btn.id = 'ooofix-xmlupd-settings-btn';
        btn.type = 'button';
        btn.className = 'ui-btn ui-btn-light-border ui-btn-sm ooofix-xmlupd-settings-toolbar';
        btn.textContent = label;
        btn.title = label;
        btn.addEventListener('click', function () {
            openSettings(cfg.settingsUrl);
        });
        return btn;
    }

    function showMessage(text, isError, extraLines) {
        var body = text || 'Ошибка генерации';
        if (extraLines && extraLines.length > 1 && body.indexOf('•') === -1) {
            body = body + '\n• ' + extraLines.join('\n• ');
        }
        if (window.BX && BX.UI && BX.UI.Notification && BX.UI.Notification.Center) {
            BX.UI.Notification.Center.notify({
                content: body.replace(/\n/g, '<br>'),
                autoHideDelay: isError ? 15000 : 4000,
            });
            return;
        }
        window.alert(body);
    }

    function findToolbar() {
        var i;
        for (i = 0; i < TOOLBAR_SELECTORS.length; i++) {
            var node = document.querySelector(TOOLBAR_SELECTORS[i]);
            if (node) {
                return node;
            }
        }
        return null;
    }

    function removeLegacyFloatingButtons() {
        document.querySelectorAll('#ooofix-xmlupd-generate-btn-float, .ooofix-xmlupd-generate-floating').forEach(function (el) {
            el.remove();
        });
    }

    function createButton(cfg) {
        if (window.BX && typeof BX.create === 'function') {
            return BX.create('button', {
                props: {
                    id: 'ooofix-xmlupd-generate-btn',
                    className: 'ui-btn ui-btn-primary ui-btn-sm ooofix-xmlupd-generate-toolbar',
                    type: 'button',
                    title: cfg.generateLabel || 'Сформировать УПД',
                },
                text: cfg.generateLabel || 'Сформировать УПД',
                events: {
                    click: function () {
                        generate(cfg);
                    },
                },
            });
        }

        var btn = document.createElement('button');
        btn.id = 'ooofix-xmlupd-generate-btn';
        btn.type = 'button';
        btn.className = 'ui-btn ui-btn-primary ui-btn-sm ooofix-xmlupd-generate-toolbar';
        btn.textContent = cfg.generateLabel || 'Сформировать УПД';
        btn.title = cfg.generateLabel || 'Сформировать УПД';
        btn.addEventListener('click', function () {
            generate(cfg);
        });
        return btn;
    }

    function parseJsonResponse(raw) {
        if (raw && typeof raw === 'object') {
            return raw;
        }
        if (typeof raw !== 'string' || raw === '') {
            return null;
        }
        try {
            return JSON.parse(raw);
        } catch (e) {
            return null;
        }
    }

    function extractErrorText(error) {
        if (!error) {
            return '';
        }
        if (typeof error === 'string') {
            return error;
        }
        if (error.responseText) {
            return error.responseText;
        }
        if (error.data) {
            return typeof error.data === 'string' ? error.data : JSON.stringify(error.data);
        }
        return '';
    }

    function handleResponse(res, rawText) {
        var data = parseJsonResponse(res);
        if (!data) {
            var preview = (rawText || String(res || '')).replace(/\s+/g, ' ').trim().slice(0, 400);
            showMessage('Сервер вернул некорректный ответ. ' + (preview || 'Пустой ответ'), true);
            return;
        }

        if (data.success) {
            var msg = 'УПД сформирован';
            if (data.fileName) {
                msg += ': ' + data.fileName;
            }
            showMessage(msg, false);
            return;
        }

        showMessage(data.message || 'Ошибка генерации', true, data.errors || data.hints);
    }

    function generate(cfg) {
        if (!window.confirm('Сформировать УПД для текущей карточки?')) {
            return;
        }

        if (!window.BX || typeof BX.ajax !== 'function') {
            showMessage('Не загружен JS-фреймворк Bitrix (BX)', true);
            return;
        }

        if (typeof BX.showWait === 'function') {
            BX.showWait();
        }

        var done = function () {
            if (typeof BX.closeWait === 'function') {
                BX.closeWait();
            }
        };

        var url = cfg.ajaxUrl;

        BX.ajax({
            url: url,
            method: 'POST',
            dataType: 'text',
            preparePost: true,
            data: {
                sessid: cfg.sessid,
                entityType: cfg.entityType,
                entityId: cfg.entityId,
            },
            onsuccess: function (res) {
                done();
                handleResponse(res, res);
            },
            onfailure: function (error) {
                done();
                var text = extractErrorText(error);
                var parsed = parseJsonResponse(text);
                if (parsed) {
                    handleResponse(parsed, text);
                    return;
                }
                showMessage(
                    'Ошибка запроса генерации УПД. URL: ' + cfg.ajaxUrl
                    + (text ? ('. Ответ: ' + text.replace(/\s+/g, ' ').trim().slice(0, 200)) : ''),
                    true
                );
            },
        });
    }

    function injectButton(cfg) {
        removeLegacyFloatingButtons();

        var hasGenerate = !!document.getElementById('ooofix-xmlupd-generate-btn');
        var hasSettings = !!document.getElementById('ooofix-xmlupd-settings-btn');
        if (hasGenerate && (!cfg.canSettings || hasSettings)) {
            return true;
        }

        var toolbar = findToolbar();
        if (!toolbar) {
            return false;
        }

        if (cfg.canSettings && cfg.settingsUrl && !hasSettings) {
            toolbar.insertBefore(createSettingsButton(cfg), toolbar.firstChild);
        }

        if (cfg.canGenerate !== false && !hasGenerate) {
            toolbar.insertBefore(createButton(cfg), toolbar.firstChild);
        }

        return true;
    }

    function start(cfg) {
        var startedAt = Date.now();
        var observer = null;

        function tick() {
            if (injectButton(cfg)) {
                if (observer) {
                    observer.disconnect();
                    observer = null;
                }
                return;
            }
            if (Date.now() - startedAt < OBSERVE_MS) {
                window.setTimeout(tick, RETRY_INTERVAL_MS);
            }
        }

        tick();

        if (window.MutationObserver && document.body) {
            observer = new MutationObserver(function () {
                if (injectButton(cfg) && observer) {
                    observer.disconnect();
                    observer = null;
                }
            });
            observer.observe(document.body, { childList: true, subtree: true });
            window.setTimeout(function () {
                if (observer) {
                    observer.disconnect();
                }
            }, OBSERVE_MS);
        }
    }

    function init() {
        removeLegacyFloatingButtons();

        var cfg = resolveConfig();
        if (!cfg || !cfg.entityId) {
            return;
        }

        window.OOOFIX_XMLUPD_CONFIG = cfg;
        window.OOOFIX_XMLUPD_JS_VERSION = SCRIPT_VERSION;

        if (window.BX && typeof BX.ready === 'function') {
            BX.ready(function () {
                start(cfg);
            });
            return;
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function () {
                start(cfg);
            });
            return;
        }

        start(cfg);
    }

    function waitForBxAndInit() {
        if (typeof window.BX !== 'undefined') {
            init();
            return;
        }
        window.setTimeout(waitForBxAndInit, 100);
    }

    waitForBxAndInit();
})(window, document);
