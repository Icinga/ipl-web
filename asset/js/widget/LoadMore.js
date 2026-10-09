define(["../notjQuery"], function ($) {

    "use strict";

    class LoadMore {
        /**
         * @param element The element that contains the load-more anchor
         */
        constructor(element) {
            $(element).on('click', '.load-more[data-no-icinga-ajax] a', this.onLoadMoreClick, this);
            $(element).on('keypress', '.load-more[data-no-icinga-ajax] a', this.onKeyPress, this);
        }

        /**
         * Keypress (space) on load-more button
         *
         * @param event
         */
        onKeyPress(event) {
            if (event.key === ' ') {
                this.onLoadMoreClick(event);
            }
        }

        /**
         * Click on load-more button
         *
         * @param event
         */
        onLoadMoreClick(event) {
            event.stopPropagation();
            event.preventDefault();

            this.loadMore(event.target);
        }


        /**
         * Load more items based on the given anchor
         *
         * @param anchor
         */
        loadMore(anchor) {
            $(anchor).trigger('load');
        }
    }

    return LoadMore;
});