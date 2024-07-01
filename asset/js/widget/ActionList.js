define(["../notjQuery"], function (notjQuery) {

    "use strict";

    const LIST_IDENTIFIER = '[data-interactable-action-list]';
    const LIST_ITEM_IDENTIFIER = '[data-action-item]';

    class ActionList {
        constructor(list) {
            this.list = list;

            this.lastActivatedItemUrl = null;
            this.lastTimeoutId = null;
        }

        bind() {
            notjQuery(this.list).on('click', `${LIST_IDENTIFIER} ${LIST_ITEM_IDENTIFIER}, ${LIST_IDENTIFIER} ${LIST_ITEM_IDENTIFIER} a[href]`, this.onClick, this);

            return this;
        }

        refresh(list, detailUrl = null) {
            if (list === this.list) {
                // If the DOM node is still the same, nothing has changed
                return;
            }

            this.list = list;
            this.bind();

            this.load(detailUrl)
        }

        destroy() {
            this.list = null;
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

            const isBeingMultiSelected = this.list.matches('[data-icinga-multiselect-url]')
                && (event.ctrlKey || event.metaKey || event.shiftKey);

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

            if (activeItems.length === 1
                && toActiveItems.length === 0
            ) {
                notjQuery(this.list).trigger('all-deselected', {target: target, actionList: this});

                this.clearSelection(toDeactivateItems);
                this.addSelectionCountToFooter();
                return;
            }

            let dashboard = this.list.closest('.dashboard');
            if (dashboard) {
                dashboard.querySelectorAll(LIST_IDENTIFIER).forEach(otherList => {
                    if (otherList !== this.list) {
                        toDeactivateItems.push(...this.getAllItems(otherList));
                    }
                })
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

            if (! dashboard) {
                this.addSelectionCountToFooter();
            }

            this.setLastActivatedItemUrl(lastActivatedUrl);
            this.loadDetailUrl(target.matches('a') ? target.getAttribute('href') : null);
        }

        /**
         * Add the selection count to footer if list allow multi selection
         *
         */
        addSelectionCountToFooter() {
            if (! this.list.matches('[data-icinga-multiselect-url]')) {
                return;
            }

            let activeItemCount = this.getActiveItems().length;
            let footer = this.list.closest('.container').querySelector('.footer');

            // For items that do not have a bottom status bar like Downtimes, Comments...
            if (footer === null) {
                footer = notjQuery.render(
                    '<div class="footer" data-action-list-automatically-added>' +
                            '<div class="selection-count"><span class="selected-items"></span></div>' +
                        '</div>'
                )

                this.list.closest('.container').appendChild(footer);
            }

            let selectionCount = footer.querySelector('.selection-count');
            if (selectionCount === null) {
                selectionCount = notjQuery.render(
                    '<div class="selection-count"><span class="selected-items"></span></div>'
                );

                footer.prepend(selectionCount);
            }

            let selectedItems = selectionCount.querySelector('.selected-items');
            selectedItems.innerText = activeItemCount
                ? this.list.dataset.icingaMultiselectCountLabel.replace('%d', activeItemCount)
                : this.list.dataset.icingaMultiselectHintLabel;

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
            let list = null;
            let pressedArrowDownKey = event.key === 'ArrowDown';
            let pressedArrowUpKey = event.key === 'ArrowUp';
            let focusedElement = document.activeElement;

            if (
                ! event.key // input auto-completion is triggered
                || (event.key.toLowerCase() !== 'a' && ! pressedArrowDownKey && ! pressedArrowUpKey)
            ) {
                return;
            }

            if (focusedElement && (
                focusedElement.matches('#main > :scope') // add #main as data-attr via php
                || focusedElement.matches('body'))
            ) {
                list = focusedElement.querySelector(LIST_IDENTIFIER);
                if (! list) {
                    let activeItem = this.list.querySelector(`:scope > ${LIST_ITEM_IDENTIFIER}.active`);
                    if (activeItem) {
                        list = this.list;
                    }
                }

            } else if (focusedElement) {
                list = focusedElement.closest(LIST_IDENTIFIER);
            }

            if (list !== this.list) {
                return;
            }

            let isMultiSelectableList = list.matches('[data-icinga-multiselect-url]');

            if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'a') {
                if (! isMultiSelectableList) {
                    return;
                }

                event.preventDefault();
                this.selectAll();
                return;
            }

            event.preventDefault();

            let allItems = this.getAllItems();
            let firstListItem = allItems[0];
            let lastListItem = allItems[allItems.length -1];
            let activeItems = this.getActiveItems();
            let markAsLastActive = null; // initialized only if it is different from toActiveItem
            let toActiveItem = null;
            let wasAllSelected = activeItems.length === allItems.length;
            let lastActivatedItem = list.querySelector(
                `[data-icinga-detail-filter="${ this.lastActivatedItemUrl }"]`
            );

            if (! lastActivatedItem && activeItems.length) {
                lastActivatedItem = activeItems[activeItems.length - 1];
            }

            let directionalNextItem = this.getDirectionalNext(lastActivatedItem, event.key);

            if (activeItems.length === 0) {
                toActiveItem = pressedArrowDownKey ? firstListItem : lastListItem;
                // reset all on manual page refresh
                this.clearSelection(activeItems);
            } else if (isMultiSelectableList && event.shiftKey) {
                if (activeItems.length === 1) {
                    toActiveItem = directionalNextItem;
                } else if (wasAllSelected && (
                    (lastActivatedItem !== firstListItem && pressedArrowDownKey)
                    || (lastActivatedItem !== lastListItem && pressedArrowUpKey)
                )) {
                    if (pressedArrowDownKey) {
                        toActiveItem = lastActivatedItem === lastListItem ? null : lastListItem;
                    } else {
                        toActiveItem = lastActivatedItem === firstListItem ? null : lastListItem;
                    }

                } else if (directionalNextItem && directionalNextItem.classList.contains('active')) {
                    // deactivate last activated by down to up select
                    this.clearSelection([lastActivatedItem]);
                    if (wasAllSelected) {
                        this.scrollItemIntoView(lastActivatedItem, event.key);
                    }

                    toActiveItem = directionalNextItem;
                } else {
                    [toActiveItem, markAsLastActive] = this.findToActiveItem(lastActivatedItem, event.key);
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
            this.scrollItemIntoView(toActiveItem, event.key);
            this.addSelectionCountToFooter();
            this.loadDetailUrl();
        }

        /**
         * Get the next list item according to the pressed key (`ArrowUp` or `ArrowDown`)
         *
         * @param item The list item from which we want the next item
         * @param eventKey Pressed key (`ArrowUp` or `ArrowDown`)
         *
         * @returns {Element|null} Returns the next selectable list item or null if none found (list ends)
         */
        getDirectionalNext(item, eventKey) {
            if (! item) {
                return null;
            }

            let nextItem = null;

            do {
                nextItem = eventKey === 'ArrowUp' ? item.previousElementSibling : item.nextElementSibling;
                item = nextItem;
            } while (nextItem && ! nextItem.hasAttribute(this.removeBrackets(LIST_ITEM_IDENTIFIER)))

            return nextItem;
        }

        /**
         * Find the list item that should be activated next
         *
         * @param lastActivatedItem
         * @param eventKey Pressed key (`ArrowUp` or `ArrowDown`)
         *
         * @returns {Element[]}
         */
        findToActiveItem(lastActivatedItem, eventKey) {
            let toActiveItem;
            let markAsLastActive;

            do {
                toActiveItem = this.getDirectionalNext(lastActivatedItem, eventKey);
                lastActivatedItem = toActiveItem;
            } while (toActiveItem && toActiveItem.classList.contains('active'))

            markAsLastActive = toActiveItem;
            // if the next/previous sibling element is already active,
            // mark the last/first active element in list as last active
            while (markAsLastActive && this.getDirectionalNext(markAsLastActive, eventKey)) {
                if (! this.getDirectionalNext(markAsLastActive, eventKey).classList.contains('active')) {
                    break;
                }

                markAsLastActive = this.getDirectionalNext(markAsLastActive, eventKey);
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
         * @param url
         */
        setLastActivatedItemUrl (url) {
            this.lastActivatedItemUrl = url;
        }

        /**
         * Scroll the given item into view
         *
         * @param item Item to scroll into view
         * @param pressedKey Pressed key (`ArrowUp` or `ArrowDown`)
         */
        scrollItemIntoView(item, pressedKey) {
            item.scrollIntoView({block: "nearest"});
            let directionalNext = this.getDirectionalNext(item, pressedKey);

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

            clearTimeout(this.lastTimeoutId);
            this.lastTimeoutId = setTimeout(() => {
                this.lastTimeoutId = null;

                // TODO: maybe we need a property to know if a req is in process

                notjQuery(this.list).trigger(
                    'load-selection',
                    {url: url, firstActiveItem: activeItems[0]}
                );
            }, 250);
        }

        /**
         * Add .active class to given list item
         *
         * @param toActiveItem The list item(s)
         */
        setActive(toActiveItem) {
            if (toActiveItem instanceof HTMLElement) {
                toActiveItem.classList.add('active');
            } else {
                toActiveItem.forEach(item => item.classList.add('active'));
            }
        }

        /**
         * Get the active items
         *
         * @return array
         */
        getActiveItems()
        {
            let items;

            if (this.list.tagName.toLowerCase() === 'table') {
                items = this.list.querySelectorAll(`:scope > tbody > ${LIST_ITEM_IDENTIFIER}.active`);
            } else {
                items = this.list.querySelectorAll(`:scope > ${LIST_ITEM_IDENTIFIER}.active`);
            }

            return Array.from(items);
        }

        /**
         * Get all available items
         *
         * @return array
         */
        getAllItems()
        {
            let items;

            if (this.list.tagName.toLowerCase() === 'table') {
                items = this.list.querySelectorAll(`:scope > tbody > ${LIST_ITEM_IDENTIFIER}`);
            } else {
                items = this.list.querySelectorAll(`:scope > ${LIST_ITEM_IDENTIFIER}`);
            }

            return Array.from(items);
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
            if (! detailUrl) {
                return;
            }

            let toActiveItems = [];
            if (this.list.dataset.icingaMultiselectUrl === detailUrl.path) {
                for (const filter of this.parseSelectionQuery(detailUrl.query.slice(1))) {
                    let item = this.list.querySelector(
                        '[data-icinga-multiselect-filter="' + filter + '"]'
                    );

                    if (item) {
                        toActiveItems.push(item);
                    }
                }
            } else  {
                let item = this.list.querySelector(
                    '[data-icinga-detail-filter="' + detailUrl.query.slice(1) + '"]'
                );

                if (item) {
                    toActiveItems.push(item);
                }
            }

            this.clearSelection(this.getAllItems().filter(item => ! toActiveItems.includes(item)));
            this.setActive(toActiveItems);
            this.addSelectionCountToFooter();
        }
    }

    return ActionList;
});
