define(function () {

    "use strict";

    class RelativeTime {

        static DYNAMIC_RELATIVE_TIME_THRESHOLD = 60 * 60;

        constructor(timezone) {
            this.timezone = timezone;
            this._offsetCache = null;
            this._trackedElements = new Set();
            this._timer = null;
        }

        scan(root) {
            const elements = root.querySelectorAll('time[data-relative-time]');
            if (elements.length === 0) {
                return;
            }

            elements.forEach((el) => {
                this._trackedElements.add(new WeakRef(el));
            });

            if (! this._timer) {
                this._timer = setInterval(() => this.tick(), 1000);
            }
        }

        tick() {
            this._trackedElements.forEach((ref) => {
                const el = ref.deref();
                if (el) {
                    this.updateElement(el);
                }
            })
        }

        stop() {
            if (this._timer !== null) {
                clearInterval(this._timer);
                this._timer = null;
            }
        }

        updateElement(element) {
            const relativeTimeAgo = element.getAttribute('data-relative-time');
            if (relativeTimeAgo === 'ago' || relativeTimeAgo === 'since') {
                const diffSeconds = this.getTimeDifferenceInSeconds(element);
                if (diffSeconds == null || diffSeconds >= RelativeTime.DYNAMIC_RELATIVE_TIME_THRESHOLD) {
                    return;
                }

                element.textContent = element.textContent.replace(
                    /-?\d+m \d+s/,
                    this.render(diffSeconds)
                );
            } else if (relativeTimeAgo === 'until') {
                const remainingSeconds = this.getTimeDifferenceInSeconds(element, true);
                if (
                    remainingSeconds == null
                    || Math.abs(remainingSeconds) >= RelativeTime.DYNAMIC_RELATIVE_TIME_THRESHOLD
                ) {
                    return;
                }

                if (remainingSeconds === 0 && element.dataset.agoLabel) {
                    element.textContent = element.dataset.agoLabel;
                    element.dataset.relativeTime = 'ago';
                }

                element.textContent = element.textContent.replace(
                    /-?\d+m \d+s/,
                    this.render(remainingSeconds)
                );
            }
        }

        getTimeDifferenceInSeconds(element, future = false) {
            const timeString = element.dateTime || element.getAttribute('datetime');
            const isoString = timeString.replace(' ', 'T');

            const offset = this.getOffset();

            const targetTimeUTC = Date.parse(`${isoString}${offset}`);
            const now = Date.now();

            return Math.floor((future ? targetTimeUTC - now : now - targetTimeUTC) / 1000);
        }

        getOffset() {
            if (! this._offsetCache) {
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

        render(diffInSeconds) {
            const absDiff = Math.abs(diffInSeconds);
            const sign = diffInSeconds < 0 ? '-' : '';
            return `${sign}${Math.floor(absDiff / 60)}m ${absDiff % 60}s`;
        }
    }

    return RelativeTime;
});
