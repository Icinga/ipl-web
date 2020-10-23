<?php

namespace ipl\Web\Control\SearchBar;

use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;
use ipl\Stdlib\Filter;
use ipl\Web\Filter\QueryString;
use ipl\Web\Widget\Icon;

class Terms extends BaseHtmlElement
{
    protected $tag = 'div';

    protected $defaultAttributes = ['class' => 'terms'];

    /** @var callable|Filter\Rule */
    protected $filter;

    private $currentIndex = 0;

    public function setFilter($filter)
    {
        $this->filter = $filter;

        return $this;
    }

    protected function assemble()
    {
        $filter = $this->filter;
        if (is_callable($filter)) {
            $filter = $filter();
        }

        if ($filter === null) {
            return;
        }

        if ($filter instanceof Filter\Chain) {
            if ($filter->isEmpty()) {
                return;
            }

            $this->assembleConditions($filter, $this);
        } else {
            /** @var Filter\Condition $filter */
            $this->assembleCondition($filter, $this);
        }
    }

    protected function assembleConditions(Filter\Chain $filters, BaseHtmlElement $where)
    {
        foreach ($filters as $i => $filter) {
            if ($i > 0) {
                $logicalOperator = QueryString::getRuleSymbol($filters);
                $this->assembleTerm('logical_operator', 'logical_operator', $logicalOperator, $logicalOperator, $where);
            }

            if ($filter instanceof Filter\Chain) {
                $this->assembleChain($filter, $where);
            } else {
                /** @var Filter\Condition $filter */
                $this->assembleCondition($filter, $where);
            }
        }
    }

    protected function assembleChain(Filter\Chain $chain, BaseHtmlElement $where)
    {
        $group = new HtmlElement(
            'div',
            ['class' => 'filter-chain', 'data-group-type' => 'chain']
        );

        $opening = $this->assembleTerm('grouping_operator_open', 'grouping_operator', '(', '(', $group);
        $this->assembleConditions($chain, $group);
        $closing = $this->assembleTerm('grouping_operator_close', 'grouping_operator', ')', ')', $group);

        $opening->addAttributes([
            'data-counterpart' => $closing->getAttributes()->get('data-index')->getValue()
        ]);
        $closing->addAttributes([
            'data-counterpart' => $opening->getAttributes()->get('data-index')->getValue()
        ]);

        $where->add($group);
    }

    protected function assembleCondition(Filter\Condition $filter, BaseHtmlElement $where)
    {
        $column = $filter->getColumn();
        $operator = QueryString::getRuleSymbol($filter);
        $value = $filter->getValue();

        $columnLabel = $column;
        if (isset($filter->columnLabel)) {
            $columnLabel = $filter->columnLabel;
        }

        $group = new HtmlElement(
            'div',
            ['class' => 'filter-condition', 'data-group-type' => 'condition'],
            new HtmlElement('button', ['type' => 'button'], new Icon('cancel'))
        );

        $this->assembleTerm('column', 'column', rawurlencode($column), $columnLabel, $group);

        if ($value !== true) {
            $this->assembleTerm('operator', 'operator', $operator, $operator, $group);

            if (! empty($value) || ctype_digit($value)) {
                $this->assembleTerm('value', 'value', rawurlencode($value), $value, $group);
            }
        }

        $where->add($group);
    }

    protected function assembleTerm($class, $type, $search, $label, BaseHtmlElement $where)
    {
        $term = new HtmlElement('label', [
            'class'         => $class,
            'data-index'    => $this->currentIndex++,
            'data-type'     => $type,
            'data-search'   => $search,
            'data-label'    => $label
        ], new HtmlElement('input', [
            'type'  => 'text',
            'value' => $label
        ]));

        $where->add($term);

        return $term;
    }
}
