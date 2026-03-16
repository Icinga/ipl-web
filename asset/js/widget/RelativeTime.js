define([], function () {

    "use strict";

    const TIME_REGEX_FULL = /^(.*?)(\d+)([^\d\s]+)\s+(\d+)([^\d\s]+)(.*?)$/;
    const TIME_REGEX_MIN_ONLY = /^(.*?)(\d+)([^\d\s]+)(.*?)$/;
    const TIME_REGEX_DIGITS = /(-?)(\d{1,2})(?!\d|[:;\-._,])\s*m\s+(\d{1,2})(?!\d|[:;\-._,])\s*s/i;

    class RelativeTime {

        constructor(timezone) {
            this.timezone = timezone;
            this._offsetCache = null;
            this._templateCache = new Map();
        }

        update(root = document) {
            const DYNAMIC_RELATIVE_TIME_THRESHOLD = 60 * 60;

            this._offsetCache = null;
            this._templateCache = new Map();

            root.querySelectorAll('time[data-relative-time="ago"], time[data-relative-time="since"]')
                .forEach((element) => {
                    const diffSeconds = this._getTimeDifferenceInSeconds(element);
                    if (diffSeconds == null || diffSeconds >= DYNAMIC_RELATIVE_TIME_THRESHOLD) {
                        return;
                    }

                    element.textContent = this.render(diffSeconds, element);
                });

            root.querySelectorAll('time[data-relative-time="until"]')
                .forEach((element) => {
                    let remainingSeconds = this._getTimeDifferenceInSeconds(element, true);
                    if (remainingSeconds == null || Math.abs(remainingSeconds) >= DYNAMIC_RELATIVE_TIME_THRESHOLD) {
                        return;
                    }

                    if (remainingSeconds === 0 && element.dataset.agoLabel) {
                        element.textContent = element.dataset.agoLabel;
                        delete element.dataset.agoLabel;
                        element.dataset.relativeTime = 'ago';
                    }

                    element.textContent = this.render(remainingSeconds, element);
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

                const isNegative = partialTime[1] === '-';
                const minutes = parseInt(partialTime[2], 10);
                const seconds = parseInt(partialTime[3], 10);

                let secondsDiff = (isNegative ? -1 : 1) * (minutes * 60 + seconds);

                return future ? --secondsDiff : ++secondsDiff;
            };

            return fromTextContent(element, future);
            // return fromDateTimeWithTimezone(element, future);
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
                prefix: (match?.[1]).replace(/-+$/g, '') ?? '',
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

            const absDiff = Math.abs(diffInSeconds);
            const minute = Math.floor(absDiff / 60);
            const second = absDiff % 60;
            const sign = diffInSeconds < 0 ? '-' : '';

            return (
                template.prefix +
                sign +
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
