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

    /** @var callable|Filter */
    protected $filter;

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

        if ($filter === null || $filter->isEmpty()) {
            return;
        }

        if ($filter->isChain()) {
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
                $opening = $this->assembleTerm('grouping_operator_open', 'grouping_operator', '(', '(');
                $this->assembleConditions($filter);
                $closing = $this->assembleTerm('grouping_operator_close', 'grouping_operator', ')', ')');

                $opening->addAttributes([
                    'data-counterpart' => $closing->getAttributes()->get('data-index')->getValue()
                ]);
                $closing->addAttributes([
                    'data-counterpart' => $opening->getAttributes()->get('data-index')->getValue()
                ]);
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

        $this->assembleTerm('column', 'column', rawurlencode($column), $columnLabel);

        if (! $filter->isBooleanTrue()) {
            $this->assembleTerm('operator', 'operator', $operator, $operator);

            if (! empty($value)) {
                $this->assembleTerm('value', 'value', rawurlencode($value), $value);
            }
        }
    }

    protected function assembleTerm($class, $type, $search, $label)
    {
        $term = new HtmlElement('label', [
            'class'         => $class,
            'data-index'    => $this->count(),
            'data-type'     => $type,
            'data-search'   => $search,
            'data-label'    => $label
        ], new HtmlElement('input', [
            'type'  => 'text',
            'value' => $label
        ]));

        $this->add($term);

        return $term;
    }
}
