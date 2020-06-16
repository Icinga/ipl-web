/**
 * Completion - auto-completion of terms for forms
 */
(function (Completion) {

    "use strict";

    window["Completion"] = Completion;

})((function ($) {

    "use strict";

    var Completion = function (icinga, input) {
        /**
         * Supported logical operators
         *
         * The first is also the default.
         *
         * @type {String[]}
         */
        this.logical_operators = ['&', '|'];

        /**
         * Supported relational operators
         *
         * The first is also the default.
         *
         * @type {String[]}
         */
        this.relational_operators = ['=', '!=', '>', '<', '>=', '<='];

        /**
         * Yes, we also need Icinga (..)
         *
         * @type {{}}
         */
        this.icinga = icinga;

        /**
         * The input whose content is completed
         *
         * @type {{}}
         */
        this.input = input;

        /**
         * The mode this completion runs in
         *
         * @type {String}
         */
        this.mode = 'basic';

        /**
         * The currently running suggest XHR request
         *
         * @type {{}}
         */
        this.activeSuggestion = null;

        /**
         * The ID of the next suggest event
         *
         * @type {Number}
         */
        this.nextSuggestion = null;

        /**
         * If set, space characters are ignored until the set character is typed
         *
         * @type {String}
         */
        this.ignoreSpaceUntil = null;

        /**
         * The location of the character which is responsible for ignoring spaces
         *
         * @type {Number}
         */
        this.ignoreSpaceSince = null;

        /**
         * The searchTerm and termClass of the last completed term
         *
         * @type {{}}
         */
        this.lastCompletedTerm = null;

        /**
         * The current term type
         *
         * @type {String}
         */
        this.termType = null;

        /**
         * Whether to keep any used terms
         *
         * Set when submitting a form.
         *
         * @type {boolean}
         */
        this.keepUsedTerms = false;

        /**
         * A list of used terms
         *
         * @type {Array}
         */
        this.usedTerms = [];
    };

    /**
     * Bind this instance to its input and register event handlers
     *
     * @returns {Completion}
     */
    Completion.prototype.bind = function () {
        var $input = $(this.input);
        var $form = $input.closest('form');

        // Initializations
        this.mode = $input.data('term-completion') || this.mode;
        if (this.mode === 'full') {
            this.termType = 'column';
        }

        // Form submissions
        $form.on('submit', { self: this }, this.onSubmit);
        $form.on('change', 'input.autosubmit', { self: this }, this.onSubmit);
        $form.on('change', 'select.autosubmit', { self: this }, this.onSubmit);
        $form.on('click', 'button, input[type="submit"]', { self: this }, this.onButtonClick);

        // User interactions
        $form.on('keypress', '[data-term-input]', { self: this }, this.onInputKeyPress);
        $form.on('keydown', '[data-term-input]', { self: this }, this.onInputKeyDown);
        $form.on('keyup', '[data-term-input]', { self: this }, this.onInputKeyUp);
        $form.on('keydown', '[data-term]', { self: this }, this.onSuggestionKeyDown);
        $form.on('click', '[data-term]', { self: this }, this.onSuggestionClick);
        $form.on('click', '[data-term-index]', { self: this }, this.onTermClick);
        $form.on('keydown', '[data-term-index]', { self: this }, this.onTermKeyDown);

        // Ensure we'll survive
        $input.data('completion', this);

        return this;
    };

    /**
     * Reset the instance and its widget
     */
    Completion.prototype.reset = function () {
        // First the instance
        this.activeSuggestion = null;
        this.nextSuggestion = null;
        this.ignoreSpaceUntil = null;
        this.ignoreSpaceSince = null;
        this.lastCompletedTerm = null;
        this.usedTerms = [];

        // Then the widget
        this.updatePlaceholder();
        var $input = $(this.input);
        $($input.data('term-input')).val('');
        $($input.data('term-container')).html('');
        this.hideSuggestions($($input.data('term-suggestions')));
        $input.val('');
        $input[0].blur();
    };

    /**
     * Refresh the instance
     *
     * @param input
     */
    Completion.prototype.refresh = function (input) {
        if (input !== this.input) {
            this.input = input;
            this.bind();
        }

        if (! this.restoreTerms()) {
            this.reset();
        }
    };

    /**
     * Destroy the instance
     */
    Completion.prototype.destroy = function () {
        $(this.input).removeData('completion');
        this.input = null;
    };

    Completion.prototype.restoreTerms = function () {
        var $input = $(this.input);

        if (! $input.is('[data-reverse-term]')) {
            return false;
        }

        if (! this.keepUsedTerms) {
            this.usedTerms = [];
        } else {
            this.keepUsedTerms = false;
        }

        var _this = this;
        if (this.hasTerms()) {
            var $termContainer = $($input.data('term-container'));
            $.each(this.usedTerms, function (termIndex, termData) {
                _this.addTerm(termData, $termContainer, $input.data('term-input'), termIndex);
            });
            this.updatePlaceholder();
            $input.val('');
        } else if (this.mode === 'basic') {
            var terms = $input.val();
            if (! terms) {
                var params = this.icinga.utils.parseUrl($input.closest('.container').data('icingaUrl')).params;
                var param = params.find(function (p) {
                    return p.key === $input.prop('name')
                });
                if (typeof param !== 'undefined') {
                    terms = param.value;
                }
            }

            if (terms) {
                var matches = terms.match(new RegExp($input.data('term-splitter'), 'g'));
                $.each(matches, function (_, searchTerm) {
                    var termData = {
                        'class' : $input.data('term-default-class'),
                        'search': searchTerm,
                        'term'  : searchTerm
                    };
                    var parts = searchTerm.match(new RegExp($input.data('reverse-term')));
                    if (parts) {
                        if (parts[1]) {
                            termData.class = parts[1];
                        }
                        termData.term = parts[2];
                    }

                    _this.addTerm(termData, $input.data('term-container'), $input.data('term-input'));
                });
                this.updatePlaceholder();
                $input.val('');
            }
        }

        return true;
    };

    /**
     * @param event
     */
    Completion.prototype.onSubmit = function (event) {
        var _this = event.data.self;
        var $input = $(_this.input);

        // TODO: This omits incomplete quoted terms. Since it seems not to be possible to prevent submission
        // in this case we'll need some workaround here. Maybe using the incomplete term anyway?
        _this.exchangeTerm($input.data('term-container'), $input.data('term-input'));

        // Unset the input's name, to prevent its submission (It may actually have a name, as no-js fallback)
        $input.prop('name', '');

        if (_this.mode === 'full') {
            // Rewrite the form's action. There's no particular search parameter in full mode
            var $form = $(event.currentTarget).closest('form');
            var action = $form.attr('action');
            if (! action) {
                action = _this.icinga.loader.getLinkTargetFor($form).closest('.container').data('icingaUrl');
            }

            $form.attr('action', _this.icinga.utils.addUrlFlag(action, _this.usedTerms.map(function (e) {
                if (!! e.inactive) {
                    return '';
                } else {
                    return e.search;
                }
            }).join('')));
        } else {
            // Enable the hidden input, otherwise it's not submitted
            $($input.data('term-input')).prop('disabled', false);
        }

        // Reset all states, the user is about to navigate away
        _this.abort();
        _this.ignoreSpaceUntil = null;
        _this.ignoreSpaceSince = null;
        _this.lastCompletedTerm = null;

        // But keep any used terms (Otherwise reset in onRendered)
        _this.keepUsedTerms = true;
    };

    /**
     * @param event
     */
    Completion.prototype.onInputKeyPress = function (event) {
        var _this = event.data.self;
        var $input = $(_this.input);
        var termInput = $input.data('term-input');
        var termContainer = $input.data('term-container');

        _this.clearSelectedTerms(termContainer, termInput);
    };

    /**
     * @param event
     */
    Completion.prototype.onInputKeyDown = function (event) {
        var _this = event.data.self;
        var $input = $(_this.input);
        var termInput = $input.data('term-input');
        var termContainer = $input.data('term-container');
        var termSuggestions = $input.data('term-suggestions');

        switch (event.which) {
            case 32: // Spacebar
                if (_this.exchangeTerm(termContainer, termInput)) {
                    _this.hideSuggestions($(termSuggestions));
                    _this.updatePlaceholder();
                    return false;
                }
                break;
            case 8: // Backspace
                _this.clearSelectedTerms(termContainer, termInput);

                if (! $input.val()) {
                    _this.popTerm(termContainer, termInput);
                    _this.updatePlaceholder();
                }
                break;
            case 9: // Tab
                var $suggestion = $('[data-term]', termSuggestions);
                if ($suggestion.length === 1) {
                    event.preventDefault();
                    $suggestion = $suggestion.first();

                    _this.complete(
                        $suggestion.html().trim(),
                        $suggestion.prop('class'),
                        $suggestion.data('term')
                    );
                    _this.hideSuggestions($(termSuggestions));
                }
                break;
            case 37: // Arrow left
                if ($input[0].selectionStart === 0 && _this.hasTerms()) {
                    event.preventDefault();
                    _this.moveFocusBackward(termContainer);
                }
                break;
            case 39: // Arrow right
                if ($input[0].selectionStart === $input.val().length && _this.hasTerms()) {
                    event.preventDefault();
                    _this.moveFocusForward(termContainer);
                }
                break;
            case 38: // Arrow up
            case 40: // Arrow down
                if (! $(termSuggestions).is(':empty')) {
                    event.preventDefault();
                    if (event.which === 38) {
                        _this.moveFocusBackward(termSuggestions);
                    } else {
                        _this.moveFocusForward(termSuggestions);
                    }
                }
        }
    };

    /**
     * @param event
     */
    Completion.prototype.onInputKeyUp = function (event) {
        var _this = event.data.self;
        var $input = $(_this.input);
        var termInput = $input.data('term-input');
        var termContainer = $input.data('term-container');
        var termSuggestions = $input.data('term-suggestions');

        var term;

        switch (event.which) {
            case 8: // Backspace
                var $suggestions = $(termSuggestions);
                term = _this.readPartialTerm();
                if (term) {
                    _this.suggest($input.data('suggest-url'), _this.addWildcards(term), $suggestions);
                } else {
                    _this.hideSuggestions($suggestions);
                }
                break;
            case 27: // ESC
                _this.hideSuggestions($(termSuggestions));
                break;
            case 35: // End
            case 37: // ArrowLeft
            case 39: // ArrowRight
                _this.deselectTerms(termContainer);
                break;
            case 36: // HOME
                if (event.shiftKey) {
                    _this.selectTerms(termContainer);
                }
                break;
            case 9: // Tab
            case 16: // Shift
            case 32: // Spacebar
            case 38: // Arrow up
            case 40: // Arrow down
                break;  // Do not suggest anything
            case 50: // Double quote
            case 191: // Single quote
                if (event.shiftKey && _this.ignoreSpaceUntil === null) {
                    _this.ignoreSpaceUntil = event.key;
                    _this.ignoreSpaceSince = _this.input.selectionStart - 1;
                    break;
                }
            default:
                if (event.which === 65 && (event.ctrlKey || event.metaKey)) {  // a
                    _this.selectTerms(termContainer);
                    break;
                } else if (event.which === 46) {
                    _this.clearSelectedTerms(termContainer, termInput);
                    _this.updatePlaceholder();
                }

                term = _this.readPartialTerm();
                if (term) {
                    if (_this.mode === 'full' && _this.hasTerms()) {
                        if (_this.logical_operators.includes(term)) {
                            // Don't wait for user confirmation, exchange the term instantly if it's a logical operator
                            if (_this.exchangeTerm(termContainer, termInput, 'logical_operator')) {
                                _this.hideSuggestions($(termSuggestions));
                                _this.updatePlaceholder();
                                return;
                            }
                        } else if (_this.termType === 'column' && _this.lastTerm().type !== 'logical_operator') {
                            _this.addTerm(
                                {
                                    inactive: true,
                                    class: 'logical_operator',
                                    type: 'logical_operator',
                                    search: _this.logical_operators[0],
                                    term: _this.logical_operators[0]
                                },
                                termContainer,
                                termInput
                            );
                        }
                    }

                    _this.suggest($input.data('suggest-url'), _this.addWildcards(term), $(termSuggestions));
                } else {
                    _this.hideSuggestions($(termSuggestions));
                }
        }
    };

    /**
     * @param event
     */
    Completion.prototype.onSuggestionKeyDown = function (event) {
        var _this = event.data.self;

        switch (event.which) {
            case 9: // Tab
                event.preventDefault();
                var $el = $(event.currentTarget);
                var $input = $(_this.input);
                var term = $el.html().trim();

                _this.complete(term, $el.prop('class'), $el.data('term'));
                _this.suggest($input.data('suggest-url'), _this.addWildcards(term), $($input.data('term-suggestions')));
                _this.focusElement($input);
                break;
            case 37: // Arrow left
            case 38: // Arrow up
                event.preventDefault();
                _this.moveFocusBackward($(_this.input).data('term-suggestions'));
                break;
            case 39: // Arrow right
            case 40: // Arrow down
                event.preventDefault();
                _this.moveFocusForward($(_this.input).data('term-suggestions'));
                break;
        }
    };

    /**
     * @param event
     */
    Completion.prototype.onSuggestionClick = function (event) {
        var _this = event.data.self;
        var $el = $(event.currentTarget);
        var $input = $(_this.input);
        var term = $el.html().trim();

        _this.complete(term, $el.prop('class'), $el.data('term'));
        _this.exchangeTerm($input.data('term-container'), $input.data('term-input'));
        _this.hideSuggestions($($input.data('term-suggestions')));
        _this.updatePlaceholder();
        _this.focusElement($input);
    };

    /**
     * @param event
     */
    Completion.prototype.onTermClick = function (event) {
        var _this = event.data.self;
        var $input = $(_this.input);
        var $term = $(event.currentTarget);

        _this.removeTerm($term, $input.data('term-input'));
        _this.updatePlaceholder();
        _this.focusElement($input);
    };

    /**
     * @param event
     */
    Completion.prototype.onTermKeyDown = function (event) {
        var _this = event.data.self;

        switch (event.which) {
            case 37: // Arrow left
            case 39: // Arrow right
                event.preventDefault();
                if (event.which === 37) {
                    _this.moveFocusBackward($(_this.input).data('term-container'));
                } else {
                    _this.moveFocusForward($(_this.input).data('term-container'));
                }
        }
    };

    /**
     * @param event
     */
    Completion.prototype.onButtonClick = function (event) {
        var _this = event.data.self;

        var $input = $(_this.input);
        var $button = $(event.currentTarget);
        if ($button.prop('type') === 'submit' || ($button.is('button') && ! !!$button.prop('type'))) {
            if (! $input.is('[data-manage-required="false"]')) {
                if (_this.hasTerms()) {
                    $input.prop('required', false);
                } else if (! !!$input.prop('required')) {
                    $input.prop('required', true);
                }
            }
        }
    };

    /**
     * @param term
     * @param termClass
     * @param searchTerm
     */
    Completion.prototype.complete = function (term, termClass, searchTerm) {
        this.lastCompletedTerm = { 'search': searchTerm, 'class': termClass };
        this.writePartialTerm(term);
    };

    /**
     * @param term
     */
    Completion.prototype.writePartialTerm = function (term) {
        if (this.ignoreSpaceUntil !== null && this.ignoreSpaceSince === 0) {
            term = this.ignoreSpaceUntil + term;
        }

        $(this.input).val(term);
    };

    /**
     * @returns {string}
     */
    Completion.prototype.readPartialTerm = function () {
        var term = this.readFullTerm();
        if (this.ignoreSpaceUntil !== null && this.ignoreSpaceSince === 0) {
            term = term.slice(1);
        }

        return term;
    };

    /**
     * @returns {string}
     */
    Completion.prototype.readFullTerm = function () {
        return $(this.input).val().trim();
    };

    /**
     * @param   termContainer
     * @param   termInput
     * @param   termType
     * @returns {boolean}
     */
    Completion.prototype.exchangeTerm = function (termContainer, termInput, termType) {
        var newTerm = this.readFullTerm();
        if (! newTerm) {
            return false;
        } else if (this.ignoreSpaceUntil !== null && newTerm[this.ignoreSpaceSince] === this.ignoreSpaceUntil) {
            if (newTerm.length - 1 === this.ignoreSpaceSince
                || newTerm.slice(-1) !== this.ignoreSpaceUntil
                || (this.ignoreSpaceSince === 0
                    && (newTerm.length < 2
                        || newTerm.slice(0, 1) !== this.ignoreSpaceUntil))
            ) {
                return false;
            }
        }

        this.abort();

        if (typeof termType === 'undefined') {
            termType = this.termType;
        }

        var $input = $(this.input);
        var termData = {
            'class': $input.data('term-default-class'),
            'type': termType,
            'search': newTerm,
            'term': newTerm
        };

        if (this.ignoreSpaceUntil !== null) {
            if (this.ignoreSpaceSince === 0 && newTerm[this.ignoreSpaceSince] === this.ignoreSpaceUntil) {
                termData.term = newTerm.slice(1, -1);
            }

            this.ignoreSpaceUntil = null;
            this.ignoreSpaceSince = null;
        } else if (this.lastCompletedTerm !== null) {
            termData.class = this.lastCompletedTerm.class;
            termData.search = this.lastCompletedTerm.search;
            this.lastCompletedTerm = null;
        }

        this.activateLastTerm(termContainer);
        this.addTerm(termData, termContainer, termInput);
        $input.val('');

        if (termType === 'column') {
            this.addTerm(
                {
                    inactive: true,
                    class: 'operator',
                    type: 'operator',
                    search: this.relational_operators[0],
                    term: this.relational_operators[0]
                },
                termContainer,
                termInput
            );
        }

        return true;
    };

    /**
     * @param termData
     * @param termContainer
     * @param termInput
     * @param termIndex
     */
    Completion.prototype.addTerm = function (termData, termContainer, termInput, termIndex) {
        if (typeof termIndex === 'undefined') {
            termIndex = this.usedTerms.push(termData) - 1;
        }

        var $termInput = $(termInput);
        var existingTerms = $termInput.val();
        if (existingTerms) {
            existingTerms += ' ';
        }

        existingTerms += termData.search;
        $termInput.val(existingTerms);

        var html = '<button type="button"';
        html += ' data-term-index="' + termIndex + '"';
        if (termData.class) {
            html += ' class="' + termData.class;
            if (!! termData.inactive) {
                html += ' inactive';
            }
            html += '"';
        }
        html += '>' + this.icinga.utils.escape(termData.term) + '</button>';

        $(termContainer).append(html);

        var $term = $('[data-term-index="' + termIndex + '"]', termContainer);
        if ($term[0].scrollWidth > $term.innerWidth()) {
            // Term label overflowed
            $term.prop('title', termData.term);
        }

        if (termData.type !== null) {
            this.nextTermType(termData.type);
        }
    };

    /**
     * @return {boolean}
     */
    Completion.prototype.hasTerms = function () {
        return this.usedTerms.length > 0;
    };

    /**
     * @return  {{}}
     */
    Completion.prototype.lastTerm = function () {
        if (! this.hasTerms()) {
            return null;
        }

        return this.usedTerms[this.usedTerms.length - 1];
    };

    /**
     * @param   termContainer
     */
    Completion.prototype.activateLastTerm = function (termContainer) {
        var lastTerm = this.lastTerm();
        if (lastTerm !== null && !! lastTerm.inactive) {
            lastTerm.inactive = false;
            $('[data-term-index=' + (this.usedTerms.length - 1) + ']', termContainer).removeClass('inactive');
        }
    };

    /**
     * @param   termContainer
     * @param   termInput
     */
    Completion.prototype.popTerm = function (termContainer, termInput) {
        var $term = $('button', termContainer).last();
        if ($term.length) {
            this.removeTerm($term, termInput);
        }
    };

    /**
     * @param $term
     * @param termInput
     */
    Completion.prototype.removeTerm = function ($term, termInput) {
        var $termInput = $(termInput);
        var terms = $termInput.val();

        var termData = this.usedTerms.splice($term.data('term-index'), 1)[0];
        $term.nextAll().each(function (_, el) {
            $(el).data('term-index', $(el).data('term-index') - 1);
        });

        var searchPattern = termData.search.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
        terms = terms.replace(new RegExp('(^|\\s)' + searchPattern + '($|\\s)'), ' ');
        $termInput.val(terms.trim());
        $term.remove();

        if (this.mode === 'full') {
            if (this.hasTerms()) {
                this.nextTermType(this.lastTerm().type);
            } else {
                this.termType = 'column';
            }
        }
    };

    /**
     * @param   termContainer
     */
    Completion.prototype.selectTerms = function (termContainer) {
        $('button', termContainer).addClass('selected');
    };

    /**
     * @param   termContainer
     */
    Completion.prototype.deselectTerms = function (termContainer) {
        $('button.selected', termContainer).removeClass('selected');
    };

    /**
     * @param   termContainer
     * @param   termInput
     */
    Completion.prototype.clearSelectedTerms = function (termContainer, termInput) {
        var _this = this;
        $('button.selected', termContainer).each(function (_, el) {
            _this.removeTerm($(el), termInput);
        });
    };

    /**
     * @param   data
     * @param   $suggestions
     */
    Completion.prototype.showSuggestions = function (data, $suggestions) {
        $suggestions.html(data);

        var $input = $(this.input),
            inputPos = $input.position(),
            inputWidth = $input.outerWidth(true),
            suggestionWidth = $suggestions.outerWidth(true);
        if (inputPos.left + suggestionWidth > inputPos.left + inputWidth) {
            $suggestions.css({left: inputPos.left + inputWidth - suggestionWidth});
        } else {
            $suggestions.css({left: inputPos.left});
        }

        $suggestions.show();
    };

    /**
     * @param   $suggestions
     */
    Completion.prototype.hideSuggestions = function ($suggestions) {
        $suggestions.hide();
        $suggestions.html('');
    };

    /**
     * @param   term
     * @returns {String}
     */
    Completion.prototype.addWildcards = function (term) {
        if (term.slice(0, 1) !== '*' && term.slice(-1) !== '*') {
            return term + '*';
        }

        return term;
    };

    Completion.prototype.abort = function () {
        if (this.activeSuggestion !== null) {
            this.activeSuggestion.abort();
            this.activeSuggestion = null;
        }
        if (this.nextSuggestion !== null) {
            clearTimeout(this.nextSuggestion);
            this.nextSuggestion = null;
        }
    };

    /**
     * @param   url
     * @param   query
     * @param   $target
     */
    Completion.prototype.suggest = function (url, query, $target) {
        this.abort();

        var self = this;
        this.nextSuggestion = setTimeout(function () {
            var data = {};
            var suggestParameter;

            if (self.mode === 'full') {
                suggestParameter = self.termType;
            } else {
                suggestParameter = $($(self.input).data('term-input')).prop('name');
            }

            data[suggestParameter] = query;
            if (self.hasTerms() && self.mode === 'basic') {
                data['!' + suggestParameter] = self.usedTerms.map(function (e) { return e.term}).join();
            }

            var headers = { 'X-Icinga-WindowId': self.icinga.ui.getWindowId() };
            var containerId = self.icinga.ui.getUniqueContainerId($target);
            if (containerId) {
                headers['X-Icinga-WindowId'] += '_' + containerId;
            }

            var req = $.ajax({
                type    : 'GET',
                headers : headers,
                url     : this.icinga.utils.addUrlParams(url, data),
                context : self
            });

            req.$target = $target;
            req.done(self.onCompletionResponse);
            req.fail(self.onCompletionFailure);
            req.always(self.onCompletionComplete);

            self.activeSuggestion = req;
        }, 200);
    };

    /**
     * @param data
     * @param textStatus
     * @param req
     */
    Completion.prototype.onCompletionResponse = function (data, textStatus, req) {
        if (data && this.readPartialTerm()) {
            this.showSuggestions(data, req.$target);
        } else {
            this.hideSuggestions(req.$target);
        }
    };

    /**
     * @param req
     * @param textStatus
     * @param errorThrown
     */
    Completion.prototype.onCompletionFailure = function (req, textStatus, errorThrown) {
        if (textStatus !== 'abort') {
            this.showSuggestions(errorThrown, req.$target);
        }
    };

    Completion.prototype.onCompletionComplete = function () {
        this.activeSuggestion = null;
        this.nextSuggestion = null;
    };

    Completion.prototype.moveFocusForward = function (where) {
        // TODO: Use are more flexible selector than button (:focusable is jQuery UI only..)
        var $focused = $('button:focus', where);

        if ($focused.length) {
            var $buttons = $('button', where);
            var next = $buttons.get($buttons.index($focused) + 1);
            if (next) {
                this.focusElement($(next));
            } else {
                this.focusElement($(this.input));
            }
        } else {
            this.focusElement($('button', where).first());
        }
    };

    Completion.prototype.moveFocusBackward = function (where) {
        // TODO: Use are more flexible selector than button (:focusable is jQuery UI only..)
        var $focused = $('button:focus', where);

        if ($focused.length) {
            var $buttons = $('button', where);
            if ($buttons.index($focused) > 0) {
                this.focusElement($($buttons.get($buttons.index($focused) - 1)));
            } else {
                this.focusElement($(this.input));
            }
        } else {
            this.focusElement($('button', where).last());
        }
    };

    Completion.prototype.updatePlaceholder = function () {
        var $input = $(this.input);
        if (! this.hasTerms()) {
            if ($input.data('placeholder')) {
                $input.prop('placeholder', $input.data('placeholder'));
            }
        } else if (!! $input.prop('placeholder')) {
            $input.data('placeholder', $input.prop('placeholder'));
            $input.prop('placeholder', '');
        }
    };

    Completion.prototype.focusElement = function ($element) {
        if ($element.length) {
            $element[0].focus();
        }
    };

    Completion.prototype.nextTermType = function (type) {
        switch (type) {
            case 'column':
                this.termType = 'operator';
                break;
            case 'operator':
                this.termType = 'value';
                break;
            case 'value':
            case 'logical_operator':
                this.termType = 'column';
                break;
        }

        return this.termType;
    };

    return Completion;

})(jQuery));
