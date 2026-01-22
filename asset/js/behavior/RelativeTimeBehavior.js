/* Icinga Web 2 | (c) 2025 Icinga GmbH | GPLv2+ */

define(["../widget/RelativeTime", "icinga/legacy-app/Icinga"], function (RelativeTime, Icinga) {

    "use strict";

    class RelativeTimeBehavior extends Icinga.EventListener {
        constructor(icinga) {
            super(icinga);

            this.on('rendered', '#main > .container, #modal-content', this.onRendered, this);
            this.on('close-column', this.stop, this);
            this.on('close-modal', this.stop, this);

            /**
             * RelativeTime instance
             *
             * @type {RelativeTime}
             * @private
             */
            this._relativeTime = new RelativeTime(icinga.config.locale);

            /**
             * Timer handle
             *
             * @type {null|*}
             * @private
             */
            this._timerHandle = null;
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
                    () => {_this._relativeTime.update(); },
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
    }

    Icinga.Behaviors = Icinga.Behaviors || {};

    Icinga.Behaviors.RelativeTime = RelativeTimeBehavior;
});