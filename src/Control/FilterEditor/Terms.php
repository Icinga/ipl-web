<?php

namespace ipl\Web\Control\FilterEditor;

use Icinga\Data\Filter\Filter;
use Icinga\Data\Filter\FilterChain;
use Icinga\Data\Filter\FilterExpression;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;

class Terms extends BaseHtmlElement
{
    protected $tag = 'div';

    protected $defaultAttributes = ['class' => 'terms'];

    /** @var Filter */
    protected $filter;

    public function setFilter(Filter $filter)
    {
        $this->filter = $filter;

        return $this;
    }

    protected function assemble()
    {
        if ($this->filter === null || $this->filter->isEmpty()) {
            return;
        }

        $filter = $this->filter;
        if ($this->filter->isChain()) {
            /** @var FilterChain $filter */
            $this->assembleConditions($filter);
        } else {
            /** @var FilterExpression $filter */
            $this->assembleCondition($filter);
        }
    }

    protected function assembleConditions(FilterChain $filters)
    {
        foreach ($filters->filters() as $i => $filter) {
            if ($i > 0) {
                $logicalOperator = $filters->getOperatorSymbol();
                $this->assembleTerm('logical_operator', 'logical_operator', $logicalOperator, $logicalOperator);
            }

            if ($filter->isChain()) {
                $this->assembleTerm('grouping_operator_open', 'grouping_operator', '(', '(');
                $this->assembleConditions($filter);
                $this->assembleTerm('grouping_operator_close', 'grouping_operator', ')', ')');
            } else {
                $this->assembleCondition($filter);
            }
        }
    }

    protected function assembleCondition(FilterExpression $filter)
    {
        $column = $filter->getColumn();
        $operator = $filter->getSign();
        $value = $filter->getExpression();

        $columnLabel = $column;
        if (isset($filter->metaData['label'])) {
            // TODO: Change once filters have native meta data
            $columnLabel = $filter->metaData['label'];
        }

        $this->assembleTerm('column', 'column', $column, $columnLabel);

        if (! $filter->isBooleanTrue()) {
            $this->assembleTerm('operator', 'operator', $operator, $operator);
            $this->assembleTerm('value', 'value', $value, $value);
        }
    }

    protected function assembleTerm($class, $type, $search, $term)
    {
        $this->add(new HtmlElement('label', [
            'data-term-index'   => $this->count(),
            'data-term-class'   => $class,
            'data-term-type'    => $type,
            'data-term-search'  => $search,
            'data-term'         => $term
        ], new HtmlElement('input', [
            'type'  => 'text',
            'value' => $term
        ])));
    }
}
