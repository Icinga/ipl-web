(function (FilterInput) {

    "use strict";

    window["FilterInput"] = FilterInput;

})((function (BaseInput, $) {

    "use strict";

    class FilterInput extends BaseInput {
        /**
         * Supported grouping operators
         *
         * @type {{close: {}, open: {}}}
         */
        grouping_operators = {
            open: { label: '(', search: '(', class: 'grouping_operator_open', type: 'grouping_operator' },
            close: { label: ')', search: ')', class: 'grouping_operator_close', type: 'grouping_operator' }
        };

        /**
         * Supported logical operators
         *
         * The first is also the default.
         *
         * @type {{}[]}
         */
        logical_operators = [
            { label: '&', search: '&', class: 'logical_operator', type: 'logical_operator' },
            { label: '|', search: '|', class: 'logical_operator', type: 'logical_operator' },
        ];

        /**
         * Supported relational operators
         *
         * The first is also the default.
         *
         * @type {{}[]}
         */
        relational_operators = [
            { label: '=', search: '=', class: 'operator', type: 'operator' },
            { label: '!=', search: '!=', class: 'operator', type: 'operator' },
            { label: '>', search: '>', class: 'operator', type: 'operator' },
            { label: '<', search: '<', class: 'operator', type: 'operator' },
            { label: '>=', search: '>=', class: 'operator', type: 'operator' },
            { label: '<=', search: '<=', class: 'operator', type: 'operator' }
        ];

        constructor(input) {
            super(input)

            this.termType = 'column';
            this.previewedTerm = null;
            this._currentGroup = null;
        }

        set currentGroup(value) {
            if (value !== this.termContainer) {
                this._currentGroup = value;
            } else {
                this._currentGroup = null;
            }
        }

        get currentGroup() {
            if (this._currentGroup !== null) {
                return this._currentGroup;
            }

            return this.termContainer;
        }

        bind() {
            $(this.termContainer).on('focusin', '[data-index]', this.onTermFocus, this);
            $(this.termContainer).on('click', '[data-group-type="condition"] > button', this.onRemoveCondition, this);
            return super.bind();
        }

        reset() {
            super.reset();

            this.termType = 'column';
            this.previewedTerm = null;
            this._currentGroup = null;
        }

        destroy() {
            super.destroy();

            this._currentGroup = null;
        }

        restoreTerms() {
            this._currentGroup = null;

            if (super.restoreTerms()) {
                this.reportValidity(this.input.form);
                return true;
            }

            return false;
        }

        registerTerms() {
            super.registerTerms();

            if (this.hasTerms()) {
                this.termType = this.nextTermType(this.lastTerm());
                this.togglePreview();
            }
        }

        registerTerm(termData, termIndex = null) {
            termIndex = super.registerTerm(termData, termIndex);

            if (termData.type === 'grouping_operator' && typeof termData.counterpart === 'undefined') {
                let counterpart;
                if (this.isGroupOpen(termData)) {
                    counterpart = this.nextPendingGroupClose(termIndex);
                } else {
                    counterpart = this.lastPendingGroupOpen(termIndex);
                }

                if (counterpart !== null) {
                    termData.counterpart = counterpart;
                    this.usedTerms[counterpart].counterpart = termIndex;
                }
            }

            return termIndex;
        }

        readFullTerm(input, termIndex = null) {
            let termData = super.readFullTerm(input, termIndex);
            if (termData === false) {
                return false;
            }

            if (! termData.type) {
                termData.type = this.termType;
            }

            if (termData.type === 'column' || termData.type === 'value') {
                termData.search = this.escapeExpression(termData.search);
            }

            return termData;
        }

        insertTerm(termData, termIndex) {
            let label = super.insertTerm(termData, termIndex);

            if (termIndex === this.usedTerms.length - 1) {
                this.termType = this.nextTermType(termData);
                this.togglePreview();
            } else {
                let next = this.termContainer.querySelector(`[data-index="${ termIndex + 1 }"]`);
                this.checkValidity(next.firstChild, next.dataset.type, termIndex + 1);
            }

            return label;
        }

        insertRenderedTerm(label) {
            if (label.dataset.counterpart >= 0) {
                let otherLabel = this.termContainer.querySelector(`[data-index="${ label.dataset.counterpart }"]`);
                if (otherLabel !== null) {
                    otherLabel.dataset.counterpart = label.dataset.index;
                    this.checkValidity(otherLabel.firstChild);
                }
            }

            let previous = this.termContainer.querySelector(`[data-index="${ label.dataset.index - 1 }"]`);
            switch (label.dataset.type) {
                case 'column':
                    let newCondition = this.renderCondition();
                    newCondition.appendChild(label);

                    previous.parentNode.insertBefore(newCondition, previous.nextSibling);
                    break;
                case 'operator':
                case 'value':
                    previous.parentNode.appendChild(label);
                    break;
                case 'logical_operator':
                    if (previous.parentNode.dataset.groupType === 'condition') {
                        previous.parentNode.parentNode.insertBefore(label, previous.parentNode.nextSibling);
                    } else {
                        previous.parentNode.insertBefore(label, previous.nextSibling);
                    }

                    break;
                case 'grouping_operator':
                    if (this.isGroupOpen(label.dataset)) {
                        if (label.dataset.counterpart >= 0) {
                            let counterpart = this.termContainer.querySelector(
                                `[data-index="${ label.dataset.counterpart }"]`
                            );
                            counterpart.parentNode.insertBefore(label, counterpart.parentNode.firstChild);
                        } else {
                            let newGroup = this.renderChain();
                            newGroup.appendChild(label);

                            let sibling = previous.nextSibling;
                            while (sibling !== null && sibling.dataset.type !== 'grouping_operator') {
                                let nextSibling = sibling.nextSibling;
                                newGroup.appendChild(sibling);
                                sibling = nextSibling;
                            }

                            previous.parentNode.insertBefore(newGroup, previous.nextSibling);
                        }
                    } else {
                        this.termContainer.querySelector(
                            `[data-index="${ label.dataset.counterpart }"]`
                        ).parentNode.appendChild(label);
                    }
            }

            return label;
        }

        addTerm(termData, termIndex = null) {
            super.addTerm(termData, termIndex);

            if (termData.counterpart >= 0) {
                let otherLabel = this.termContainer.querySelector(`[data-index="${ termData.counterpart }"]`);
                if (otherLabel !== null) {
                    otherLabel.dataset.counterpart = termIndex || this.usedTerms[termData.counterpart].counterpart;
                    this.checkValidity(otherLabel.firstChild);
                }
            }

            this.termType = this.nextTermType(termData);
            this.togglePreview();
        }

        addRenderedTerm(label) {
            let newGroup = null;
            let leaveGroup = false;

            switch (label.dataset.type) {
                case 'column':
                    newGroup = this.renderCondition();
                    break;
                case 'value':
                    leaveGroup = true;
                    break;
                case 'logical_operator':
                    if (this.currentGroup.dataset.groupType === 'condition') {
                        this.currentGroup = this.currentGroup.parentNode;
                    }

                    break;
                case 'grouping_operator':
                    if (this.isGroupOpen(label.dataset)) {
                        newGroup = this.renderChain();
                    } else {
                        if (this.currentGroup.dataset.groupType === 'condition') {
                            this.currentGroup = this.currentGroup.parentNode;
                        }

                        leaveGroup = true;
                    }
            }

            if (newGroup !== null) {
                newGroup.appendChild(label);
                this.currentGroup.appendChild(newGroup);
                this.currentGroup = newGroup;
            } else {
                this.currentGroup.appendChild(label);
            }

            if (leaveGroup) {
                this.currentGroup = this.currentGroup.parentNode;
            }
        }

        saveTerm(input, updateDOM = true) {
            if (! this.checkValidity(input)) {
                return false;
            }

            return super.saveTerm(input, updateDOM);
        }

        removeTerm(label, updateDOM = true) {
            let termIndex = Number(label.dataset.index);
            if (termIndex < this.usedTerms.length - 1) {
                // It's not the last term
                if (! this.checkValidity(label.firstChild, label.dataset.type, termIndex)) {
                    this.reportValidity(label.firstChild);
                    return false;
                }
            }

            let termData = super.removeTerm(label, updateDOM);

            if (this.hasTerms()) {
                if (termIndex === this.usedTerms.length) {
                    // It's been the last term
                    this.termType = this.nextTermType(this.lastTerm());
                }

                if (termData.counterpart >= 0) {
                    let otherLabel = this.termContainer.querySelector(`[data-index="${ termData.counterpart }"]`);
                    delete this.usedTerms[otherLabel.dataset.index].counterpart;
                    delete otherLabel.dataset.counterpart;
                    this.checkValidity(otherLabel.firstChild);
                }
            } else {
                this.termType = 'column';
            }

            this.togglePreview();
            return termData;
        }

        removeRange(labels) {
            super.removeRange(labels);

            if (this.hasTerms()) {
                this.termType = this.nextTermType(this.lastTerm());

                labels.forEach((label) => {
                    if (label.dataset.counterpart >= 0) {
                        let otherLabel = this.termContainer.querySelector(
                            `[data-counterpart="${ label.dataset.index }"]`
                        );
                        if (otherLabel !== null) {
                            delete this.usedTerms[otherLabel.dataset.index].counterpart;
                            delete otherLabel.dataset.counterpart;
                            this.checkValidity(otherLabel.firstChild);
                        }
                    }
                });
            } else {
                this.termType = 'column';
            }

            this.togglePreview();
        }

        removeRenderedTerm(label) {
            let parent = label.parentNode;
            let children = parent.querySelectorAll(':scope > [data-index], :scope > [data-group-type]');
            if (parent.dataset.groupType && children.length === 1) {
                if (this.currentGroup === parent) {
                    this.currentGroup = parent.parentNode;
                }

                // If the parent is a group and the label is the only child, we can remove the entire group
                label.firstChild.skipSaveOnBlur = true;
                parent.remove();
            } else {
                if (label.dataset.index >= this.usedTerms.length) {
                    // It's been the last term
                    switch (label.dataset.type) {
                        case 'grouping_operator':
                            if (this.isGroupClose(label.dataset)) {
                                // TODO: This should be done on demand, get rid of currentGroup please..
                                let groupOpenAt = this.lastPendingGroupOpen(Number(label.dataset.index));
                                if (groupOpenAt) {
                                    let groupOpen = this.termContainer.querySelector(`[data-index="${ groupOpenAt }"]`);
                                    this.currentGroup = groupOpen.parentNode;
                                    break;
                                }
                            }
                        case 'operator':
                        case 'value':
                            this.currentGroup = parent;
                    }
                }

                super.removeRenderedTerm(label);

                if (parent.dataset.groupType === 'chain') {
                    // Get a new nodes list first, otherwise the removed label is still part of it
                    children = parent.querySelectorAll(':scope > [data-index], :scope > [data-group-type]');
                    let hasNoGroupOperators = children[0].dataset.type !== 'grouping_operator'
                        && children[children.length - 1].dataset.type !== 'grouping_operator';
                    if (hasNoGroupOperators) {
                        if (this.currentGroup === parent) {
                            this.currentGroup = parent.parentNode;
                        }

                        // Unwrap remaining terms, remove the resulting empty group
                        Array.from(children).forEach(child => parent.parentNode.insertBefore(child, parent));
                        parent.remove();
                    }
                }
            }
        }

        removeRenderedRange(labels) {
            let to = Number(labels[labels.length - 1].dataset.index);

            while (labels.length) {
                let label = labels.shift();
                let parent = label.parentNode;
                if (parent.dataset.groupType && label === parent.firstChild) {
                    let counterpartIndex = Number(label.dataset.counterpart);
                    if (isNaN(counterpartIndex)) {
                        counterpartIndex = Number(
                            Array.from(parent.querySelectorAll(':scope > [data-index]')).pop().dataset.index
                        );
                    }

                    if (counterpartIndex <= to) {
                        if (this.currentGroup === parent) {
                            this.currentGroup = parent.parentNode;
                        }

                        // If the parent's terms are all to be removed, we'll remove the
                        // entire parent to keep the DOM operations as efficient as possible
                        parent.remove();

                        labels.splice(0, counterpartIndex - Number(label.dataset.index));
                        continue;
                    }
                }

                this.removeRenderedTerm(label);
            }
        }

        reIndexTerms(from, howMuch = 1, forward = false) {
            let fromLabel = this.termContainer.querySelector(`[data-index="${ from }"]`);

            super.reIndexTerms(from, howMuch, forward);

            let _this = this;
            this.termContainer.querySelectorAll('[data-counterpart]').forEach(label => {
                let counterpartIndex = Number(label.dataset.counterpart);
                if ((forward && counterpartIndex >= from) || (! forward && counterpartIndex > from)) {
                    counterpartIndex += forward ? howMuch : -howMuch;

                    let termIndex = Number(label.dataset.index);
                    if (
                        (! forward && termIndex > from - howMuch && label !== fromLabel)
                        || (forward && termIndex >= from)
                    ) {
                        // Make sure to use the previous index to access usedTerms, it's not adjusted yet
                        termIndex += forward ? -howMuch : howMuch;
                    }

                    label.dataset.counterpart = `${ counterpartIndex }`;
                    _this.usedTerms[termIndex].counterpart = `${ counterpartIndex }`;
                }
            });
        }

        complete(input, data) {
            let termIndex = input.parentNode.dataset.index;
            if (termIndex) {
                data.term.type = this.usedTerms[termIndex].type;
            } else {
                termIndex = this.usedTerms.length;
                data.term.type = this.termType;
            }

            // Special cases
            switch (data.term.type) {
                case 'grouping_operator':
                    return;
                case 'operator':
                case 'logical_operator':
                    data.suggestions = this.renderSuggestions(
                        this.validOperator(data.term.label, data.term.type, termIndex)
                    );
            }

            // Additional metadata
            switch (data.term.type) {
                case 'value':
                    data.operator = this.usedTerms[--termIndex].search;
                case 'operator':
                    data.column = this.usedTerms[--termIndex].search;
            }

            super.complete(input, data);
        }

        nextTermType(termData) {
            switch (termData.type) {
                case 'column':
                    return 'operator';
                case 'operator':
                    return 'value';
                case 'value':
                    return 'logical_operator';
                case 'logical_operator':
                    return 'column';
                case 'grouping_operator':
                    return this.isGroupOpen(termData) ? 'column' : 'logical_operator';
            }
        }

        lastPendingGroupOpen(before) {
            let level = 0;
            for (let i = before - 1; i >= 0 && i < this.usedTerms.length; i--) {
                let termData = this.usedTerms[i];

                if (termData.type === 'grouping_operator') {
                    if (this.isGroupOpen(termData)) {
                        if (level === 0) {
                            return typeof termData.counterpart === 'undefined' ? i : null;
                        }

                        level++;
                    } else {
                        if (termData.counterpart >= 0) {
                            i = termData.counterpart;
                        } else {
                            level--;
                        }
                    }
                }
            }

            return null;
        }

        nextPendingGroupClose(after) {
            let level = 0;
            for (let i = after + 1; i < this.usedTerms.length; i++) {
                let termData = this.usedTerms[i];

                if (termData.type === 'grouping_operator') {
                    if (this.isGroupClose(termData)) {
                        if (level === 0) {
                            return typeof termData.counterpart === 'undefined' ? i : null;
                        }

                        level--;
                    } else {
                        if (termData.counterpart >= 0) {
                            i = termData.counterpart;
                        } else {
                            level++;
                        }
                    }
                }
            }

            return null;
        }

        isGroupOpen(termData) {
            return termData.type === 'grouping_operator' && termData.search === this.grouping_operators.open.search;
        }

        isGroupClose(termData) {
            return termData.type === 'grouping_operator' && termData.search === this.grouping_operators.close.search;
        }

        getOperator(value, termType = null) {
            if (termType === null) {
                termType = this.termType;
            }

            let operators;
            switch (termType) {
                case 'operator':
                    operators = this.relational_operators;
                    break;
                case 'logical_operator':
                    operators = this.logical_operators;
                    break;
            }

            value = value.toLowerCase();
            return operators.find((term) => {
                return value === term.label.toLowerCase() || value === term.search.toLowerCase();
            }) || null;
        }

        matchOperators(operators, value) {
            value = value.toLowerCase();

            let exactMatch = false;
            let partialMatch = false;
            let filtered = operators.filter((op) => {
                let label = op.label.toLowerCase();
                let search = op.search.toLowerCase();

                if (
                    (value.length < label.length && value === label.slice(0, value.length))
                    || (value.length < search.length && value === search.slice(0, value.length))
                ) {
                    partialMatch = true;
                    return true;
                }

                if (value === label || value === search) {
                    exactMatch = true;
                    return true;
                }

                return false;
            });

            if (exactMatch || partialMatch) {
                operators = filtered;
            }

            operators.exactMatch = exactMatch && ! partialMatch;
            operators.partialMatches = partialMatch;

            return operators;
        }

        nextOperator(value, termType = null, termIndex = null) {
            let operators = [];

            if (termType === null) {
                termType = this.termType;
            }

            if (termIndex === null && termType === 'column' && ! this.readPartialTerm(this.input)) {
                switch (true) {
                    case ! this.hasTerms():
                    case this.lastTerm().type === 'logical_operator':
                    case this.isGroupOpen(this.lastTerm()):
                        operators.push(this.grouping_operators.open);
                }
            } else {
                let nextIndex = termIndex === null ? this.usedTerms.length : termIndex + 1;
                switch (termType) {
                    case 'column':
                        operators = operators.concat(this.relational_operators);
                    case 'operator':
                    case 'value':
                        operators = operators.concat(this.logical_operators);

                        if (this.lastPendingGroupOpen(nextIndex) !== null) {
                            operators.push(this.grouping_operators.close);
                        }

                        break;
                    case 'logical_operator':
                        if (this.lastPendingGroupOpen(nextIndex) !== null) {
                            operators.push(this.grouping_operators.close);
                        }

                        operators.push(this.grouping_operators.open);
                        break;
                    case 'grouping_operator':
                        let termData = this.usedTerms[termIndex];
                        if (this.isGroupOpen(termData)) {
                            operators.push(this.grouping_operators.open);
                        } else if (this.lastPendingGroupOpen(nextIndex)) {
                            operators.push(this.grouping_operators.close);
                        }
                }
            }

            return value ? this.matchOperators(operators, value) : operators;
        }

        validOperator(value, termType = null, termIndex = null) {
            let operators = [];

            if (termType === null) {
                termType = this.termType;
            }

            switch (termType) {
                case 'operator':
                    operators = operators.concat(this.relational_operators);
                    break;
                case 'logical_operator':
                    operators = operators.concat(this.logical_operators);
                    break;
                case 'grouping_operator':
                    let termData = this.usedTerms[termIndex];
                    if (termData.counterpart >= 0) {
                        let counterpart = this.usedTerms[termData.counterpart];
                        if (this.isGroupOpen(counterpart)) {
                            operators.push(this.grouping_operators.close);
                        } else {
                            operators.push(this.grouping_operators.open);
                        }
                    }
            }

            return value ? this.matchOperators(operators, value) : operators;
        }

        checkValidity(input, type = null, termIndex = null) {
            if (type === null) {
                type = input.parentNode.dataset.type;
            }

            if (! type || type === 'value') {
                // type is undefined for the main input, values have no special validity rules
                return input.checkValidity();
            }

            if (termIndex === null && input.parentNode.dataset.index >= 0) {
                termIndex = Number(input.parentNode.dataset.index);
            }

            let value = this.readPartialTerm(input);

            let options;
            switch (type) {
                case 'operator':
                case 'logical_operator':
                case 'grouping_operator':
                    options = this.validOperator(value, type, termIndex);
            }

            let message = '';
            if (type === 'column') {
                let nextTermAt = termIndex + 1;
                if (! value && nextTermAt < this.usedTerms.length && this.usedTerms[nextTermAt].type === 'operator') {
                    message = this.input.dataset.chooseColumn;
                }
            } else {
                let isRequired = ! options.exactMatch;
                if (options.partialMatches && options.length > 1) {
                    isRequired = false;
                } else if (type === 'operator' && ! value) {
                    let nextTermAt = termIndex + 1;
                    isRequired = nextTermAt < this.usedTerms.length && this.usedTerms[nextTermAt].type === 'value';
                } else if (type === 'logical_operator' && ! value) {
                    if (termIndex === 0 || termIndex === this.usedTerms.length - 1) {
                        isRequired = false;
                    } else {
                        isRequired = ! this.isGroupOpen(this.usedTerms[termIndex - 1])
                            && ! this.isGroupClose(this.usedTerms[termIndex + 1])
                            && this.usedTerms[termIndex - 1].type !== 'logical_operator'
                            && this.usedTerms[termIndex + 1].type !== 'logical_operator';
                    }
                } else if (type === 'grouping_operator') {
                    if (typeof this.usedTerms[termIndex].counterpart === 'undefined') {
                        if (value) {
                            message = this.input.dataset.incompleteGroup;
                        }

                        isRequired = false;
                    } else if (! value) {
                        isRequired = false;
                    }
                }

                if (isRequired) {
                    message = this.input.dataset.chooseTemplate.replace(
                        '%s',
                        options.map(e => e.label).join(', ')
                    );
                }
            }

            if (! message && termIndex > 0 && type !== 'logical_operator') {
                let previousTerm = this.usedTerms[termIndex - 1];

                let missingLogicalOp = true;
                switch (type) {
                    case 'column':
                        missingLogicalOp = previousTerm.type !== 'logical_operator'
                            && ! this.isGroupOpen(previousTerm);
                        break;
                    case 'operator':
                        missingLogicalOp = previousTerm.type !== 'column';
                        break;
                    case 'value':
                        missingLogicalOp = previousTerm.type !== 'operator';
                        break;
                    case 'grouping_operator':
                        if (value === this.grouping_operators.open.label) {
                            missingLogicalOp = previousTerm.type !== 'logical_operator'
                                && ! this.isGroupOpen(previousTerm);
                        } else {
                            missingLogicalOp = false;
                        }
                }

                if (missingLogicalOp) {
                    message = this.input.dataset.missingLogOp;
                }
            }

            input.setCustomValidity(message);
            return input.checkValidity();
        }

        reportValidity(element) {
            setTimeout(() => element.reportValidity(), 0);
        }

        togglePreview() {
            switch (this.termType) {
                case 'operator':
                    this.previewedTerm = this.relational_operators[0];
                    break;
                case 'logical_operator':
                    this.previewedTerm = this.logical_operators[0];
                    break;
                default:
                    this.previewedTerm = null;
            }

            if (this.previewedTerm !== null) {
                if (this.input.nextSibling !== null) {
                    this.input.nextSibling.innerText = this.previewedTerm.label;
                } else {
                    this.input.after(this.renderPreview(this.previewedTerm.label));
                }
            } else if (this.input.nextSibling !== null) {
                this.input.nextSibling.remove();
            }
        }

        renderSuggestions(suggestions) {
            let itemTemplate = $('<li><input type="button"></li>').render();

            let list = document.createElement('ul');

            suggestions.forEach((term) => {
                let item = itemTemplate.cloneNode(true);
                item.firstChild.value = term.label;

                for (let name in term) {
                    item.firstChild.dataset[name] = term[name];
                }

                list.appendChild(item);
            });

            return list;
        }

        renderPreview(content) {
            return $('<span>' + content + '</span>').render();
        }

        renderCondition() {
            return $(
                '<div class="filter-condition" data-group-type="condition">'
                + '<button type="button"><i class="icon-cancel"></i></button>'
                + '</div>'
            ).render();
        }

        renderChain() {
            return $('<div class="filter-chain" data-group-type="chain"></div>').render();
        }

        renderTerm(termData, termIndex) {
            let label = super.renderTerm(termData, termIndex);
            label.dataset.type = termData.type;
            if (termData.counterpart >= 0) {
                label.dataset.counterpart = termData.counterpart;
            }

            return label;
        }

        escapeExpression(expr) {
            return encodeURIComponent(expr).replace(
                /[()]/g,
                function(c) {
                    return '%' + c.charCodeAt(0).toString(16);
                }
            );
        }

        /**
         * Event listeners
         */

        onTermFocus(event) {
            if (! this.checkValidity(event.target)) {
                this.reportValidity(event.target);
            }
        }

        onRemoveCondition(event) {
            let button = event.target.closest('button');
            this.removeRange(Array.from(button.parentNode.querySelectorAll(':scope > [data-index]')));
            // TODO: Remove unnecessary logical operators and chains
        }

        onCompletion(event) {
            super.onCompletion(event);

            let input = event.target;
            this.checkValidity(input);

            if (input.parentNode.dataset.index >= 0) {
                return;
            }

            if (this.previewedTerm !== null) {
                this.complete(this.input, { term: { label: '' } });
            }
        }

        onKeyDown(event) {
            let input = event.target;
            let isTerm = input.parentNode.dataset.index >= 0;

            if (! isTerm && this.previewedTerm !== null && event.key === ' ' && ! this.readPartialTerm(input)) {
                // Done early because pushing space in this case will already show suggestions.
                // But in case of a previewed term, these should be for the next term type.
                this.addTerm(this.previewedTerm);
            }

            super.onKeyDown(event);
            if (event.defaultPrevented) {
                return;
            }

            switch (event.key) {
                case 'Tab':
                    if (! isTerm && this.previewedTerm !== null) {
                        this.addTerm(this.previewedTerm);
                        this.togglePlaceholder();
                        event.preventDefault();
                    }

                    break;
                default:
                    let currentValue = this.readPartialTerm(input);
                    if (isTerm && ! currentValue) {
                        // Switching contexts requires input first
                        break;
                    }else if (input.selectionStart !== input.selectionEnd) {
                        // In case the user selected a range of text, do nothing
                        break;
                    } else if (/[A-Z]/.test(event.key.charAt(0))) {
                        // Ignore control keys not resulting in new input data
                        // TODO: Remove this and move the entire block into `onInput`
                        //       once Safari supports `InputEvent.data`
                        break;
                    }

                    let termIndex = null;
                    let termType = this.termType;
                    if (isTerm) {
                        if (input.selectionEnd === input.value.length) {
                            // Cursor is at the end of the input
                            termIndex = Number(input.parentNode.dataset.index);
                            termType = input.parentNode.dataset.type;
                        } else if (input.selectionStart === 0) {
                            // Cursor is at the start of the input
                            termIndex = Number(input.parentNode.dataset.index) - 1;
                            termType = this.usedTerms[termIndex].type;
                        } else {
                            // In case the cursor is somewhere in between, do nothing
                            break;
                        }

                        if (termIndex < this.usedTerms.length - 1) {
                            let nextTerm = this.usedTerms[termIndex + 1];
                            if (nextTerm.type === 'operator' || nextTerm.type === 'value') {
                                // In between parts of a condition there's no context switch possible at all
                                break;
                            }
                        }
                    } else if (input.selectionEnd !== input.value.length) {
                        // Main input processing only happens at the end of the input
                        break;
                    }

                    let operators;
                    let value = event.key;
                    if (! isTerm || termType === 'operator') {
                        operators = this.validOperator(
                            termType === 'operator' ? currentValue + value : value, termType, termIndex);
                        if (! operators.exactMatch && ! operators.partialMatches) {
                            operators = this.nextOperator(value, termType, termIndex);
                        }
                    } else {
                        operators = this.nextOperator(value, termType, termIndex);
                    }

                    if (isTerm) {
                        if (operators.exactMatch && operators[0].label.toLowerCase() !== value.toLowerCase()) {
                            // The user completes a partial match
                        } else if (operators.exactMatch && (
                            termType !== 'operator' || operators[0].type !== 'operator'
                        )) {
                            $(this.insertTerm({ ...operators[0] }, termIndex + 1)).focus();
                            event.preventDefault();
                        } else if (operators.partialMatches && termType !== 'operator') {
                            let termData = { ...operators[0] };
                            termData.label = termData.search = value;
                            $(this.insertTerm(termData, termIndex + 1)).focus();
                            event.preventDefault();
                        } else {
                            // If no match is found, the user continues typing
                            switch (termType) {
                                case 'operator':
                                    $(this.insertTerm(
                                        { label: value, search: value, type: 'value' },
                                        termIndex + 1
                                    )).focus();
                                    event.preventDefault();
                                    break;
                                case 'logical_operator':
                                    $(this.insertTerm(
                                        { label: value, search: value, type: 'column' },
                                        termIndex + 1
                                    )).focus();
                                    event.preventDefault();
                                    break;
                            }
                        }
                    } else {
                        if (operators.partialMatches) {
                            this.exchangeTerm();
                            this.togglePlaceholder();
                        } else if (operators.exactMatch) {
                            if (termType !== operators[0].type) {
                                this.exchangeTerm();
                            } else {
                                this.clearPartialTerm(input);
                            }

                            this.addTerm({ ...operators[0] });
                            this.togglePlaceholder();
                            event.preventDefault();
                        } else if (termType === 'operator') {
                            let partialOperator = this.getOperator(currentValue);
                            if (partialOperator !== null) {
                                // If no match is found, the user seems to want the partial operator.
                                this.addTerm({ ...partialOperator });
                                this.clearPartialTerm(input);
                            }
                        }
                    }
            }
        }

        onInput(event) {
            let input = event.target;

            if (! this.checkValidity(input)) {
                this.reportValidity(input);
                return;
            }

            let isTerm = input.parentNode.dataset.index >= 0;

            if (! isTerm && this.previewedTerm !== null) {
                let value = this.readPartialTerm(input);
                if (value && ! this.validOperator(value).partialMatches) {
                    if (value !== this.previewedTerm.label) {
                        this.addTerm(this.previewedTerm);
                        this.togglePlaceholder();
                    } else {
                        this.exchangeTerm();
                        this.togglePlaceholder();
                    }
                }
            }

            super.onInput(event);
        }
    }

    return FilterInput;
})(BaseInput, notjQuery));
