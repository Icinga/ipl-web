/* Icinga Web 2 | (c) 2025 Icinga GmbH | GPLv2+ */

define(["../widget/RelativeTime", "icinga/legacy-app/Icinga"], function (RelativeTime, Icinga) {

    "use strict";

    class RelativeTimeBehavior extends Icinga.EventListener {
        constructor(icinga) {
            super(icinga);

            /**
             * RelativeTime instance
             *
             * @type {RelativeTime}
             * @private
             */
            this._relativeTime = new RelativeTime(icinga.config.timezone);

            this._relativeTime.update(document);

            if (this._timerHandle == null) {
                this._timerHandle = icinga.timer.register(
                    () => {this._relativeTime.update(document); },
                    this,
                    1000
                );
            }
        }
    }

    Icinga.Behaviors = Icinga.Behaviors || {};

    Icinga.Behaviors.RelativeTime = RelativeTimeBehavior;
});
