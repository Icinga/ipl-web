define([], function () {

    "use strict";

    const TIME_REGEX_FULL = /^(.*?)(\d+)([^\d\s]+)\s+(\d+)([^\d\s]+)(.*?)$/;
    const TIME_REGEX_MIN_ONLY = /^(.*?)(\d+)([^\d\s]+)(.*?)$/;

    // Cache parsed time templates per element
    const timeTemplateCache = new WeakMap();

    class RelativeTime {

        constructor(timezone, locale) {
            this.timezone = timezone;
            this.locale = locale;
        }

        setTimezone(timezone) {
            this.timezone = timezone;
        }

        /**
         * Update relative time elements within the given root
         *
         * @param root The root element to search within
         */
        update(root = document) {
            const timezone = this.timezone;
            const DYNAMIC_RELATIVE_TIME_THRESHOLD = 60 * 60;

            const getTimeDifferenceInSeconds = (element, timezone, future = false) => {
                const timeString = element.dateTime || element.getAttribute('datetime'); // e.g. "2026-01-30 03:21:52"
                const isoString = timeString.replace(' ', 'T'); // → "2026-01-30T03:21:52"

                // Construct a date assuming it's in the target timezone
                const [year, month, day, hour, minute, second] = isoString
                    .split(/[-T:]/)
                    .map(Number);

                // Create a "fake" UTC date from the components
                const utcDate = new Date(Date.UTC(year, month - 1, day, hour, minute, second));

                // Get the timezone offset at that local time
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

                const parts = formatter.formatToParts(utcDate);
                const offsetPart = parts.find(p => p.type === 'timeZoneName');
                const offsetString = offsetPart.value.replace('GMT', ''); // e.g. "+05:00" or "-04:00"

                // Now re-interpret the original ISO string with the correct offset
                const dateTimeWithOffset = `${isoString}${offsetString}`; // e.g. "2026-01-30T03:21:52-05:00"

                const targetTimeUTC = Date.parse(dateTimeWithOffset);
                const now = Date.now();

                return Math.floor((future ? targetTimeUTC - now : now - targetTimeUTC) / 1000);
            };

            root.querySelectorAll('time[data-relative-time="ago"], time[data-relative-time="since"]')
                .forEach((element) => {
                    const diffSeconds = Math.max(0, getTimeDifferenceInSeconds(element, timezone));
                    if (diffSeconds >= DYNAMIC_RELATIVE_TIME_THRESHOLD) return;

                    element.textContent = this.render(diffSeconds, element);
                });

            root.querySelectorAll('time[data-relative-time="until"]')
                .forEach((element) => {
                    let remainingSeconds = getTimeDifferenceInSeconds(element, timezone, true);
                    if (Math.abs(remainingSeconds) >= DYNAMIC_RELATIVE_TIME_THRESHOLD) return;

                    if (remainingSeconds === 0 && element.dataset.agoLabel) {
                        element.textContent = element.dataset.agoLabel;
                        element.dataset.relativeTime = 'ago';
                        return;
                    }

                    element.textContent = this.render(Math.abs(remainingSeconds), element);
                });
        }

        /**
         * Parse and cache prefix / units / suffix once
         */
        _getTemplate(element) {
            let cached = timeTemplateCache.get(element);
            if (cached) return cached;

            const content = element.textContent || '';
            let match = TIME_REGEX_FULL.exec(content) || TIME_REGEX_MIN_ONLY.exec(content);

            if (!match) {
                cached = {
                    prefix: '',
                    minuteUnit: 'm',
                    secondUnit: 's',
                    suffix: ''
                };
            } else {
                cached = {
                    prefix: match[1] || '',
                    minuteUnit: match[3] || 'm',
                    secondUnit: match[5] || 's',
                    suffix: match[6] || ''
                };
            }

            timeTemplateCache.set(element, cached);
            return cached;
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
                String(second).padStart(2, '0') +
                template.secondUnit +
                template.suffix
            );
        }
    }

    return RelativeTime;
});
