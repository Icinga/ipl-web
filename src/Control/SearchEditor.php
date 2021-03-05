<?php

namespace ipl\Web\Control;

use ipl\Html\Form;
use ipl\Html\HtmlElement;
use ipl\Stdlib\Filter;
use ipl\Web\Filter\Parser;
use ipl\Web\Filter\QueryString;
use ipl\Web\Filter\Renderer;
use ipl\Web\Url;
use ipl\Web\Widget\Icon;

class SearchEditor extends Form
{
    /** @var string The column name used for empty conditions */
    const FAKE_COLUMN = '_fake_';

    protected $defaultAttributes = [
        'data-enrichment-type'  => 'search-editor',
        'class'                 => 'search-editor'
    ];

    /** @var string */
    protected $queryString;

    /** @var Url */
    protected $suggestionUrl;

    /** @var Parser */
    protected $parser;

    /** @var Filter\Rule */
    protected $filter;

    /**
     * Set the filter query string to populate the form with
     *
     * Use {@see SearchEditor::getParser()} to subscribe to parser events.
     *
     * @param string $query
     *
     * @return $this
     */
    public function setQueryString($query)
    {
        $this->queryString = $query;

        return $this;
    }

    /**
     * Set the suggestion url
     *
     * @param Url $url
     *
     * @return $this
     */
    public function setSuggestionUrl(Url $url)
    {
        $this->suggestionUrl = $url;

        return $this;
    }

    /**
     * Get the query string parser being used
     *
     * @return Parser
     */
    public function getParser()
    {
        if ($this->parser === null) {
            $this->parser = new Parser();
        }

        return $this->parser;
    }

    /**
     * Get the current filter
     *
     * @return Filter\Rule
     */
    public function getFilter()
    {
        if ($this->filter === null) {
            $this->filter = $this->getParser()
                ->setQueryString($this->queryString)
                ->parse();
        }

        return $this->filter;
    }

    public function populate($values)
    {
        // applyChanges() is basically this form's own populate implementation, hence
        // why it changes $values and needs to run before actually populating the form
        $filter = (new Parser(isset($values['filter']) ? $values['filter'] : $this->queryString))
            ->setStrict()
            ->parse();
        $filter = $this->applyChanges($filter, $values);

        parent::populate($values);

        $this->filter = $this->applyStructuralChange($filter);
        $this->queryString = (new Renderer($this->filter))->setStrict()->render();

        return $this;
    }

    protected function applyChanges(Filter\Rule $rule, array &$values, array $path = [0])
    {
        $identifier = join('-', $path);

        if ($rule instanceof Filter\Condition) {
            $newColumn = $this->popKey($values, $identifier . '-column-search');
            if ($newColumn === null) {
                $newColumn = $this->popKey($values, $identifier . '-column');
            } else {
                // Make sure we don't forget to present the column labels again
                $rule->columnLabel = $this->popKey($values, $identifier . '-column');
            }

            if ($newColumn !== null && $rule->getColumn() !== $newColumn) {
                $rule->setColumn($newColumn ?: static::FAKE_COLUMN);
                // TODO: Clear meta data?
            }

            $newValue = $this->popKey($values, $identifier . '-value');
            if ($newValue !== null && $rule->getValue() !== $newValue) {
                $rule->setValue($newValue);
            }

            $newOperator = $this->popKey($values, $identifier . '-operator');
            if ($newOperator !== null && QueryString::getRuleSymbol($rule) !== $newOperator) {
                switch ($newOperator) {
                    case '=':
                        return Filter::equal($rule->getColumn(), $rule->getValue());
                    case '!=':
                        return Filter::unequal($rule->getColumn(), $rule->getValue());
                    case '>':
                        return Filter::greaterThan($rule->getColumn(), $rule->getValue());
                    case '>=':
                        return Filter::greaterThanOrEqual($rule->getColumn(), $rule->getValue());
                    case '<':
                        return Filter::lessThan($rule->getColumn(), $rule->getValue());
                    case '<=':
                        return Filter::lessThanOrEqual($rule->getColumn(), $rule->getValue());
                }
            }
        } else {
            /** @var Filter\Chain $rule */
            $newGroupOperator = $this->popKey($values, $identifier);
            $oldGroupOperator = $rule instanceof Filter\None ? '!' : QueryString::getRuleSymbol($rule);
            if ($newGroupOperator !== null && $oldGroupOperator !== $newGroupOperator) {
                switch ($newGroupOperator) {
                    case '&':
                        $rule = Filter::all(...$rule);
                        break;
                    case '|':
                        $rule = Filter::any(...$rule);
                        break;
                    case '!':
                        $rule = Filter::none(...$rule);
                        break;
                }
            }

            $i = 0;
            foreach ($rule as $child) {
                $childPath = $path;
                $childPath[] = $i++;
                $newChild = $this->applyChanges($child, $values, $childPath);
                if ($child !== $newChild) {
                    $rule->replace($child, $newChild);
                }
            }
        }

        return $rule;
    }

