(function (window, document) {
    'use strict';

    var EXTENSIONS = ['ui.entity-selector'];

    function getConfig() {
        return window.OX_UPD_SETTINGS_CONFIG || {};
    }

    function notify(message, isError) {
        if (window.BX && BX.UI && BX.UI.Notification && BX.UI.Notification.Center) {
            BX.UI.Notification.Center.notify({
                content: message,
                autoHideDelay: isError ? 12000 : 4000,
            });
            return;
        }
        window.alert(message);
    }

    function updateDisplay(container, code, value, label) {
        var display = container.querySelector('[data-display-for="' + code + '"]');
        if (!display) {
            return;
        }
        var textNode = display.querySelector('.ox-upd-settings__crm-chip-text') || display;
        textNode.textContent = label || (value !== '' && value !== '0' ? value : '—');

        var clearBtn = container.querySelector('[data-crm-clear="' + code + '"]');
        if (clearBtn) {
            if (value !== '' && value !== '0') {
                clearBtn.removeAttribute('hidden');
            } else {
                clearBtn.setAttribute('hidden', 'hidden');
            }
        }
    }

    function hideEntitySelectorChrome() {
        [
            '.ui-selector-footer',
            '.ui-selector-dialog__footer',
            '.ui-entity-selector-footer',
            '.ui-selector-dialog-footer',
            '.ui-selector-dialog [data-role="footer"]',
            '.ui-selector-dialog__title',
            '.ui-entity-selector-header-title',
        ].forEach(function (selector) {
            document.querySelectorAll(selector).forEach(function (node) {
                node.style.display = 'none';
            });
        });
        document.querySelectorAll('.ui-selector-search').forEach(function (node) {
            node.style.display = 'none';
        });
    }

    var selectorObserverStarted = false;
    function watchEntitySelectorChrome() {
        if (selectorObserverStarted || !window.MutationObserver) {
            return;
        }
        selectorObserverStarted = true;
        var observer = new MutationObserver(function () {
            hideEntitySelectorChrome();
        });
        observer.observe(document.body, { childList: true, subtree: true });
    }

    function loadUiExtensions() {
        return new Promise(function (resolve) {
            if (!window.BX) {
                resolve();
                return;
            }
            if (BX.UI && BX.UI.EntitySelector && BX.UI.EntitySelector.Dialog) {
                resolve();
                return;
            }
            if (BX.Runtime && typeof BX.Runtime.loadExtension === 'function') {
                BX.Runtime.loadExtension(EXTENSIONS).then(resolve).catch(resolve);
                return;
            }
            resolve();
        });
    }

    function getItemTitle(item) {
        if (!item) {
            return '';
        }
        if (typeof item.getTitle === 'function') {
            var title = item.getTitle();
            if (title) {
                return String(title);
            }
        }
        return 'ID ' + item.getId();
    }

    function openEntityDialog(btn, onSelect) {
        if (!window.BX || !BX.UI || !BX.UI.EntitySelector || !BX.UI.EntitySelector.Dialog) {
            notify('Компонент выбора пользователя недоступен. Обновите страницу.', true);
            return;
        }

        var dialog = new BX.UI.EntitySelector.Dialog({
            targetNode: btn,
            enableSearch: false,
            multiple: false,
            dropdownMode: false,
            hideOnSelect: true,
            footer: null,
            context: 'OX_UPD_SETTINGS',
            entities: [{ id: 'user' }],
        });

        var handleSelect = function (event) {
            var item = event.getData().item;
            if (!item) {
                return;
            }
            onSelect(item);
            if (typeof dialog.hide === 'function') {
                dialog.hide();
            }
        };

        if (typeof dialog.subscribe === 'function') {
            dialog.subscribe('Item:onSelect', handleSelect);
            dialog.subscribe('onShow', hideEntitySelectorChrome);
            dialog.subscribe('onLoad', hideEntitySelectorChrome);
        }

        watchEntitySelectorChrome();
        dialog.show();
        hideEntitySelectorChrome();
        setTimeout(hideEntitySelectorChrome, 0);
        setTimeout(hideEntitySelectorChrome, 120);
    }

    function toggleSignatoryBlocks(root) {
        var mode = root.querySelector('[name="signatory_mode"]');
        var userBlock = root.querySelector('[data-signatory-user-block]');
        if (!mode || !userBlock) {
            return;
        }
        userBlock.style.display = mode.value === 'settings' ? '' : 'none';
    }

    function bindSettings(root) {
        if (root.getAttribute('data-settings-bound') === 'Y') {
            return;
        }
        root.setAttribute('data-settings-bound', 'Y');

        root.addEventListener('click', function (event) {
            var selectUser = event.target.closest('[data-crm-select]');
            if (selectUser && root.contains(selectUser)) {
                event.preventDefault();
                var code = selectUser.getAttribute('data-crm-select');
                var input = root.querySelector('[name="' + code + '"]');
                loadUiExtensions().then(function () {
                    openEntityDialog(selectUser, function (item) {
                        input.value = String(item.getId());
                        updateDisplay(root, code, input.value, getItemTitle(item) + ' [' + item.getId() + ']');
                    });
                });
                return;
            }

            var clearBtn = event.target.closest('[data-crm-clear]');
            if (clearBtn && root.contains(clearBtn)) {
                event.preventDefault();
                var clearCode = clearBtn.getAttribute('data-crm-clear');
                var clearInput = root.querySelector('[name="' + clearCode + '"]');
                if (clearInput) {
                    clearInput.value = '';
                    updateDisplay(root, clearCode, '', '—');
                }
            }
        });

        var mode = root.querySelector('[name="signatory_mode"]');
        if (mode) {
            mode.addEventListener('change', function () {
                toggleSignatoryBlocks(root);
            });
            toggleSignatoryBlocks(root);
        }
    }

    function boot() {
        var root = document.getElementById('ox-upd-settings');
        if (!root) {
            return;
        }
        var start = function () {
            bindSettings(root);
            loadUiExtensions();
            watchEntitySelectorChrome();
        };
        if (window.BX && typeof BX.ready === 'function') {
            BX.ready(start);
        } else if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', start);
        } else {
            start();
        }
    }

    window.OX_UPD_SETTINGS_BOOT = boot;
}(window, document));
