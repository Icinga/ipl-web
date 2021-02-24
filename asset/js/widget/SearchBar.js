define(["../notjQuery"], function ($) {

    "use strict";

    class SearchBar {
        constructor(form) {
            this.form = form;
            this.filterInput = null;
            this._editorContainer = null;
        }

        bind() {
            $(this.form).on('click', '[data-search-editor-url]', this.onOpenerClick, this);

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
                }
            });

            req.send();
        }
    }

    return SearchBar;
});
