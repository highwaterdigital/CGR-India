(function () {
    function onReady(callback) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', callback);
        } else {
            callback();
        }
    }

    function getCookie(name) {
        var match = document.cookie.match(new RegExp('(?:^|; )' + name.replace(/([.$?*|{}()\\[\\]\\\\\\/\\+^])/g, '\\$1') + '=([^;]*)'));
        return match ? decodeURIComponent(match[1]) : null;
    }

    function setCookie(name, value, days) {
        var expires = '';
        if (days) {
            var date = new Date();
            date.setTime(date.getTime() + days * 864e5);
            expires = '; expires=' + date.toUTCString();
        }
        document.cookie = name + '=' + encodeURIComponent(value) + expires + '; path=/';
    }

    function getSessionFlag(key) {
        try {
            return sessionStorage.getItem(key) === '1';
        } catch (error) {
            return false;
        }
    }

    function setSessionFlag(key) {
        try {
            sessionStorage.setItem(key, '1');
        } catch (error) {
            return;
        }
    }

    function getLastDismissed(popupId) {
        var value = getCookie('cgr_popup_dismissed_' + popupId);
        return value ? parseInt(value, 10) : null;
    }

    function getNextScheduled(popup, lastDismissed, now) {
        if (window.cgrSmartPopupsHooks && typeof window.cgrSmartPopupsHooks.nextScheduled === 'function') {
            var override = window.cgrSmartPopupsHooks.nextScheduled(popup, lastDismissed, now);
            if (typeof override === 'number') {
                return override;
            }
        }

        if (popup.next_mode === 'interval' && popup.next_value && lastDismissed) {
            return lastDismissed + popup.next_value * 86400;
        }

        if (popup.next_mode === 'fixed' && popup.next_value) {
            return popup.next_value;
        }

        return null;
    }

    function isFrequencyBlocked(popup, lastDismissed, now) {
        var frequency = popup.frequency || 'always';
        var sessionKey = 'cgr_popup_session_' + popup.id;

        if (frequency === 'session') {
            return getSessionFlag(sessionKey);
        }

        if (!lastDismissed) {
            return false;
        }

        if (frequency === 'once') {
            return true;
        }

        var diff = now - lastDismissed;

        if (frequency === 'day') {
            return diff < 86400;
        }
        if (frequency === 'week') {
            return diff < 604800;
        }
        if (frequency === 'month') {
            return diff < 2592000;
        }

        return false;
    }

    function shouldShowPopup(popup, now) {
        if (!popup || popup.status === 'draft') {
            return false;
        }

        if (popup.start && now < popup.start) {
            return false;
        }

        if (popup.end && now > popup.end) {
            return false;
        }

        if ((popup.frequency || 'always') === 'always') {
            return true;
        }

        var lastDismissed = getLastDismissed(popup.id);
        if (isFrequencyBlocked(popup, lastDismissed, now)) {
            return false;
        }

        var nextScheduled = getNextScheduled(popup, lastDismissed, now);
        if (nextScheduled && now < nextScheduled) {
            return false;
        }

        return true;
    }

    onReady(function () {
        if (!window.cgrSmartPopups || !Array.isArray(window.cgrSmartPopups.popups)) {
            return;
        }

        var container = document.querySelector('[data-cgr-smart-popups]');
        if (!container) {
            return;
        }

        var data = window.cgrSmartPopups;
        var clientNow = Math.floor(Date.now() / 1000);
        var offset = typeof data.now === 'number' ? data.now - clientNow : 0;

        function getNow() {
            return Math.floor(Date.now() / 1000) + offset;
        }

        var popupMap = {};
        container.querySelectorAll('.cgr-smart-popup[data-cgr-popup-id]').forEach(function (popupEl) {
            popupMap[popupEl.getAttribute('data-cgr-popup-id')] = popupEl;
        });

        var queue = data.popups.filter(function (popup) {
            return popupMap[String(popup.id)] && shouldShowPopup(popup, getNow());
        }).sort(function (a, b) {
            return (a.priority || 0) - (b.priority || 0);
        });

        if (!queue.length) {
            return;
        }

        var activePopup = null;
        var lastFocus = null;

        function closePopup(popup, popupEl) {
            popupEl.classList.remove('is-open');
            popupEl.setAttribute('aria-hidden', 'true');
            document.body.classList.remove('cgr-smart-popup-open');

            var now = getNow();
            var dismissKey = 'cgr_popup_dismissed_' + popup.id;
            setCookie(dismissKey, String(now), 365);
            setSessionFlag('cgr_popup_session_' + popup.id);

            if (lastFocus && typeof lastFocus.focus === 'function') {
                lastFocus.focus();
            }

            document.dispatchEvent(new CustomEvent('cgr:popup:dismissed', { detail: popup }));

            activePopup = null;
            showNext();
        }

        function bindCloseHandlers(popup, popupEl) {
            if (popupEl.dataset.cgrPopupBound) {
                return;
            }
            popupEl.querySelectorAll('[data-cgr-popup-close]').forEach(function (closeEl) {
                closeEl.addEventListener('click', function () {
                    closePopup(popup, popupEl);
                });
            });
            popupEl.dataset.cgrPopupBound = '1';
        }

        function showPopup(popup) {
            var popupEl = popupMap[String(popup.id)];
            if (!popupEl) {
                return;
            }

            activePopup = popup;
            lastFocus = document.activeElement;

            popupEl.classList.add('is-open');
            popupEl.setAttribute('aria-hidden', 'false');
            document.body.classList.add('cgr-smart-popup-open');

            bindCloseHandlers(popup, popupEl);

            var closeBtn = popupEl.querySelector('.cgr-smart-popup__close');
            if (closeBtn) {
                closeBtn.focus();
            }

            document.dispatchEvent(new CustomEvent('cgr:popup:shown', { detail: popup }));
        }

        function showNext() {
            if (activePopup || !queue.length) {
                return;
            }

            var nextPopup = queue.shift();
            if (nextPopup && shouldShowPopup(nextPopup, getNow())) {
                showPopup(nextPopup);
            } else {
                showNext();
            }
        }

        document.addEventListener('keyup', function (event) {
            if (event.key === 'Escape' && activePopup) {
                var popupEl = popupMap[String(activePopup.id)];
                if (popupEl) {
                    closePopup(activePopup, popupEl);
                }
            }
        });

        showNext();
    });
})();
