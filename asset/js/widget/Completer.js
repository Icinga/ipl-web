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
            this.completedData = null;
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

        renderSuggestions(html) {
            let template = document.createElement('template');
            template.innerHTML = html;

            return template.content;
        }

        showSuggestions(suggestions, input) {
            this.termSuggestions.innerHTML = '';
            this.termSuggestions.appendChild(suggestions);
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
            this.completedData = null;
        }

        prepareCompletionData(input, data = null) {
            if (data === null) {
                data = { term: { ...input.dataset } };
                data.term.label = input.value;
            }

            let value = data.term.label;
            data.term.label = this.addWildcards(data.term.label);

            return [value, data];
        }

        addWildcards(value) {
            if (value.slice(0, 1) !== '*' && value.slice(-1) !== '*') {
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

        requestCompletion(input, data, continuous = false) {
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
                            let suggestions = this.renderSuggestions(req.responseText);

                            if (continuous) {
                                let options = suggestions.querySelectorAll('input');
                                if (options.length === 1 && options[0].value === this.completedValue) {
                                    this.complete(input, options[0].value, { ...options[0].dataset });
                                } else {
                                    this.showSuggestions(suggestions, input);
                                }
                            } else {
                                this.showSuggestions(suggestions, input);
                            }
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
            }
        }

        complete(input, value, data) {
            $(input).focus();

            if (this.instrumented) {
                $(input).trigger('completion', data);
            } else {
                input.value = value;
            }

            this.hideSuggestions();
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

                    let [value, data] = this.prepareCompletionData(input);
                    this.completedValue = value;
                    this.completedData.term = data.term;
                    this.requestCompletion(this.completedInput, this.completedData, true);
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

            this.complete(this.completedInput, input.value, { ...input.dataset });
        }

        onKeyDown(event) {
            let suggestions;

            switch (event.key) {
                case 'Tab':
                    suggestions = this.termSuggestions.querySelectorAll('input');
                    if (suggestions.length === 1) {
                        event.preventDefault();
                        let input = event.target;
                        let suggestion = suggestions[0];

                        this.complete(input, suggestion.value, { ...suggestion.dataset });
                    }
                    break;
                case 'Escape':
                    this.hideSuggestions();
                    break;
                case 'ArrowUp':
                    suggestions = this.termSuggestions.querySelectorAll('input');
                    if (suggestions.length) {
                        event.preventDefault();
                        this.moveToSuggestion(true);
                    }
                    break;
                case 'ArrowDown':
                    suggestions = this.termSuggestions.querySelectorAll('input');
                    if (suggestions.length) {
                        event.preventDefault();
                        this.moveToSuggestion();
                    }
                    break;
            }
        }

        onInput(event) {
            let input = event.target;

            let [value, data] = this.prepareCompletionData(input);
            this.completedInput = input;
            this.completedValue = value;
            this.completedData = data;
            this.requestCompletion(input, data);
        }

        onComplete(event) {
            let input = event.target;

            let [value, data] = this.prepareCompletionData(input, event.detail);
            this.completedInput = input;
            this.completedValue = value;
            this.completedData = data;

            if (typeof data.suggestions !== 'undefined') {
                this.showSuggestions(data.suggestions, input);
            } else {
                this.requestCompletion(input, data);
            }
        }
    }

    return Completer;
})(notjQuery));