    protected function applyStructuralChange(Filter\Rule $rule)
    {
        $structuralChange = $this->getPopulatedValue('structural-change');
        if (empty($structuralChange)) {
            return $rule;
        } elseif (is_array($structuralChange)) {
            ksort($structuralChange);
        }

        list($type, $where) = explode(':', is_array($structuralChange)
            ? array_shift($structuralChange)
            : $structuralChange);
        $targetPath = explode('-', $where);

        $targetFinder = function ($path) use ($rule) {
            $parent = null;
            $target = null;
            $children = [$rule];
            foreach ($path as $targetPos) {
                if ($target !== null) {
                    $parent = $target;
                    $children = $parent instanceof Filter\Chain
                        ? iterator_to_array($parent)
                        : [];
                }

                if (! isset($children[$targetPos])) {
                    return $rule;
                }

                $target = $children[$targetPos];
            }

            return [$parent, $target];
        };

        list($parent, $target) = $targetFinder($targetPath);

        $emptyEqual = Filter::equal(static::FAKE_COLUMN, '');
        switch ($type) {
            case 'move-rule':
                if (! is_array($structuralChange) || empty($structuralChange)) {
                    return $rule;
                }

                list($placement, $moveToPath) = explode(':', array_shift($structuralChange));
                list($moveToParent, $moveToTarget) = $targetFinder(explode('-', $moveToPath));

                $parent->remove($target);
                if ($placement === 'before') {
                    $moveToParent->insertBefore($target, $moveToTarget);
                } else {
                    $moveToParent->insertAfter($target, $moveToTarget);
                }

                break;
            case 'condition-before':
                if ($parent !== null) {
                    $parent->insertBefore($emptyEqual, $target);
                } else {
                    $rule = Filter::all($emptyEqual, $target);
                }

                break;
            case 'condition-after':
                if ($parent !== null) {
                    $parent->insertAfter($emptyEqual, $target);
                } else {
                    $rule = Filter::all($target, $emptyEqual);
                }

                break;
            case 'chain-before':
                if ($parent !== null) {
                    $parent->insertBefore(Filter::all($emptyEqual), $target);
                } else {
                    $rule = Filter::all(Filter::all($emptyEqual), $target);
                }

                break;
            case 'chain-after':
                if ($parent !== null) {
                    $parent->insertAfter(Filter::all($emptyEqual), $target);
                } else {
                    $rule = Filter::all($target, $emptyEqual);
                }

                break;
            case 'drop-rule':
                if ($parent !== null) {
                    $parent->remove($target);
                } else {
                    $rule = $emptyEqual;
                }
        }

        return $rule;
    }

    protected function createTree(Filter\Rule $rule, array $path = [0])
    {
        $identifier = join('-', $path);

        if ($rule instanceof Filter\Condition) {
            $item = [$this->createCondition($rule, $identifier), $this->createButtons($rule, $identifier)];

            if (count($path) === 1) {
                $item = new HtmlElement('ol', null, new HtmlElement(
                    'li',
                    ['id' => $identifier],
                    $item
                ));
            } else {
                array_splice($item, 1, 0, [
                    new Icon('bars', ['class' => 'drag-initiator'])
                ]);
            }
        } else {
            /** @var Filter\Chain $rule */
            $item = new HtmlElement('ul');

            $groupOperatorInput = $this->createElement('select', $identifier, [
                'options'   => [
                    '&' => 'ALL',
                    '|' => 'ANY',
                    '!' => 'NONE'
                ],
                'value' => $rule instanceof Filter\None ? '!' : QueryString::getRuleSymbol($rule)
            ]);
            $this->registerElement($groupOperatorInput);
            $item->add(new HtmlElement('li', ['id' => $identifier], [
                $groupOperatorInput,
                count($path) > 1
                    ? new Icon('bars', ['class' => 'drag-initiator'])
                    : null,
                $this->createButtons($rule, $identifier)
            ]));

            $children = new HtmlElement('ol');
            $item->add(new HtmlElement('li', null, $children));

            $i = 0;
            foreach ($rule as $child) {
                $childPath = $path;
                $childPath[] = $i++;
                $children->add(new HtmlElement(
                    'li',
                    ['id' => join('-', $childPath)],
                    $this->createTree($child, $childPath)
                ));
            }
        }

        return $item;
    }

