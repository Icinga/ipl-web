/* Icinga Web 2 | (c) 2025 Icinga GmbH | GPLv2+ */

define(["../widget/RelativeTime", "icinga/legacy-app/Icinga"], function (RelativeTime, Icinga) {

    "use strict";

    class RelativeTimeBehavior extends Icinga.EventListener {
        constructor(icinga) {
            super(icinga);

            this.on('submit', '[name=form_config_preferences]', this.onPreferencesSubmit, this);
            // console.log(document.querySelectorAll('time[data-relative-time]'));

            /**
             * RelativeTime instance
             *
             * @type {RelativeTime}
             * @private
             */
            this._relativeTime = new RelativeTime(icinga.config.timezone, icinga.config.locale);

            /**
             * Timer handle
             *
             * @type {null|*}
             * @private
             */
            this._timerHandle = null;

            /**
             * Current timezone name
             *
             * @type {string|null}
             * @private
             */
            this._timezoneName = icinga.config.timezone || null;

            /**
             * Current timezone offset in minutes
             *
             * @type {number|null}
             * @private
             */
            this._timezoneOffsetMinutes = null;

            /**
             * Timestamp when the next timezone check should run
             *
             * @type {number|null}
             * @private
             */
            this._timezoneNextCheckAt = null;

            this._relativeTime.update(document);
            this._maybeRefreshTimezone();

            if (this._timerHandle == null) {
                this._timerHandle = icinga.timer.register(
                    () => {this._onTimerTick(document); },
                    this,
                    1000
                );
            }
        }

        _onTimerTick(root = document) {
            this._relativeTime.update(root);
            this._maybeRefreshTimezone();
        }

        onPreferencesSubmit(event)
        {
            const timezoneElement = event.target.querySelector('[name=timezone]');
            let timezone = timezoneElement.value;
            if (timezone === 'autodetect') {
                timezone = timezoneElement.querySelector('[value=autodetect]').innerHTML.match(/\((.*)\)/)[1];
            }

            icinga.config.timezone = timezone;
            this._relativeTime.setTimezone(timezone);
            this._relativeTime.update(document);
            this._timezoneName = timezone;
            this._timezoneOffsetMinutes = null;
            this._timezoneNextCheckAt = null;
            this._maybeRefreshTimezone();
        }

        /**
         * Handle rendered event
         *
         * @param event
         */
        onRendered(event) {
            let _this = event.data.self;
            const root = event.currentTarget || event.target;
            const element = root && root.nodeType === 1 ? root : null; // 1 = Element
            const hasRelativeTime =
                element
                && (element.matches('time[data-relative-time]') || element.querySelector('time[data-relative-time]'));

            if (!hasRelativeTime) {
                return;
            }

            _this._relativeTime.update(root);

            if (_this._timerHandle == null) {
                _this._timerHandle = _this.icinga.timer.register(
                    () => {_this._onTimerTick(root); },
                    _this,
                    1000
                );
            }
        }

        /**
         * Stop the timer
         *
         * @param event
         */
        stop(event) {
            const _this = event?.data?.self || this;

            if (_this._timerHandle == null) {
                return;
            }

            const timer = _this.icinga.timer;

            if (typeof timer.unregister === 'function') {
                try {
                    timer.unregister(_this._timerHandle);
                } catch (e) {
                    // ignore
                }
            } else if (typeof timer.remove === 'function') {
                try {
                    timer.remove(_this._timerHandle);
                } catch (e) {
                    // ignore
                }
            } else {
                // Best effort fallback for older timer APIs
                try {
                    timer.unregister(function() { _this._relativeTime.update(); }, _this);
                } catch (e) {
                    // ignore
                }
            }

            _this._timerHandle = null;
        }

        _maybeRefreshTimezone() {
            const timezone = this.icinga?.config?.timezone;
            if (!timezone) {
                return;
            }

            if (this._timezoneName !== timezone) {
                this._timezoneName = timezone;
                this._relativeTime.setTimezone(timezone);
                this._timezoneOffsetMinutes = null;
                this._timezoneNextCheckAt = null;
            }

            const now = Date.now();
            if (this._timezoneNextCheckAt != null && now < this._timezoneNextCheckAt) {
                return;
            }

            const offsetMinutes = this._getTimezoneOffsetMinutes(timezone, new Date(now));
            if (this._timezoneOffsetMinutes == null) {
                this._timezoneOffsetMinutes = offsetMinutes;
            } else if (offsetMinutes !== this._timezoneOffsetMinutes) {
                this._timezoneOffsetMinutes = offsetMinutes;
                this._relativeTime.setTimezone(timezone);
                this._relativeTime.update(document);
            }

            this._timezoneNextCheckAt = this._getNextTimezoneCheckAt(timezone, new Date(now), offsetMinutes);
        }

        _getTimezoneOffsetMinutes(timezone, date) {
            const formatter = new Intl.DateTimeFormat('en-US', {
                timeZone: timezone,
                year: 'numeric',
                month: '2-digit',
                day: '2-digit',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
                hour12: false,
                timeZoneName: 'longOffset'
            });

            const parts = formatter.formatToParts(date);
            const offsetPart = parts.find(part => part.type === 'timeZoneName');
            const offsetString = offsetPart ? offsetPart.value : 'GMT';
            const match = offsetString.match(/GMT(?:(\+|-)(\d{1,2})(?::?(\d{2}))?)?/);

            if (!match || !match[1]) {
                return 0;
            }

            const sign = match[1] === '-' ? -1 : 1;
            const hours = parseInt(match[2], 10) || 0;
            const minutes = match[3] ? parseInt(match[3], 10) : 0;

            return sign * (hours * 60 + minutes);
        }

        _getNextTimezoneCheckAt(timezone, fromDate, currentOffsetMinutes) {
            const dayMs = 24 * 60 * 60 * 1000;
            const minuteMs = 60 * 1000;
            const maxDaysToScan = 370;
            const baseOffset = typeof currentOffsetMinutes === 'number'
                ? currentOffsetMinutes
                : this._getTimezoneOffsetMinutes(timezone, fromDate);

            let low = fromDate;
            let high = null;

            for (let i = 1; i <= maxDaysToScan; i++) {
                const probe = new Date(fromDate.getTime() + i * dayMs);
                const probeOffset = this._getTimezoneOffsetMinutes(timezone, probe);

                if (probeOffset !== baseOffset) {
                    low = new Date(fromDate.getTime() + (i - 1) * dayMs);
                    high = probe;
                    break;
                }
            }

            if (high == null) {
                return fromDate.getTime() + 30 * dayMs;
            }

            let lo = low.getTime();
            let hi = high.getTime();

            while (hi - lo > minuteMs) {
                const mid = Math.floor((lo + hi) / 2);
                const midOffset = this._getTimezoneOffsetMinutes(timezone, new Date(mid));

                if (midOffset === baseOffset) {
                    lo = mid;
                } else {
                    hi = mid;
                }
            }

            return hi + minuteMs;
        }
    }

    Icinga.Behaviors = Icinga.Behaviors || {};

    Icinga.Behaviors.RelativeTime = RelativeTimeBehavior;
});
