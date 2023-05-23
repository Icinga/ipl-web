<?php

namespace ipl\Web\Filter;

use InvalidArgumentException;
use ipl\Stdlib\Filter;

final class QueryString
{
    /** @var string Emitted for every completely parsed condition */
    const ON_CONDITION = Parser::ON_CONDITION;

    /** @var string Emitted for every completely parsed chain */
    const ON_CHAIN = Parser::ON_CHAIN;

    /**
     * This class is only a factory / helper
     */
    private function __construct()
    {
    }

    /**
     * Derive a rule from the given query string
     *
     * @param string $string
     *
     * @return Parser
     */
    public static function fromString($string)
    {
        return new Parser($string);
    }

    /**
     * Derive a rule from the given query string
     *
     * @param string $string
     *
     * @return Filter\Rule
     */
    public static function parse($string)
    {
        return (new Parser($string))->parse();
    }

    /**
     * Assemble a query string for the given rule
     *
     * @param Filter\Rule $rule
     *
     * @return string
     */
    public static function render(Filter\Rule $rule)
    {
        return (new Renderer($rule))->render();
    }

    /**
     * Get the symbol associated with the given rule
     *
     * @param Filter\Rule $rule
     *
     * @return string
     */
    public static function getRuleSymbol(Filter\Rule $rule)
    {
        switch (true) {
            case $rule instanceof Filter\Unequal:
            case $rule instanceof Filter\Unlike:
                return '!=';
            case $rule instanceof Filter\Equal:
            case $rule instanceof Filter\Like:
                return '=';
            case $rule instanceof Filter\GreaterThan:
                return '>';
            case $rule instanceof Filter\LessThan:
                return '<';
            case $rule instanceof Filter\GreaterThanOrEqual:
                return '>=';
            case $rule instanceof Filter\LessThanOrEqual:
                return '<=';
            case $rule instanceof Filter\All:
                return '&';
            case $rule instanceof Filter\Any:
            case $rule instanceof Filter\None:
                return '|';
            default:
                throw new InvalidArgumentException('Unknown rule type provided');
        }
    }

    /**
     * Create and return a condition
     *
     * @param string $column
     * @param string $operator
     * @param mixed  $value
     *
     * @return Filter\Condition
     * @throws InvalidArgumentException In case the operator is invalid
     */
    public static function createCondition(string $column, string $operator, $value): Filter\Condition
    {
        $column = trim($column);

        switch ($operator) {
            case '=':
                if (is_string($value) && strpos($value, "*") !== false) {
                    return Filter::like($column, $value);
                }

                return Filter::equal($column, $value);
            case '!=':
                if (is_string($value) && strpos($value, '*') !== false) {
                    return Filter::unlike($column, $value);
                }

                return Filter::unequal($column, $value);
            case '>':
                return Filter::greaterThan($column, $value);
            case '>=':
                return Filter::greaterThanOrEqual($column, $value);
            case '<':
                return Filter::lessThan($column, $value);
            case '<=':
                return Filter::lessThanOrEqual($column, $value);
            default:
                throw new InvalidArgumentException(sprintf("Invalid operator '%s'", $operator));
        }
    }
}
