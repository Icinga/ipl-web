/* Icinga Web 2 | (c) 2025 Icinga GmbH | GPLv2+ */

(function (root, factory) {

    'use strict';

    // Libraries are concatenated before Icinga Web's core JS. Therefore `root.Icinga` is not available yet.
    // We lazily initialize once `Icinga` and `Icinga.EventListener` exist.
    let initialized = false;

    const tryInit = () => {
        if (initialized) {
            return true;
        }

        if (! (root && root.Icinga && root.Icinga.EventListener)) {
            return false;
        }

        initialized = true;
        factory(root.Icinga, root.Icinga.EventListener);
        return true;
    };

    if (! tryInit() && root && typeof root.setTimeout === 'function') {
        let tries = 0;
        const maxTries = 400; // ~10s at 25ms
        const delayMs = 25;

        const tick = () => {
            if (tryInit()) {
                return;
            }

            if (++tries >= maxTries) {
                return;
            }

            root.setTimeout(tick, delayMs);
        };

        root.setTimeout(tick, 0);
    }

    // Not running inside Icinga Web: no-op
})(typeof window !== 'undefined' ? window : this, function (Icinga, EventListener) {

    'use strict';

    class RelativeTime extends EventListener {
        constructor(icinga) {
            super(icinga);

            this.formatter = new Intl.RelativeTimeFormat(
                [icinga.config.locale, 'en'],
                {style: 'narrow'}
            );

            this._timerHandle = null;

            this.on('rendered', '#main > .container, #modal-content', this.onRendered, this);
            this.on('close-column', this.stop, this);
            this.on('close-modal', this.stop, this);
        }

        onRendered(event) {
            let _this = event.data.self;
            const root = event.currentTarget || event.target;
            const hasRelativeTime =
                (root.matches && root.matches('time[data-relative-time]')) ||
                root.querySelector('time[data-relative-time]');

            if (!hasRelativeTime) {
                return;
            }

            // Always update once for the newly rendered root
            _this.update(root);

            // Register the global timer only once
            if (_this._timerHandle == null) {
                _this._timerHandle = _this.icinga.timer.register(_this.update, _this, 1000);
            }
        }

        stop(event) {
            const _this = event && event.data && event.data.self ? event.data.self : this;

            if (_this._timerHandle == null) {
                return;
            }

            const t = _this.icinga.timer;

            if (typeof t.unregister === 'function') {
                try {
                    t.unregister(_this._timerHandle);
                } catch (e) {
                    // ignore
                }
            } else if (typeof t.remove === 'function') {
                try {
                    t.remove(_this._timerHandle);
                } catch (e) {
                    // ignore
                }
            } else {
                // Best effort fallback for older timer APIs
                try {
                    t.unregister(_this.update, _this);
                } catch (e) {
                    // ignore
                }
            }

            _this._timerHandle = null;
        }

        update(root = document) {
            const now = Date.now();
            const ONE_HOUR_SEC = 60 * 60;

            const getDatetimeMs = (el) => {
                const dt = el.dateTime || el.getAttribute('datetime');
                if (!dt) {
                    return NaN;
                }

                return Date.parse(dt);
            };

            root.querySelectorAll('time[data-relative-time="ago"], time[data-relative-time="since"]')
                .forEach((el) => {
                    const mode = el.dataset.relativeTime;

                    const ts = getDatetimeMs(el);
                    if (!Number.isFinite(ts)) {
                        return;
                    }

                    let diffSec = Math.floor((now - ts) / 1000);
                    if (diffSec < 0) {
                        diffSec = 0;
                    }

                    if (diffSec >= ONE_HOUR_SEC) {
                        return;
                    }

                    const minute = Math.floor(diffSec / 60);
                    const second = diffSec % 60;

                    el.innerHTML = this.render(minute, second, mode);
                });

            root.querySelectorAll('time[data-relative-time="until"]')
                .forEach((el) => {
                    const ts = getDatetimeMs(el);
                    if (!Number.isFinite(ts)) {
                        return;
                    }

                    const remainingSec = Math.ceil((ts - now) / 1000);

                    if (Math.abs(remainingSec) >= ONE_HOUR_SEC) {
                        return;
                    }

                    if (remainingSec === 0 && el.dataset.agoLabel) {
                        el.innerText = el.dataset.agoLabel;
                        el.dataset.relativeTime = 'ago';

                        return;
                    }

                    let invert = '';
                    let absSec = remainingSec;

                    if (remainingSec < 0) {
                        invert = '-';
                        absSec = -remainingSec;
                    }

                    const minute = Math.floor(absSec / 60);
                    const second = absSec % 60;

                    el.innerHTML = this.render(minute, second, 'until', invert);
                });
        }

        render(minute, second, mode, invert = '') {
            const sign = mode === 'ago' || mode === 'since' ? -1 : 1;

            let min = minute * sign;
            let sec = second * sign;

            const minutes = this.formatter.formatToParts(min, 'minute');
            const seconds = this.formatter.formatToParts(sec, 'second');
            let isPrefix = true;
            let prefix = '', suffix = '';
            for (let i = 0; i < seconds.length; i++) {
                if (seconds[i].type === 'integer') {
                    if (i === 0) {
                        isPrefix = false;
                    }
                    continue;
                }

                if (seconds[i].value === minutes[i].value) {
                    if (isPrefix) {
                        prefix = seconds[i].value;
                    } else {
                        suffix = seconds[i].value;
                    }
                    break;
                }

                const a = String(seconds[i].value);
                const b = String(minutes[i].value);
                const maxLen = Math.min(a.length, b.length);

                // helper: longest common prefix
                const lcp = () => {
                    let common = '';
                    for (let k = 1; k <= maxLen; k++) {
                        const cand = a.slice(0, k);
                        if (b.startsWith(cand)) {
                            common = cand;
                        } else {
                            break;
                        }
                    }
                    return common;
                };

                // helper: longest common suffix
                const lcs = () => {
                    let common = '';
                    for (let k = 1; k <= maxLen; k++) {
                        const cand = a.slice(-k);
                        if (b.endsWith(cand)) {
                            common = cand;
                        } else {
                            break;
                        }
                    }
                    return common;
                };

                if (isPrefix) {
                    const common = lcp();
                    if (common && common.trim().length) {
                        prefix = common;
                    }
                } else {
                    const common = lcs();
                    if (common && common.trim().length) {
                        suffix = common;
                    }
                }
            }

            return prefix + minute + 'm ' + second + 's ' + suffix;
        }
    }

    Icinga.Behaviors = Icinga.Behaviors || {};
    Icinga.Behaviors.RelativeTime = RelativeTime;

});
