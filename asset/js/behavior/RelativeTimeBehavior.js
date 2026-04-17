define(["../RelativeTime", "icinga/legacy-app/Icinga"], function (RelativeTime, Icinga) {

    "use strict";

    class RelativeTimeBehavior extends Icinga.EventListener {
        constructor(icinga) {
            super(icinga);

            this.on('rendered', '.container', this.onTimeRendered, this);

            /**
             * RelativeTime instance
             *
             * @type {RelativeTime}
             * @private
             */
            this._relativeTime = new RelativeTime(icinga.config.timezone);
        }

        onTimeRendered(event) {
            if (event.currentTarget === event.target) {
                event.data.self._relativeTime.scan(event.target);
            }
        }

        unbind(emitter) {
            super.unbind(emitter);
            this._relativeTime.stop();
        }
    }

    Icinga.Behaviors = Icinga.Behaviors || {};

    Icinga.Behaviors.RelativeTime = RelativeTimeBehavior;
});
