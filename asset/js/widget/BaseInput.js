(function (BaseInput) {

    "use strict";

    window["BaseInput"] = BaseInput;

})((function ($) {

    "use strict";

    class BaseInput {
        constructor(input) {
            this.input = input;
            this.separator = '';
            this.usedTerms = [];
            this.completer = null;
            this.lastCompletedTerm = null;
            this._termInput = null;
            this._termContainer = null;
        }

        get termInput() {
            if (this._termInput === null) {
                this._termInput = document.querySelector(this.input.dataset.termInput);
            }

            return this._termInput;
        }

        get termContainer() {
            if (this._termContainer === null) {
                this._termContainer = document.querySelector(this.input.dataset.termContainer);
            }

            return this._termContainer;
        }

        bind() {
            let $form = $(this.input.form);

            // Form submissions
            $form.on('submit', this.onSubmit, this);
            $form.on('click', 'button, input[type="submit"]', this.onButtonClick, this);

            // User interactions
            $form.on('input', '[data-label]', this.onInput, this);
            $form.on('keydown', '[data-label]', this.onKeyDown, this);
            $form.on('keyup', '[data-label]', this.onKeyUp, this);
            $form.on('focusout', '[data-index]', this.onTermBlur, this);

            // Copy/Paste
            $(this.input).on('paste', this.onPaste, this);
            $(this.input).on('copy', this.onCopyAndCut, this);
            $(this.input).on('cut', this.onCopyAndCut, this);

            // Should terms be completed?
            if (this.input.dataset.termCompletion) {
                if (this.completer === null) {
                    this.completer = new Completer(this.input, true);
                    this.completer.bind();
                }

                $form.on('suggestion', '[data-label]', this.onSuggestion, this);
                $form.on('completion', '[data-label]', this.onCompletion, this);
            }

            return this;
        }

        refresh(input) {
            if (input === this.input) {
                // If the DOM node is still the same, nothing has changed
                return;
            }

            this._termInput = null;
            this._termContainer = null;

            this.input = input;
            this.bind();

            if (this.completer !== null) {
                this.completer.refresh(input);
            }

            if (! this.restoreTerms()) {
                this.reset();
            }
        }

        reset() {
            this.usedTerms = [];
            this.lastCompletedTerm = null;

            this.togglePlaceholder();
            this.termInput.value = '';
            this.termContainer.innerHTML = '';
        }

        destroy() {
            this._termContainer = null;
            this._termInput = null;
            this.input = null;

            if (this.completer !== null) {
                this.completer.destroy();
                this.completer = null;
            }
        }

        restoreTerms() {
            if (this.hasTerms()) {
                this.usedTerms.forEach((termData, termIndex) => this.addTerm(termData, termIndex));
                this.togglePlaceholder();
                this.clearPartialTerm(this.input);
            } else {
                this.registerTerms();
                this.togglePlaceholder();
            }

            return this.hasTerms();
        }

        registerTerms() {
            this.termContainer.querySelectorAll('[data-index]').forEach((label) => {
                let termData = { ...label.dataset };
                delete termData.index;

                if (label.className) {
                    termData['class'] = label.className;
                }

                this.registerTerm(termData, label.dataset.index);
            });
        }

        registerTerm(termData, termIndex = null) {
            if (termIndex !== null) {
                this.usedTerms[termIndex] = termData;
                return termIndex;
            } else {
                return this.usedTerms.push(termData) - 1;
            }
        }

        clearPartialTerm(input) {
            if (this.completer !== null) {
                this.completer.reset();
            }

            this.writePartialTerm('', input);
        }

        writePartialTerm(value, input) {
            input.value = value;
            this.updateTermData({ label: value }, input);
        }

        readPartialTerm(input) {
            return input.value.trim();
        }

        readFullTerm(input, termIndex = null) {
            let value = this.readPartialTerm(input);
            if (! value) {
                return false;
            }

            let termData = {};

            if (termIndex !== null) {
                termData = { ...this.usedTerms[termIndex] };
            }

            termData.label = value;
            termData.search = value;

            if (this.lastCompletedTerm !== null) {
                if (termData.label === this.lastCompletedTerm.label) {
                    termData.search = this.lastCompletedTerm.search;
                    termData.class = this.lastCompletedTerm.class;
                }

                this.lastCompletedTerm = null;
            }

            return termData;
        }

        exchangeTerm() {
            let termData = this.readFullTerm(this.input);
            if (! termData) {
                return false;
            }

            this.addTerm(termData);
            this.clearPartialTerm(this.input);

            return true;
        }

        addTerm(termData, termIndex = null) {
            if (termIndex === null) {
                termIndex = this.registerTerm(termData);
            }

            let existingTerms = this.termInput.value;
            if (existingTerms) {
                existingTerms += this.separator;
            }

            existingTerms += termData.search;
            this.termInput.value = existingTerms;

            this.addRenderedTerm(this.renderTerm(termData, termIndex));
        }

        addRenderedTerm(label) {
            this.termContainer.appendChild(label);
        }

        hasTerms() {
            return this.usedTerms.length > 0;
        }

        saveTerm(input, updateDOM = true) {
            let termIndex = input.parentNode.dataset.index;
            let termData = this.readFullTerm(input, termIndex);

            // Only save if something has changed
            if (termData === false) {
                this.removeTerm(input.parentNode, updateDOM);
            } else if (this.usedTerms[termIndex].label !== termData.label) {
                this.usedTerms[termIndex] = termData;
                this.termInput.value = this.usedTerms.map(e => e.search).join(this.separator).trim();
                this.updateTermData(termData, input);
            }
        }

        updateTermData(termData, input) {
            let label = input.parentNode;
            label.dataset.label = termData.label;

            if (!! termData.search || termData.search === '') {
                label.dataset.search = termData.search;
            }
        }

        lastTerm() {
            if (! this.hasTerms()) {
                return null;
            }

            return this.usedTerms[this.usedTerms.length - 1];
        }

        popTerm() {
            let lastTermIndex = this.usedTerms.length - 1;
            return this.removeTerm(this.termContainer.querySelector(`[data-index="${ lastTermIndex }"]`));
        }

        removeTerm(label, updateDOM = true) {
            if (this.completer !== null) {
                this.completer.reset();
            }

            let termIndex = Number(label.dataset.index);

            // Re-index following remaining terms
            this.reIndexTerms(termIndex);

            // Cut the term's data
            let [termData] = this.usedTerms.splice(termIndex, 1);

            // Update the hidden input
            this.termInput.value = this.usedTerms.map(e => e.search).join(this.separator).trim();

            // Avoid saving the term, it's removed after all
            label.firstChild.skipSaveOnBlur = true;

            if (updateDOM) {
                // Remove it from the DOM
                this.removeRenderedTerm(label);
            }

            return termData;
        }

        removeRenderedTerm(label) {
            label.remove();
        }

        removeRange(labels) {
            let from = Number(labels[0].dataset.index);
            let to = Number(labels[labels.length - 1].dataset.index);
            let deleteCount = to - from + 1;

            if (to < this.usedTerms.length - 1) {
                // Only re-index if there's something left
                this.reIndexTerms(from, deleteCount);
            }

            this.usedTerms.splice(from, deleteCount);
            this.termInput.value = this.usedTerms.map(e => e.search).join(this.separator).trim();

            this.removeRenderedRange(labels);
        }

        removeRenderedRange(labels) {
            labels.forEach(label => this.removeRenderedTerm(label));
        }

        reIndexTerms(from, howMuch = 1) {
            for (let i = ++from; i < this.usedTerms.length; i++) {
                let label = this.termContainer.querySelector(`[data-index="${ i }"]`);
                label.dataset.index -= howMuch;
            }
        }

        complete(input, data) {
            if (this.completer !== null) {
                $(input).trigger('complete', data);
            }
        }

        selectTerms() {
            this.termContainer.querySelectorAll('[data-index]').forEach(el => el.classList.add('selected'));
        }

        deselectTerms() {
            this.termContainer.querySelectorAll('.selected').forEach(el => el.classList.remove('selected'));
        }

        clearSelectedTerms() {
            if (this.hasTerms()) {
                let labels = this.termContainer.querySelectorAll('.selected');
                if (labels.length) {
                    this.removeRange(Array.from(labels));
                }
            }
        }

        togglePlaceholder() {
            let placeholder = '';

            if (! this.hasTerms()) {
                if (this.input.dataset.placeholder) {
                    placeholder = this.input.dataset.placeholder;
                } else {
                    return;
                }
            } else if (this.input.placeholder) {
                if (! this.input.dataset.placeholder) {
                    this.input.dataset.placeholder = this.input.placeholder;
                }
            }

            this.input.placeholder = placeholder;
        }

        renderTerm(termData, termIndex) {
            let label = $('<label><input type="text"></label>').render();

            if (termData.class) {
                label.classList.add(termData.class);
            }

            label.dataset.label = termData.label;
            label.dataset.search = termData.search;
            label.dataset.index = termIndex;

            label.firstChild.value = termData.label;

            return label;
        }

        moveFocusForward(from = null) {
            let toFocus;

            let inputs = Array.from(this.termContainer.querySelectorAll('input'));
            if (from === null) {
                let focused = this.termContainer.querySelector('input:focus');
                from = inputs.indexOf(focused);
            }

            if (from === -1) {
                toFocus = inputs.shift();
            } else if (from + 1 < inputs.length) {
                toFocus = inputs[from + 1];
            } else {
                toFocus = this.input;
            }

            toFocus.selectionStart = toFocus.selectionEnd = 0;
            $(toFocus).focus();

            return toFocus;
        }

        moveFocusBackward(from = null) {
            let toFocus;

            let inputs = Array.from(this.termContainer.querySelectorAll('input'));
            if (from === null) {
                let focused = this.termContainer.querySelector('input:focus');
                from = inputs.indexOf(focused);
            }

            if (from === -1) {
                toFocus = inputs.pop();
            } else if (from > 0 && from - 1 < inputs.length) {
                toFocus = inputs[from - 1];
            } else {
                toFocus = this.input;
            }

            toFocus.selectionStart = toFocus.selectionEnd = toFocus.value.length;
            $(toFocus).focus();

            return toFocus;
        }

        /**
         * Event listeners
         */

        onSubmit(event) {
            // Register current input value, otherwise it's not submitted
            this.exchangeTerm();

            // Unset the input's name, to prevent its submission (It may actually have a name, as no-js fallback)
            this.input.name = '';

            // Enable the hidden input, otherwise it's not submitted
            this.termInput.disabled = false;
        }

        onSuggestion(event) {
            let data = event.detail;
            let input = event.target;

            let termData;
            if (typeof data === 'object') {
                termData = data;
            } else {
                termData = { label: data, search: data };
            }

            this.lastCompletedTerm = termData;
            this.writePartialTerm(termData.label, input);
        }

        onCompletion(event) {
            let input = event.target;
            let termData = event.detail;
            let isTerm = input.parentNode.dataset.index >= 0;

            this.lastCompletedTerm = termData;
            this.writePartialTerm(termData.label, input);

            if (! isTerm) {
                this.exchangeTerm();
                this.togglePlaceholder();
            }
        }

        onInput(event) {
            let input = event.target;
            let isTerm = input.parentNode.dataset.index >= 0;

            let termData = { label: this.readPartialTerm(input) };
            this.updateTermData(termData, input);
            this.complete(input, { term: termData });

            if (! isTerm) {
                this.clearSelectedTerms();
                this.togglePlaceholder();
            }
        }

        onKeyDown(event) {
            let input = event.target;
            let termIndex = input.parentNode.dataset.index;

            switch (event.key) {
                case ' ':
                    if (! this.readPartialTerm(input)) {
                        this.complete(input, { term: { label: '' } });
                        event.preventDefault();
                    }
                    break;
                case 'Backspace':
                    this.clearSelectedTerms();

                    if (termIndex >= 0) {
                        if (! input.value && this.removeTerm(input.parentNode) !== false) {
                            let previous = this.moveFocusBackward(Number(termIndex));
                            if (event.ctrlKey || event.metaKey) {
                                this.clearPartialTerm(previous);
                            } else {
                                this.writePartialTerm(previous.value.slice(0, -1), previous);
                            }

                            event.preventDefault();
                        }
                    } else {
                        if (! input.value && this.hasTerms()) {
                            let termData = this.popTerm();
                            if (! event.ctrlKey && ! event.metaKey) {
                                // Removing the last char programmatically is not
                                // necessary since the browser default is not prevented
                                this.writePartialTerm(termData.label, input);
                            }
                        }
                    }

                    this.togglePlaceholder();
                    break;
                case 'Delete':
                    this.clearSelectedTerms();

                    if (termIndex >= 0 && ! input.value && this.removeTerm(input.parentNode) !== false) {
                        let next = this.moveFocusForward(Number(termIndex) - 1);
                        if (event.ctrlKey || event.metaKey) {
                            this.clearPartialTerm(next);
                        } else {
                            this.writePartialTerm(next.value.slice(1), next);
                        }

                        event.preventDefault();
                    }

                    this.togglePlaceholder();
                    break;
                case 'Enter':
                    if (termIndex >= 0) {
                        this.saveTerm(input, false);
                    }
                    break;
                case 'ArrowLeft':
                    if (input.selectionStart === 0 && this.hasTerms()) {
                        event.preventDefault();
                        this.moveFocusBackward();
                    }
                    break;
                case 'ArrowRight':
                    if (input.selectionStart === input.value.length && this.hasTerms()) {
                        event.preventDefault();
                        this.moveFocusForward();
                    }
                    break;
            }
        }

        onKeyUp(event) {
            if (event.target.parentNode.dataset.index >= 0) {
                return;
            }

            switch (event.key) {
                case 'End':
                case 'ArrowLeft':
                case 'ArrowRight':
                    this.deselectTerms();
                    break;
                case 'Home':
                    if (event.shiftKey) {
                        this.selectTerms();
                    }
                    break;
                case 'Delete':
                    this.clearSelectedTerms();
                    this.togglePlaceholder();
                    break;
                case 'a':
                    if (event.ctrlKey || event.metaKey) {
                        this.selectTerms();
                    }
                    break;
            }
        }

        onTermBlur(event) {
            let input = event.target;
            // skipSaveOnBlur is set if the input is about to be removed anyway.
            // If saveTerm would remove the input as well, the other removal will fail
            // without any chance to handle it. (Element.remove() blurs the input)
            if (typeof input.skipSaveOnBlur === 'undefined' || ! input.skipSaveOnBlur) {
                this.saveTerm(input);
            }
        }

        onButtonClick(event) {
            if (! this.input.dataset.manageRequired) {
                return;
            }

            let button = event.currentTarget;
            if (button.type === 'submit' || (button.tagName === 'button' && ! button.type)) {
                if (this.hasTerms()) {
                    this.input.required = false;
                } else if (! this.input.required) {
                    this.input.required = true;
                }
            }
        }

        onPaste(event) {
            if (this.hasTerms()) {
                return;
            }

            this.termInput.value = event.clipboardData.getData('text/plain');
            $(this.input.form).trigger('submit');

            event.preventDefault();
        }

        onCopyAndCut(event) {
            if (! this.hasTerms()) {
                return;
            }

            let data = '';

            let selectedTerms = this.termContainer.querySelectorAll('.selected');
            if (selectedTerms.length) {
                data = Array.from(selectedTerms).map(label => label.dataset.search).join(this.separator);
            }

            if (this.input.selectionStart < this.input.selectionEnd) {
                data += this.separator + this.input.value.slice(this.input.selectionStart, this.input.selectionEnd);
            }

            event.clipboardData.setData('text/plain', data);
            event.preventDefault();

            if (event.type === 'cut') {
                this.clearPartialTerm(this.input);
                this.clearSelectedTerms();
                this.togglePlaceholder();
            }
        }
    }

    return BaseInput;
})(notjQuery));
