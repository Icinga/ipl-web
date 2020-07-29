(function (Completer) {

    "use strict";

    window["Completer"] = Completer;

})((function ($) {

    "use strict";

    class Completer {
        constructor(input, instrumented = false) {
            this.input = input;
            this.instrumented = instrumented;
            this.nextSuggestion = null;
            this.activeSuggestion = null;
            this.completedInput = null;
            this.completedValue = null;
            this._termSuggestions = null;
        }

        get termSuggestions() {
            if (this._termSuggestions === null) {
                this._termSuggestions = document.querySelector(this.input.dataset.termSuggestions);
            }

            return this._termSuggestions;
        }

        bind() {
            let $form = $(this.input.form);
            let termSuggestions = this.input.dataset.termSuggestions;

            // Form submissions
            $form.on('submit', this.onSubmit, this);

            // User interactions
            $form.on('focusin', 'input[type="text"]', this.onFocus, this);
            $form.on('keydown', 'input[type="text"]', this.onKeyDown, this);
            $form.on('click', termSuggestions + ' input[type="button"]', this.onSuggestionClick, this);
            $form.on('keydown', termSuggestions + ' input[type="button"]', this.onSuggestionKeyDown, this);

            if (this.instrumented) {
                $form.on('complete', 'input[type="text"]', this.onComplete, this);
            } else {
                $form.on('input', 'input[type="text"]', this.onInput, this);
            }

            return this;
        }

        refresh(input) {
            if (input === this.input) {
                // If the DOM node is still the same, nothing has changed
                return;
            }

            this._termSuggestions = null;
            this.abort();

            this.input = input;
            this.bind();
        }

        reset() {
            this.abort();
            this.hideSuggestions();
        }

        destroy() {
            this._termSuggestions = null;
            this.input = null;
        }

        showSuggestions(html, input) {
            this.termSuggestions.innerHTML = html;
            this.termSuggestions.style.display = '';

            let formRect = input.form.getBoundingClientRect();
            let inputPosX = input.getBoundingClientRect().left - formRect.left;
            let suggestionWidth = this.termSuggestions.offsetWidth;

            if (inputPosX + suggestionWidth > formRect.right - formRect.left) {
                this.termSuggestions.style.left = `${ inputPosX + input.offsetWidth - suggestionWidth }px`;
            } else {
                this.termSuggestions.style.left = `${ inputPosX }px`;
            }
        }

        hideSuggestions() {
            this.termSuggestions.style.display = 'none';
            this.termSuggestions.innerHTML = '';

            this.completedInput = null;
            this.completedValue = null;
        }

        addWildcards(value) {
            if (value && value.slice(0, 1) !== '*' && value.slice(-1) !== '*') {
                return value + '*';
            }

            return value;
        }

        abort() {
            if (this.activeSuggestion !== null) {
                this.activeSuggestion.abort();
                this.activeSuggestion = null;
            }

            if (this.nextSuggestion !== null) {
                clearTimeout(this.nextSuggestion);
                this.nextSuggestion = null;
            }
        }

        requestCompletion(data, input) {
            this.abort();

            this.nextSuggestion = setTimeout(() => {
                let req = new XMLHttpRequest();
                req.open('POST', this.input.dataset.suggestUrl, true);
                req.setRequestHeader('Content-Type', 'application/json');

                if (typeof icinga !== 'undefined') {
                    let windowId = icinga.ui.getWindowId();
                    let containerId = icinga.ui.getUniqueContainerId(this.termSuggestions);
                    if (containerId) {
                        req.setRequestHeader('X-Icinga-WindowId', windowId + '_' + containerId);
                    } else {
                        req.setRequestHeader('X-Icinga-WindowId', windowId);
                    }
                }

                req.addEventListener('loadend', () => {
                    if (req.readyState > 0) {
                        if (req.responseText) {
                            this.showSuggestions(req.responseText, input);
                        } else {
                            this.hideSuggestions();
                        }
                    }

                    this.activeSuggestion = null;
                    this.nextSuggestion = null;
                });

                req.send(JSON.stringify(data));

                this.activeSuggestion = req;
            }, 200);
        }

        suggest(input, value, data = null) {
            if (this.instrumented) {
                if (data === null) {
                    data = value;
                }

                $(input).trigger('suggestion', data);
            } else {
                input.value = value;

                data[input.name] = this.addWildcards(value);
                this.requestCompletion(data, input);
                this.completedValue = value;
            }
        }

        complete(input, value, data) {
            if (this.instrumented) {
                $(input).trigger('completion', data);
            } else {
                input.value = value;
            }
        }

        moveToSuggestion(backwards = false) {
            let focused = this.termSuggestions.querySelector('input:focus');
            let inputs = Array.from(this.termSuggestions.querySelectorAll('input'));

            let input;
            if (focused !== null) {
                let sibling = inputs[backwards ? inputs.indexOf(focused) - 1 : inputs.indexOf(focused) + 1];
                if (sibling) {
                    input = sibling;
                } else {
                    input = this.completedInput;
                }
            } else {
                input = inputs[backwards ? inputs.length - 1 : 0];
            }

            $(input).focus();

            if (this.completedValue !== null) {
                if (input === this.completedInput) {
                    this.suggest(this.completedInput, this.completedValue);
                } else {
                    this.suggest(this.completedInput, input.value, { ...input.dataset });
                }
            }

            return input;
        }

        /**
         * Event listeners
         */

        onSubmit(event) {
            // Reset all states, the user is about to navigate away
            this.reset();
        }

        onFocus(event) {
            let input = event.target;

            if (input !== this.completedInput) {
                this.hideSuggestions();
            }
        }

        onSuggestionKeyDown(event) {
            switch (event.key) {
                case 'Tab':
                    event.preventDefault();
                    let input = event.target;

                    $(this.completedInput).focus();
                    this.suggest(this.completedInput, input.value, { ...input.dataset });
                    break;
                case 'ArrowLeft':
                case 'ArrowUp':
                    event.preventDefault();
                    this.moveToSuggestion(true);
                    break;
                case 'ArrowRight':
                case 'ArrowDown':
                    event.preventDefault();
                    this.moveToSuggestion();
                    break;
            }
        }

        onSuggestionClick(event) {
            let input = event.target;

            $(this.completedInput).focus();
            this.complete(this.completedInput, input.value, { ...input.dataset });

            this.hideSuggestions();
        }

        onKeyDown(event) {
            switch (event.key) {
                case 'Tab':
                    if (this.termSuggestions.childNodes.length === 1) {
                        event.preventDefault();
                        let input = event.target;
                        let suggestion = this.termSuggestions.firstChild;

                        $(input).focus();
                        this.complete(input, suggestion.value, { ...suggestion.dataset });
                        this.hideSuggestions();
                    }
                    break;
                case 'Escape':
                    this.hideSuggestions();
                    break;
                case 'ArrowUp':
                    if (this.termSuggestions.childNodes.length) {
                        event.preventDefault();
                        this.moveToSuggestion(true);
                    }
                    break;
                case 'ArrowDown':
                    if (this.termSuggestions.childNodes.length) {
                        event.preventDefault();
                        this.moveToSuggestion();
                    }
                    break;
            }
        }

        onInput(event) {
            let input = event.target;

            let value = input.value;
            if (value) {
                let data = { ...input.dataset };
                data[input.name] = this.addWildcards(value);

                this.requestCompletion(data, input);
                this.completedInput = input;
                this.completedValue = value;
            } else {
                this.hideSuggestions();
            }
        }

        onComplete(event) {
            let data = event.detail;
            let input = event.target;

            if (data.label === '') {
                this.hideSuggestions();
            } else {
                if (typeof data.label !== 'undefined') {
                    this.completedValue = data.label;
                    data.label = this.addWildcards(data.label);
                } else {
                    this.completedValue = '';
                }

                this.completedInput = input;
                this.requestCompletion(data, input);
            }
        }
    }

    return Completer;
})(notjQuery));
