define([], function () {

    "use strict";

    class RelativeTime {
        /**
         * @param icinga The Icinga application instance
         */
        constructor(icinga) {
            this.icinga = icinga;

            this.formatter = new Intl.RelativeTimeFormat(
                [icinga.config.locale, 'en'],
                {style: 'narrow'}
            );
        }

        /**
         * Update relative time elements within the given root
         *
         * @param root The root element to search within
         */
        update(root = document) {
            const RELATIVE_TIME_THRESHOLD = 60 * 60;

            const timezone = ((root) => {
                const doc = root?.nodeType === 9 ? root : (root?.ownerDocument || document);

                return icinga.config.timezone;
            })(root);

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

                    const minute = Math.floor(diffSeconds / 60);
                    const second = diffSeconds % 60;

                    element.innerHTML = this.render(minute, second, mode);
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

                    const minute = Math.floor(absSeconds / 60);
                    const second = absSeconds % 60;

                    element.innerHTML = this.render(minute, second, 'until');
                });
        }

        /**
         * Render the relative time string
         *
         * @param minute
         * @param second
         * @param mode
         * @returns {string}
         */
        render(minute, second, mode) {
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

    return RelativeTime;
});
