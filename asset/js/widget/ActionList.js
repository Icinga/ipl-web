define(["../notjQuery"], function ($) {

    "use strict";

    const LIST_IDENTIFIER = '[data-interactable-action-list]';
    const LIST_ITEM_IDENTIFIER = '[data-action-item]';

    class ActionList {
        /** @type {?Element} The list */
        #list = null;

        /** @type {?Element} The footer to add selection count and navigation hint */
        #footer = null;

        /** @type {boolean} Whether the list should be considered as a primary list */
        #isPrimary = false;

        /** @type {boolean} Whether the list selection is in process */
        #processing = false;

        /** @type {boolean} Whether the list supports multi-selection */
        #isMultiSelectable = false;

        /** @type {boolean} Whether the list is set to `display: contents` */
        #isDisplayContents = false;

        /** @type {?string} The url of last active item */
        #lastActivatedItemUrl = null;

        /** @type {?number} The timeout id */
        #lastTimeoutId = null;

        constructor(list) {
            this.#list = list;

            let firstItem = this.getDirectionalNext(null, false);
            if (firstItem
                && (! firstItem.checkVisibility() && firstItem.firstChild && firstItem.firstChild.checkVisibility())
            ) {
                this.#isDisplayContents = true;
            }

            this.bind();
        }

        bind() {
            $(this.#list).on('click', `${LIST_IDENTIFIER} ${LIST_ITEM_IDENTIFIER}, ${LIST_IDENTIFIER} ${LIST_ITEM_IDENTIFIER} a[href]`, this.onClick, this);

            this.bindedKeyDown = this.onKeyDown.bind(this)
            document.body.addEventListener('keydown', this.bindedKeyDown);

            return this;
        }

        unbind() {
            document.body.removeEventListener('keydown', this.bindedKeyDown);
            this.bindedKeyDown = null;
            this.#list = null;
        }

        refresh(list, footer = null, detailUrl = null) {
            if (list === this.#list) {
                // If the DOM node is still the same, nothing has changed
                return;
            }

            this.unbind();

            this.#list = list;
            this.setFooter(footer);

            this.bind();

            this.load(detailUrl)
        }

        destroy() {
            this.unbind();
        }

        /**
         * Set whether the list should be considered as a primary list
         *
         * @param {boolean} isPrimary
         *
         * @returns {ActionList}
         */
        setIsPrimary(isPrimary) {
            this.#isPrimary = isPrimary;

            return this;
        }

        /**
         * Set whether the list supports multi-selection
         * @param {boolean} isMultiSelectable
         *
         * @returns {ActionList}
         */
        setIsMultiSelectable(isMultiSelectable) {
            this.#isMultiSelectable = isMultiSelectable;

            return this;
        }

        /**
         * The footer to add selection count and navigation hint
         *
         * @param {?Element} footer
         *
         * @returns {ActionList}
         */
        setFooter(footer) {
            this.#footer = footer;

            return this;
        }

        /**
         * Whether the list selection is processing
         *
         * @return {boolean}
         */
        isProcessing() {
            return this.#processing;
        }

        /**
         * Set whether the list selection is being loaded
         *
         * @param isProcessing True as default
         */
        setProcessing(isProcessing = true) {
            this.#processing = isProcessing;
        }

        /**
         * Parse the filter query contained in the given URL query string
         *
         * @param {string} queryString
         *
         * @returns {array}
         */
        parseSelectionQuery(queryString) {
            return queryString.split('|');
        }

        /**
         * Remove the `[ ]`brackets from the given identifier
         * @param identifier
         * @return {*}
         */
        removeBrackets(identifier) {
            return identifier.replaceAll(/[\[\]]/g, '');
        }

        onClick(event) {
            let target = event.currentTarget;

            if (target.matches('a') && (! target.matches('.subject') || event.ctrlKey || event.metaKey)) {
                return true;
            }

            event.preventDefault();
            event.stopImmediatePropagation();
            event.stopPropagation();

            let item = target.closest(LIST_ITEM_IDENTIFIER);
            let activeItems = this.getActiveItems();
            let toActiveItems = [],
                toDeactivateItems = [];

            const isBeingMultiSelected = this.#isMultiSelectable && (event.ctrlKey || event.metaKey || event.shiftKey);

            if (isBeingMultiSelected) {
                if (event.ctrlKey || event.metaKey) {
                    if (item.classList.contains('active')) {
                        toDeactivateItems.push(item);
                    } else {
                        toActiveItems.push(item);
                    }
                } else {
                    document.getSelection().removeAllRanges();

                    let allItems = this.getAllItems();

                    let startIndex = allItems.indexOf(item);
                    if(startIndex < 0) {
                        startIndex = 0;
                    }

                    let endIndex = activeItems.length ? allItems.indexOf(activeItems[0]) : 0;
                    if (startIndex > endIndex) {
                        toActiveItems = allItems.slice(endIndex, startIndex + 1);
                    } else {
                        endIndex = activeItems.length ? allItems.indexOf(activeItems[activeItems.length - 1]) : 0;
                        toActiveItems = allItems.slice(startIndex, endIndex + 1);
                    }

                    toDeactivateItems = activeItems.filter(item => ! toActiveItems.includes(item));
                    toActiveItems = toActiveItems.filter(item => ! activeItems.includes(item));
                }
            } else {
                toDeactivateItems = activeItems;
                toActiveItems.push(item);
            }

            if (activeItems.length === 1 && toActiveItems.length === 0) {
                $(this.#list).trigger('all-deselected');

                this.setLastActivatedItemUrl(null);
                this.clearSelection(toDeactivateItems);
                this.addSelectionCountToFooter();
                return;
            }

            let lastActivatedUrl = null;
            if (toActiveItems.includes(item)) {
                lastActivatedUrl = item.dataset.icingaDetailFilter;
            } else if (activeItems.length > 1) {
                lastActivatedUrl = activeItems[activeItems.length - 1] === item
                    ? activeItems[activeItems.length - 2].dataset.icingaDetailFilter
                    : activeItems[activeItems.length - 1].dataset.icingaDetailFilter;
            }

            this.clearSelection(toDeactivateItems);
            this.setActive(toActiveItems);

            this.setLastActivatedItemUrl(lastActivatedUrl);
            this.addSelectionCountToFooter();
            this.loadDetailUrl(target.matches('a') ? target.getAttribute('href') : null);
        }

        /**
         * Add selection count to the footer if list is multi selectable and primary
         */
        addSelectionCountToFooter() {
            if (! this.#footer) {
                return;
            }

            let activeItemCount = this.getActiveItems().length;

            let selectionCount = this.#footer.querySelector('.selection-count');
            if (selectionCount === null) {
                selectionCount = $.render(
                    '<div class="selection-count"><span class="selected-items"></span></div>'
                );

                this.#footer.prepend(selectionCount);
            }

            let selectedItems = selectionCount.querySelector('.selected-items');
            selectedItems.innerText = activeItemCount
                ? this.#list.dataset.icingaMultiselectCountLabel.replace('%d', activeItemCount)
                : this.#list.dataset.icingaMultiselectHintLabel;

            if (activeItemCount === 0) {
                selectedItems.classList.add('hint');
            } else {
                selectedItems.classList.remove('hint');
            }
        }

        /**
         * Key navigation
         *
         * - `Shift + ArrowUp|ArrowDown` = Multiselect
         * - `ArrowUp|ArrowDown` = Select next/previous
         * - `Ctrl|cmd + A` = Select all on currect page
         *
         * @param event
         */
        onKeyDown(event) {
            if (! this.#list.checkVisibility()) {
                // list is not visible in the current view
                return;
            }

            let activeItems = this.getActiveItems();
            if (! this.#isPrimary && activeItems.length === 0) {
                return;
            }

            //TODO: fix that navigation on searchbar suggestions also navigate the list

            let pressedArrowDownKey = event.key === 'ArrowDown';
            let pressedArrowUpKey = event.key === 'ArrowUp';
            let isSelectAll = (event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'a' && this.#isMultiSelectable;

            if (! isSelectAll && ! pressedArrowDownKey && ! pressedArrowUpKey) {
                return;
            }

            event.preventDefault();
            if (isSelectAll) {
                this.selectAll();
                return;
            }

            let allItems = this.getAllItems();
            let firstListItem = allItems[0];
            let lastListItem = allItems[allItems.length -1];
            let markAsLastActive = null; // initialized only if it is different from toActiveItem
            let toActiveItem = null;
            let wasAllSelected = activeItems.length === allItems.length;
            let lastActivatedItem = this.#list.querySelector(
                `[data-icinga-detail-filter="${ this.#lastActivatedItemUrl }"]`
            );

            if (! lastActivatedItem && activeItems.length) {
                lastActivatedItem = activeItems[activeItems.length - 1];
            }

            let directionalNextItem = this.getDirectionalNext(lastActivatedItem, pressedArrowUpKey);

            if (activeItems.length === 0) {
                toActiveItem = directionalNextItem;
                // reset all on manual page refresh
                this.clearSelection(activeItems);
            } else if (this.#isMultiSelectable && event.shiftKey) {
                if (activeItems.length === 1) {
                    toActiveItem = directionalNextItem;
                } else if (wasAllSelected && (lastActivatedItem !== firstListItem && pressedArrowDownKey)) {
                    toActiveItem = lastActivatedItem === lastListItem ? null : lastListItem;
                } else if (directionalNextItem && directionalNextItem.classList.contains('active')) {
                    // deactivate last activated by down to up select
                    this.clearSelection([lastActivatedItem]);
                    if (wasAllSelected) {
                        this.scrollItemIntoView(lastActivatedItem, pressedArrowUpKey);
                    }

                    toActiveItem = directionalNextItem;
                } else {
                    [toActiveItem, markAsLastActive] = this.findToActiveItem(lastActivatedItem, pressedArrowUpKey);
                }
            } else {
                toActiveItem = directionalNextItem;

                if (toActiveItem) {
                    this.clearSelection(activeItems);
                }
            }

            if (! toActiveItem) {
                return;
            }

            this.setActive(toActiveItem);
            this.setLastActivatedItemUrl(
                markAsLastActive ? markAsLastActive.dataset.icingaDetailFilter : toActiveItem.dataset.icingaDetailFilter
            );
            this.scrollItemIntoView(toActiveItem, pressedArrowUpKey);
            this.addSelectionCountToFooter();
            this.loadDetailUrl();
        }

        /**
         * Get the next list item according to the pressed key (`ArrowUp` or `ArrowDown`)
         *
         * @param item The list item from which we want the next item
         * @param isArrowUp Whether the arrow up key is pressed, if not, arrow down key is assumed
         *
         * @returns {Element|null} Returns the next selectable list item or null if none found (list ends)
         */
        getDirectionalNext(item, isArrowUp) {
            if (! item) {
                item = isArrowUp ? this.#list.lastChild : this.#list.firstChild;

                if (! item) {
                    return null;
                }

                if (item.hasAttribute(this.removeBrackets(LIST_ITEM_IDENTIFIER))) {
                    return item;
                }
            }

            let nextItem = null;

            do {
                nextItem = isArrowUp ? item.previousElementSibling : item.nextElementSibling;
                item = nextItem;
            } while (nextItem && ! nextItem.hasAttribute(this.removeBrackets(LIST_ITEM_IDENTIFIER)))

            return nextItem;
        }

        /**
         * Find the list item that should be activated next
         *
         * @param lastActivatedItem
         * @param isArrowUp Whether the arrow up key is pressed, if not, arrow down key is assumed
         *
         * @returns {Element[]}
         */
        findToActiveItem(lastActivatedItem, isArrowUp) {
            let toActiveItem;
            let markAsLastActive;

            do {
                toActiveItem = this.getDirectionalNext(lastActivatedItem, isArrowUp);
                lastActivatedItem = toActiveItem;
            } while (toActiveItem && toActiveItem.classList.contains('active'))

            markAsLastActive = toActiveItem;
            // if the next/previous sibling element is already active,
            // mark the last/first active element in list as last active
            while (markAsLastActive && this.getDirectionalNext(markAsLastActive, isArrowUp)) {
                if (! this.getDirectionalNext(markAsLastActive, isArrowUp).classList.contains('active')) {
                    break;
                }

                markAsLastActive = this.getDirectionalNext(markAsLastActive, isArrowUp);
            }

            return [toActiveItem, markAsLastActive];
        }

        /**
         * Select All list items
         */
        selectAll() {
            let allItems = this.getAllItems();
            let activeItems = this.getActiveItems();
            this.setActive(allItems.filter(item => ! activeItems.includes(item)));
            this.setLastActivatedItemUrl(allItems[allItems.length -1].dataset.icingaDetailFilter);
            this.addSelectionCountToFooter();
            this.loadDetailUrl();
        }

        /**
         * Clear the selection by removing .active class
         *
         * @param selectedItems The items with class active
         */
        clearSelection(selectedItems) {
            selectedItems.forEach(item => item.classList.remove('active'));
        }

        /**
         * Set the last activated item Url
         *
         * @param {?string} url
         */
        setLastActivatedItemUrl (url) {
            this.#lastActivatedItemUrl = url;
        }

        /**
         * Scroll the given item into view
         *
         * @param item Item to scroll into view
         * @param isArrowUp Whether the arrow up key is pressed, if not, arrow down key is assumed
         */
        scrollItemIntoView(item, isArrowUp) {
            let directionalNext = this.getDirectionalNext(item, isArrowUp);
            if (this.#isDisplayContents) {
                item = item.firstChild;
                directionalNext = directionalNext ? directionalNext.firstChild : null;
            }

            item.scrollIntoView({block: "nearest"});
            if (directionalNext) {
                directionalNext.scrollIntoView({block: "nearest"});
            }
        }

        /**
         * Load the detail url with selected items
         *
         * @param anchorUrl If any anchor is clicked (e.g. host in service list)
         */
        loadDetailUrl(anchorUrl = null) {
            let url = anchorUrl;
            let activeItems = this.getActiveItems();

            if (url === null) {
                if (activeItems.length > 1) {
                    url = this.createMultiSelectUrl(activeItems);
                } else {
                    let anchor = activeItems[0].querySelector('[href]');
                    url = anchor ? anchor.getAttribute('href') : null;
                }
            }

            if (url === null) {
                return;
            }

            if (this.#lastTimeoutId === null) { // trigger once, when just started selecting list items
                $(this.#list).trigger('selection-start');
            }

            clearTimeout(this.#lastTimeoutId);
            this.#lastTimeoutId = setTimeout(() => {
                this.#lastTimeoutId = null;

                this.setProcessing();

                $(this.#list).trigger('selection-end', {url: url, actionList: this});
            }, 250);
        }

        /**
         * Add .active class to given list item
         *
         * @param toActiveItem The list item(s)
         */
        setActive(toActiveItem) {
            if (toActiveItem instanceof HTMLElement) {
                toActiveItem = [toActiveItem]
            }

            toActiveItem.forEach(item => item.classList.add('active'));
        }

        /**
         * Get the active items
         *
         * @return array
         */
        getActiveItems()
        {
            return Array.from(this.#list.querySelectorAll(`${LIST_ITEM_IDENTIFIER}.active`));
        }

        /**
         * Get all available items
         *
         * @return array
         */
        getAllItems()
        {
            return Array.from(this.#list.querySelectorAll(LIST_ITEM_IDENTIFIER));
        }

        /**
         * Create the detail url for multi selectable list
         *
         * @param items List items
         * @param withBaseUrl Default to true
         *
         * @returns {string} The url
         */
        createMultiSelectUrl(items, withBaseUrl = true) {
            let filters = [];
            items.forEach(item => {
                filters.push(item.getAttribute('data-icinga-multiselect-filter'));
            });

            let url = '?' + filters.join('|');

            if (withBaseUrl) {
                return items[0].closest(LIST_IDENTIFIER).getAttribute('data-icinga-multiselect-url') + url;
            }

            return url;
        }

        /**
         * Load the selection based on given detail url
         *
         * @param detailUrl
         */
        load(detailUrl = null) {
            if (this.isProcessing()) {
                return;
            }

            if (! detailUrl) {
                let activeItems = this.getActiveItems();
                if (activeItems.length) {
                    this.setLastActivatedItemUrl(null);
                    this.clearSelection(activeItems);
                    this.addSelectionCountToFooter();
                }

                return;
            }

            let toActiveItems = [];
            if (this.#list.dataset.icingaMultiselectUrl === detailUrl.path) {
                for (const filter of this.parseSelectionQuery(detailUrl.query.slice(1))) {
                    let item = this.#list.querySelector(
                        '[data-icinga-multiselect-filter="' + filter + '"]'
                    );

                    if (item) {
                        toActiveItems.push(item);
                    }
                }
            } else  {
                let item = this.#list.querySelector(
                    '[data-icinga-detail-filter="' + detailUrl.query.slice(1) + '"]'
                );

                if (item) {
                    toActiveItems.push(item);
                }
            }

            this.clearSelection(this.getAllItems().filter(item => ! toActiveItems.includes(item)));
            this.setActive(toActiveItems);
            this.addSelectionCountToFooter();

            if (toActiveItems.length) {
                this.scrollItemIntoView(toActiveItems[toActiveItems.length - 1], false);
            }
        }
    }

    return ActionList;
});
