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
                    const mode = element.dataset.relativeTime;

                    const diffSeconds = Math.max(0, getTimeDifferenceInSeconds(element, timezone));

                    if (diffSeconds >= DYNAMIC_RELATIVE_TIME_THRESHOLD) {
                        return;
                    }

                    element.innerHTML = this.render(diffSeconds, mode, element);
                });

            root.querySelectorAll('time[data-relative-time="until"]')
                .forEach((element) => {
                    let remainingSeconds = getTimeDifferenceInSeconds(element, timezone, true);

                    if (Math.abs(remainingSeconds) >= DYNAMIC_RELATIVE_TIME_THRESHOLD) {
                        return;
                    }

                    if (remainingSeconds === 0 && element.dataset.agoLabel) {
                        element.innerText = element.dataset.agoLabel;
                        element.dataset.relativeTime = 'ago';

                        return;
                    }

                    const absSeconds = remainingSeconds * (remainingSeconds < 0 ? -1 : 1);

                    element.innerHTML =  this.render(absSeconds, 'until', element);
                });
        }

        /**
         * Render the relative time string
         *
         * @param diffInSeconds
         * @param mode
         * @param element The HTML element to extract units from
         *
         * @returns {string}
         */
        render(diffInSeconds, mode, element) {
            const minute = Math.floor(diffInSeconds / 60);
            const second = diffInSeconds % 60;

            const sign = mode === 'ago' || mode === 'since' ? -1 : 1;

            let min = minute * sign;
            let sec = second * sign;

            // Parse prefix, suffix, and units from existing content
            const content = element.textContent || element.innerText || '';

            // Pattern to extract: {prefix}MM{minute_unit} SS{second_unit}{suffix}
            const timeMatch = content.match(/^(.*?)(\d+)([^\d\s]+)\s+(\d+)([^\d\s]+)(.*?)$/)
                || content.match(/^(.*?)(\d+)([^\d\s]+)(.*?)$/);

            let prefix = timeMatch[1] || '';
            let minuteUnit = timeMatch[3] || 'm';
            let secondUnit = timeMatch[5] || 's';
            let suffix = timeMatch[6] || '';

            if (!timeMatch || (!prefix && !suffix)) {
                if (sign === -1) {
                    suffix = ' ago';
                } else {
                    prefix = 'in ';
                }
            }

            const absMinute = Math.abs(min);
            const absSecond = Math.abs(second);

            return `${prefix}${absMinute.toString()}${minuteUnit} ${absSecond.toString().padStart(2, '0')}${secondUnit}${suffix}`;
        }
    }

    return RelativeTime;
});
