define([], function () {

    "use strict";

    class RelativeTime {
        /**
         * @param timezone The timezone to use for relative time calculations
         * @param locale The locale to use for relative time formatting
         */
        constructor(timezone, locale) {
            this.timezone = timezone;
            this.locale = locale;

            this.formatter = new Intl.RelativeTimeFormat(
                [locale, 'en'],
                {style: 'narrow'}
            );
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
            const RELATIVE_TIME_THRESHOLD = 60 * 60;

            const getTimeDifferenceInSeconds = (element, timezone, future = false) => {
                const timeString = element.dateTime || element.getAttribute('datetime');
                const isoString = timeString.replace(' ', 'T');

                const date = new Date(isoString + 'Z'); // Parse as UTC temporarily

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
                const offsetString = offsetPart.value.replace('GMT', '');
                // Create ISO 8601 string with timezone offset
                const dateTimeString = `${isoString}${offsetString}`;

                const givenTimeUTC = Date.parse(dateTimeString);

                const now = Date.now();

                return Math.floor((future ? givenTimeUTC - now : now - givenTimeUTC) / 1000);
            }

            root.querySelectorAll('time[data-relative-time="ago"], time[data-relative-time="since"]')
                .forEach((element) => {
                    const mode = element.dataset.relativeTime;

                    let diffSeconds = getTimeDifferenceInSeconds(element, timezone);
                    if (diffSeconds < 0) {
                        diffSeconds = 0;
                    }

                    if (diffSeconds >= RELATIVE_TIME_THRESHOLD) {
                        return;
                    }

                    element.innerHTML = this.render(diffSeconds, mode);
                });

            root.querySelectorAll('time[data-relative-time="until"]')
                .forEach((element) => {
                    let remainingSeconds = getTimeDifferenceInSeconds(element, timezone, true);

                    if (Math.abs(remainingSeconds) >= RELATIVE_TIME_THRESHOLD) {
                        return;
                    }

                    if (remainingSeconds === 0 && element.dataset.agoLabel) {
                        element.innerText = element.dataset.agoLabel;
                        element.dataset.relativeTime = 'ago';

                        return;
                    }

                    const absSeconds = remainingSeconds * (remainingSeconds < 0 ? -1 : 1);

                    element.innerHTML = this.render(absSeconds, 'until');
                });
        }

        /**
         * Render the relative time string
         *
         * @param diffInSeconds
         * @param mode
         *
         * @returns {string}
         */
        render(diffInSeconds, mode) {
            const minute = Math.floor(diffInSeconds / 60);
            const second = diffInSeconds % 60;

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

                const sec = String(seconds[i].value);
                const min = String(minutes[i].value);
                const maxLen = Math.min(min.length, sec.length);

                // helper: longest common prefix
                const lcp = () => {
                    let common = '';
                    for (let k = 1; k <= maxLen; k++) {
                        const currentPart = sec.slice(0, k);
                        if (min.startsWith(currentPart)) {
                            common = currentPart;
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
                        const currentPart = sec.slice(-k);
                        if (min.endsWith(currentPart)) {
                            common = currentPart;
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

    return RelativeTime;
});
