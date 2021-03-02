define(["../notjQuery", "../vendor/Sortable"], function ($, Sortable) {

    "use strict";

    class SearchBar {
        constructor(form) {
            this.form = form;
            this.filterInput = null;
            this._editorContainer = null;
            this._eventListener = null;
        }

        bind() {
            $(this.form.parentNode).on('click', '[data-search-editor-url]', this.onOpenerClick, this);
            $(this.editorContainer).on('end', 'ol.sortable', this.onRuleDropped, this);

            if (typeof icinga !== 'undefined') {
                // TODO: This *might* not be necessary anymore once the editor
                //       is loaded into a modal (and has its own enrichment)
                this._eventListener = new Icinga.EventListener(icinga);
                this._eventListener.on('rendered', this.form.dataset.searchEditor, this.onEditorRendered, this);
                this._eventListener.bind(document);
            }

            return this;
        }

        refresh(form) {
            if (form === this.form) {
                // If the DOM node is still the same, nothing has changed
                return;
            }

            this._editorContainer = null;

            this.form = form;
            this.bind();
        }

        destroy() {
            this.form = null;
            this.filterInput = null;
            this._editorContainer = null;
            this._eventListener = null;
        }

        disable() {
            // TODO: Use a data attribute to identify the button?
            let optionBtn = this.form.querySelector('.search-options');
            if (optionBtn) {
                optionBtn.disabled = true;
            }

            this.filterInput.disable();
        }

        enable() {
            // TODO: Use a data attribute to identify the button?
            let optionBtn = this.form.querySelector('.search-options');
            if (optionBtn) {
                optionBtn.disabled = false;
            }

            this.filterInput.enable();
        }

        get editorContainer() {
            if (this._editorContainer === null) {
                this._editorContainer = document.querySelector(this.form.dataset.searchEditor);
            }

            return this._editorContainer;
        }

        setFilterInput(filterInput) {
            this.filterInput = filterInput;

            return this;
        }

        onOpenerClick(event) {
            let opener = event.currentTarget;

            if (! this.editorContainer.matches(':empty')) {
                // TODO: Just ignore? i.e. require the user to submit the form?
                this.editorContainer.innerHTML = '';
                opener.classList.remove('active');
                $(this.form).trigger('editor-close');
                this.enable();
                return;
            }

            let url = this.form.action;
            if (! url) {
                throw new Error('Can\'t open editor. Form has no action');
            }

            this.disable();

            let editorUrl = opener.dataset.searchEditorUrl;
            let filterQueryString = this.filterInput.getQueryString();

            editorUrl += (editorUrl.indexOf('?') > -1 ? '&' : '?') + filterQueryString;

            let req = new XMLHttpRequest();
            req.open('GET', editorUrl, true);

            if (typeof icinga !== 'undefined') {
                let windowId = icinga.ui.getWindowId();
                let containerId = icinga.ui.getUniqueContainerId(this.editorContainer);
                if (containerId) {
                    req.setRequestHeader('X-Icinga-WindowId', windowId + '_' + containerId);
                } else {
                    req.setRequestHeader('X-Icinga-WindowId', windowId);
                }
            }

            req.addEventListener('loadend', () => {
                if (req.readyState > 0) {
                    opener.classList.add('active');
                    this.editorContainer.appendChild($.render(req.responseText));
                    $(this.form).trigger('editor-open');
                }
            });

            req.send();
        }

        onEditorRendered(event) {
            let renderTarget = event.target;

            renderTarget.querySelectorAll('.sortable').forEach(sortable => {
                let options = {
                    scroll: this.editorContainer,
                    group: 'rules',
                    direction: 'vertical',
                    // TODO: Play with these if drag'n'drop doesn't behave well
                    //invertSwap: true,
                    //swapThreshold: 0.65,
                    handle: '.drag-initiator'
                };

                Sortable.create(sortable, options);
            });
        }

        onRuleDropped(event) {
            if (event.to === event.from && event.newIndex === event.oldIndex) {
                // The user dropped the rule at its previous position
                return;
            }

            let placement = 'before';
            let neighbour = event.to.querySelector(':scope > :nth-child(' + (event.newIndex + 2) + ')');
            if (! neighbour) {
                // User dropped the rule at the end of a group
                placement = 'after';
                neighbour = event.to.querySelector(':scope > :nth-child(' + event.newIndex + ')')
            }

            let form = event.to.closest('form');
            // It's a submit element, the very first one, otherwise Icinga Web 2 sends another "structural-change"
            form.insertBefore(
                $.render('<input type="submit" name="structural-change[0]" value="move-rule:' + event.item.id + '">'),
                form.firstChild
            );
            form.appendChild($.render(
                '<input type="hidden" name="structural-change[1]" value="' + placement + ':' + neighbour.id + '">'
            ));

            $(form).trigger('submit');
        }
    }

    return SearchBar;
});
