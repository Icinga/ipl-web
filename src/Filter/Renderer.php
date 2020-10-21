<?php

namespace ipl\Web\Filter;

use ipl\Stdlib\Filter;

class Renderer
{
    /** @var Filter\Rule */
    protected $filter;

    /** @var string */
    protected $string;

    /**
     * Create a new filter Renderer
     *
     * @param Filter\Rule $filter
     */
    public function __construct(Filter\Rule $filter)
    {
        $this->filter = $filter;
    }

    /**
     * Assemble and return the filter as query string
     *
     * @return string
     */
    public function render()
    {
        if ($this->string !== null) {
            return $this->string;
        }

        $this->string = '';
        $filter = $this->filter;

        if ($filter instanceof Filter\Chain) {
            $this->renderChain($filter);
        } else {
            /** @var Filter\Condition $filter */
            $this->renderCondition($filter);
        }

        return $this->string;
    }

    /**
     * Assemble the given filter Chain
     *
     * @param Filter\Chain $chain
     * @param bool $wrap
     *
     * @return void
     */
    protected function renderChain(Filter\Chain $chain, $wrap = false)
    {
        if ($chain->isEmpty()) {
            return;
        }

        $chainOperator = null;
        switch (true) {
            case $chain instanceof Filter\All:
                $chainOperator = '&';
                break;
            case $chain instanceof Filter\None:
                $this->string .= '!';

                // Force wrap, it may be the root node
                if (! $wrap) {
                    if ($chain->count() > 1) {
                        $wrap = true;
                    } else {
                        $iterator = $chain->getIterator();
                        $wrap = $iterator->current() instanceof Filter\None;
                    }
                }

                // None shares the operator with Any
            case $chain instanceof Filter\Any:
                $chainOperator = '|';
                break;
        }

        if ($wrap) {
            $this->string .= '(';
        }

        foreach ($chain as $rule) {
            if ($rule instanceof Filter\Chain) {
                $this->renderChain($rule, $rule->count() > 1);
            } else {
                /** @var Filter\Condition $rule */
                $this->renderCondition($rule);
            }

            $this->string .= $chainOperator;
        }

        // Remove redundant chain operator added last
        $this->string = substr($this->string, 0, -1);

        if ($wrap) {
            $this->string .= ')';
        }
    }

    /**
     * Assemble the given filter Condition
     *
     * @param Filter\Condition $condition
     *
     * @return void
     */
    protected function renderCondition(Filter\Condition $condition)
    {
        $value = $condition->getValue();
        if (is_bool($value) && ! $value) {
            $this->string .= '!';
        }

        $this->string .= rawurlencode($condition->getColumn());

        if (is_bool($value)) {
            return;
        }

        switch (true) {
            case $condition instanceof Filter\Unequal:
                $this->string .= '!=';
                break;
            case $condition instanceof Filter\Equal:
                $this->string .= '=';
                break;
            case $condition instanceof Filter\GreaterThan:
                $this->string .= '>';
                break;
            case $condition instanceof Filter\LessThan:
                $this->string .= '<';
                break;
            case $condition instanceof Filter\GreaterThanOrEqual:
                $this->string .= '>=';
                break;
            case $condition instanceof Filter\LessThanOrEqual:
                $this->string .= '<=';
                break;
        }

        if (is_array($value)) {
            $this->string .= '(' . join('|', array_map('rawurlencode', $value)) . ')';
        } else {
            $this->string .= rawurlencode($value);
        }
    }
}
