(function () {
    function isDebugEnabled(payload) {
        return !!(payload && payload.debugEnabled);
    }

    function logDebug(payload, level, message, data) {
        if (!isDebugEnabled(payload)) {
            return;
        }

        var logger = console;
        if (!logger || typeof logger[level] !== 'function') {
            return;
        }

        if (typeof data !== 'undefined') {
            logger[level]('[CGR Popups] ' + message, data);
        } else {
            logger[level]('[CGR Popups] ' + message);
        }
    }

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

    function getPopupDecision(popup, now) {
        var reasons = [];
        var details = {
            now: now
        };

        if (!popup) {
            reasons.push({ code: 'missing_payload' });
            return { show: false, reasons: reasons, details: details };
        }

        if (popup.status === 'draft') {
            reasons.push({ code: 'status_draft' });
        }

        if (popup.start && now < popup.start) {
            reasons.push({ code: 'start_in_future', start: popup.start });
        }

        if (popup.end && now > popup.end) {
            reasons.push({ code: 'end_in_past', end: popup.end });
        }

        var frequency = popup.frequency || 'always';
        details.frequency = frequency;

        var lastDismissed = getLastDismissed(popup.id);
        details.lastDismissed = lastDismissed;

        if (frequency !== 'always') {
            if (isFrequencyBlocked(popup, lastDismissed, now)) {
                reasons.push({ code: 'frequency_blocked' });
            }

            var nextScheduled = getNextScheduled(popup, lastDismissed, now);
            details.nextScheduled = nextScheduled;
            if (nextScheduled && now < nextScheduled) {
                reasons.push({ code: 'next_scheduled_in_future', nextScheduled: nextScheduled });
            }
        }

        return { show: reasons.length === 0, reasons: reasons, details: details };
    }

    function shouldShowPopup(popup, now) {
        return getPopupDecision(popup, now).show;
    }

    onReady(function () {
        if (!window.cgrSmartPopups || !Array.isArray(window.cgrSmartPopups.popups)) {
            logDebug(window.cgrSmartPopups, 'warn', 'Payload missing or invalid.', window.cgrSmartPopups);
            return;
        }

        var container = document.querySelector('[data-cgr-smart-popups]');
        if (!container) {
            logDebug(window.cgrSmartPopups, 'warn', 'Popup container not found in DOM.');
            return;
        }

        var data = window.cgrSmartPopups;
        var clientNow = Math.floor(Date.now() / 1000);
        var offset = typeof data.now === 'number' ? data.now - clientNow : 0;
        logDebug(data, 'info', 'Init', {
            clientNow: clientNow,
            serverNow: data.now,
            offset: offset,
            payloadCount: Array.isArray(data.popups) ? data.popups.length : 0,
            debug: data.debug || null
        });

        function getNow() {
            return Math.floor(Date.now() / 1000) + offset;
        }

        var popupMap = {};
        container.querySelectorAll('.cgr-smart-popup[data-cgr-popup-id]').forEach(function (popupEl) {
            popupMap[popupEl.getAttribute('data-cgr-popup-id')] = popupEl;
        });
        logDebug(data, 'info', 'Markup map', { domCount: Object.keys(popupMap).length });

        var decisions = {};
        var queue = data.popups.filter(function (popup) {
            var decision = getPopupDecision(popup, getNow());
            decisions[String(popup.id)] = decision;

            if (!popupMap[String(popup.id)]) {
                decision.reasons.push({ code: 'missing_markup' });
            }

            return popupMap[String(popup.id)] && decision.show;
        }).sort(function (a, b) {
            return (a.priority || 0) - (b.priority || 0);
        });

        if (!queue.length) {
            if (isDebugEnabled(data)) {
                var decisionRows = data.popups.map(function (popup) {
                    var decision = decisions[String(popup.id)] || getPopupDecision(popup, getNow());
                    return {
                        id: popup.id,
                        status: popup.status,
                        show: decision.show,
                        reasons: decision.reasons.map(function (reason) { return reason.code; }).join(', '),
                        start: popup.start || null,
                        end: popup.end || null,
                        frequency: popup.frequency || 'always',
                        next_mode: popup.next_mode || 'none',
                        next_value: popup.next_value || null
                    };
                });

                if (console && typeof console.table === 'function') {
                    console.table(decisionRows);
                } else {
                    logDebug(data, 'info', 'Popup decisions', decisionRows);
                }
            }

            logDebug(data, 'warn', 'No eligible popups after filtering.');
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
                logDebug(data, 'warn', 'Popup markup missing for payload.', popup);
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
            logDebug(data, 'info', 'Popup shown', popup);
        }

        function showNext() {
            if (activePopup || !queue.length) {
                return;
            }

            var nextPopup = queue.shift();
            if (nextPopup && shouldShowPopup(nextPopup, getNow())) {
                showPopup(nextPopup);
            } else {
                logDebug(data, 'info', 'Skipping popup (no longer eligible).', nextPopup);
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