    protected function createButtons(Filter\Rule $for, $identifier)
    {
        return new HtmlElement('div', ['class' => 'buttons'], [
            new HtmlElement('ul', null, [
                new HtmlElement('li', null, $this->createElement('submitButton', 'structural-change', [
                    'value' => 'condition-before:' . $identifier,
                    'label' => t('Prepend Condition')
                ])),
                new HtmlElement('li', null, $this->createElement('submitButton', 'structural-change', [
                    'value' => 'condition-after:' . $identifier,
                    'label' => t('Append Condition')
                ])),
                new HtmlElement('li', null, $this->createElement('submitButton', 'structural-change', [
                    'value' => 'chain-before:' . $identifier,
                    'label' => t('Prepend Group')
                ])),
                new HtmlElement('li', null, $this->createElement('submitButton', 'structural-change', [
                    'value' => 'chain-after:' . $identifier,
                    'label' => t('Append Group')
                ])),
                new HtmlElement('li', null, $this->createElement('submitButton', 'structural-change', [
                    'value' => 'drop-rule:' . $identifier,
                    'label' => $for instanceof Filter\Condition
                        ? t('Delete Condition')
                        : t('Delete Group')
                ]))
            ]),
            new Icon('ellipsis-h')
        ]);
    }

    protected function createCondition(Filter\Condition $condition, $identifier)
    {
        $columnInput = $this->createElement('text', $identifier . '-column', [
            'value' => isset($condition->columnLabel)
                ? $condition->columnLabel
                : ($condition->getColumn() !== static::FAKE_COLUMN
                    ? $condition->getColumn()
                    : null),
            'autocomplete' => 'off',
            'data-type' => 'column',
            'data-enrichment-type' => 'completion',
            'data-term-suggestions' => '#search-editor-suggestions',
            'data-suggest-url' => $this->suggestionUrl
        ]);
        $columnFakeInput = $this->createElement('hidden', $identifier . '-column-search', [
            'value' => static::FAKE_COLUMN
        ]);
        $columnSearchInput = $this->createElement('hidden', $identifier . '-column-search', [
            'value' => $condition->getColumn()
        ]);

        $operatorInput = $this->createElement('select', $identifier . '-operator', [
            'options'   => [
                '='     => '=',
                '!='    => '!=',
                '>'     => '>',
                '<'     => '<',
                '>='    => '>=',
                '<='    => '<='
            ],
            'value'     => QueryString::getRuleSymbol($condition)
        ]);

        $valueInput = $this->createElement('text', $identifier . '-value', [
            'value' => $condition->getValue(),
            'autocomplete' => 'off',
            'data-type' => 'value',
            'data-enrichment-type' => 'completion',
            'data-term-suggestions' => '#search-editor-suggestions',
            'data-suggest-url' => $this->suggestionUrl
        ]);

        $this->registerElement($columnInput);
        $this->registerElement($columnSearchInput);
        $this->registerElement($operatorInput);
        $this->registerElement($valueInput);

        return new HtmlElement('fieldset', ['name' => $identifier . '-'], [
            $columnInput,
            $columnFakeInput,
            $columnSearchInput,
            $operatorInput,
            $valueInput
        ]);
    }

    protected function assemble()
    {
        $filterInput = $this->createElement('hidden', 'filter');
        $filterInput->getAttributes()->registerAttributeCallback(
            'value',
            function () {
                return $this->queryString;
            },
            [$this, 'setQueryString']
        );
        $this->addElement($filterInput);

        $this->add($this->createTree($this->getFilter()));
        $this->add(new HtmlElement('div', [
            'id'    => 'search-editor-suggestions',
            'class' => 'search-suggestions'
        ]));

        $this->addElement('submit', 'btn_submit', [
            'label' => t('Apply')
        ]);

        // Add submit button also as first element to make Web 2 submit
        // the form instead of using a structural change to submit if
        // the user just presses Enter.
        $this->prepend($this->getElement('btn_submit'));
    }

    private function popKey(array &$from, $key, $default = null)
    {
        if (isset($from[$key])) {
            $value = $from[$key];
            unset($from[$key]);

            return $value;
        }

        return $default;
    }
}
