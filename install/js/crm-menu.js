(function (window, document) {
    'use strict';

    var MENU_ITEM_ID = 'OOOFIX_XMLUPD';
    var MENU_URL = '/crm/ooofix_xmlupd/settings/';
    var MENU_NAME = 'XML Документы';

    function getSiteDir() {
        if (window.BX && BX.message && BX.message('SITE_DIR')) {
            var dir = BX.message('SITE_DIR');
            return dir.charAt(dir.length - 1) === '/' ? dir : dir + '/';
        }
        return '/';
    }

    function getConfig() {
        return window.OX_UPD_CRM_MENU || {};
    }

    function menuExists() {
        return !!document.querySelector('[data-id="' + MENU_ITEM_ID + '"]')
            || !!document.querySelector('a[href*="/crm/ooofix_xmlupd/"]')
            || !!document.querySelector('a[href*="/crm/xml_documents/"]')
            || !!document.querySelector('a[href*="/xml_documents/"]');
    }

    function findMenuContainer() {
        var selectors = [
            '.main-buttons-inner-container',
            '.crm-control-panel-menu',
            '#crm_control_panel_menu',
            '.ui-nav-panel'
        ];

        for (var i = 0; i < selectors.length; i++) {
            var node = document.querySelector(selectors[i]);
            if (node) {
                return node;
            }
        }

        return null;
    }

    function injectMenuItem() {
        var config = getConfig();
        if (!config.enabled) {
            return;
        }

        if (menuExists()) {
            return;
        }

        var container = findMenuContainer();
        if (!container) {
            return;
        }

        var url = config.url || (getSiteDir() + MENU_URL.replace(/^\//, ''));
        var name = config.name || MENU_NAME;

        var link = document.createElement('a');
        link.setAttribute('data-id', MENU_ITEM_ID);
        link.className = 'main-buttons-item';
        link.href = url;
        link.title = name;
        link.innerHTML = '<span class="main-buttons-item-text">' + name + '</span>';

        container.appendChild(link);
    }

    function boot() {
        if (!/\/crm\//.test(window.location.pathname)) {
            return;
        }

        injectMenuItem();

        var attempts = 0;
        var timer = window.setInterval(function () {
            attempts += 1;
            injectMenuItem();
            if (menuExists() || attempts > 30) {
                window.clearInterval(timer);
            }
        }, 500);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
}(window, document));
