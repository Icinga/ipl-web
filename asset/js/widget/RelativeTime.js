define([], function () {

    "use strict";

    const TIME_REGEX_FULL = /^(.*?)(\d+)([^\d\s]+)\s+(\d+)([^\d\s]+)(.*?)$/;
    const TIME_REGEX_MIN_ONLY = /^(.*?)(\d+)([^\d\s]+)(.*?)$/;
    // const TIME_REGEX_DIGITS = /(\d+)\s*[^\d\s]+\s+(\d+)/;
    // const TIME_REGEX_DIGITS = /(\d{1,2})m (\d{1,2})s/.exec(content);
    const TIME_REGEX_DIGITS = /(\d{1,2})(?!\d|[:;\-._,])\s*[^\d\s]+\s+(\d{1,2})/;


    // Cache element time differences
    const elementTimeCache = new WeakMap();

    class RelativeTime {

        constructor(timezone) {
            this.timezone = timezone;
            this._offsetCache = null;
            this._templateCache = new Map();
        }

        setTimezone(timezone) {
            this.timezone = timezone;
        }

        update(root = document) {
            const DYNAMIC_RELATIVE_TIME_THRESHOLD = 60 * 60;

            root.querySelectorAll('time[data-relative-time="ago"], time[data-relative-time="since"]')
                .forEach((element) => {
                    const diffSeconds = this._getTimeDifferenceInSeconds(element);
                    if (diffSeconds == null || diffSeconds >= DYNAMIC_RELATIVE_TIME_THRESHOLD) return;

                    element.textContent = this.render(diffSeconds, element);
                });

            root.querySelectorAll('time[data-relative-time="until"]')
                .forEach((element) => {
                    let remainingSeconds = this._getTimeDifferenceInSeconds(element, true);
                    if (remainingSeconds == null || Math.abs(remainingSeconds) >= DYNAMIC_RELATIVE_TIME_THRESHOLD) return;

                    if (remainingSeconds === 0 && element.dataset.agoLabel) {
                        element.textContent = element.dataset.agoLabel;
                        element.dataset.relativeTime = 'ago';
                        return;
                    }

                    element.textContent = this.render(Math.abs(remainingSeconds), element);
                });
        }

        _getTimeDifferenceInSeconds(element, future = false) {

            const fromDateTimeWithTimezone = (element, future = false) => {
                const timeString = element.dateTime || element.getAttribute('datetime');
                const isoString = timeString.replace(' ', 'T');

                const offset = this._getOffset();

                const targetTimeUTC = Date.parse(`${isoString}${offset}`);
                const now = Date.now();

                return Math.floor((future ? targetTimeUTC - now : now - targetTimeUTC) / 1000);
            };

            const fromTextContent = (element, future = false) => {
                const content = element.textContent;

                const partialTime = TIME_REGEX_DIGITS.exec(content);

                if (!partialTime) {
                    return null;
                }

                const minutes = parseInt(partialTime[1], 10);
                const seconds = parseInt(partialTime[2], 10);

                let secondsDiff = minutes * 60 + seconds;

                return future ? --secondsDiff * -1 : ++secondsDiff;
            };

            const cached = elementTimeCache.get(element);
            if (cached) {
                const next = cached + (future ? -1 : 1);
                elementTimeCache.set(element, next);

                return next;
            }

            const timeDifference = fromDateTimeWithTimezone(element, future);
            // const timeDifference = fromTextContent(element, future);
            elementTimeCache.set(element, timeDifference);

            return timeDifference;
        }

        /**
         * Parse and cache prefix / units / suffix once
         */
        _getTemplate(element) {
            const type = element.dataset.relativeTime; // 'ago', 'since', 'until'

            if (this._templateCache.has(type)) {
                return this._templateCache.get(type);
            }

            const content = element.textContent || '';
            let match = TIME_REGEX_FULL.exec(content) || TIME_REGEX_MIN_ONLY.exec(content);

            const template = {
                prefix: match?.[1] ?? '',
                minuteUnit: match?.[3] ?? 'm',
                secondUnit: match?.[5] ?? 's',
                suffix: match?.[6] ?? ''
            };

            this._templateCache.set(type, template);

            return template
        }

        _getOffset() {
            if (!this._offsetCache) {
                const formatter = new Intl.DateTimeFormat('en-US', {
                    timeZone: this.timezone,
                    timeZoneName: 'longOffset'
                });
                const parts = formatter.formatToParts(new Date());
                this._offsetCache = parts.find(p => p.type === 'timeZoneName')
                    .value.replace('GMT', '');
            }

            return this._offsetCache;
        }

        /**
         * Render relative time string using cached template
         */
        render(diffInSeconds, element) {
            const template = this._getTemplate(element);

            const minute = Math.floor(diffInSeconds / 60);
            const second = diffInSeconds % 60;

            return (
                template.prefix +
                minute +
                template.minuteUnit +
                ' ' +
                second +
                template.secondUnit +
                template.suffix
            );
        }
    }

    return RelativeTime;
});
