<?php

namespace ipl\Tests\Web;

use ipl\Stdlib\Filter;
use ipl\Web\Filter\Parser;
use ipl\Web\Filter\QueryString;
use ipl\Web\Filter\Renderer;

/**
 * @todo Error handling tests
 */
class FilterTest extends TestCase
{
    public function testParserIdentifiesSingleConditions()
    {
        $expected = QueryString::render(Filter::equal('foo', 'bar'));

        $this->assertEquals(
            $expected,
            QueryString::render(QueryString::parse($expected)),
            "Filter\Parser doesn't parse single conditions correctly"
        );
    }

    public function testParserIdentifiesBooleanConditions()
    {
        $expectedTrue = QueryString::render(Filter::equal('active', true));
        $this->assertEquals(
            $expectedTrue,
            QueryString::render(QueryString::parse($expectedTrue)),
            "Filter\Parser doesn't parse boolean (true) conditions correctly"
        );

        $expectedFalse = QueryString::render(Filter::equal('active', false));
        $this->assertEquals(
            $expectedFalse,
            QueryString::render(QueryString::parse($expectedFalse)),
            "Filter\Parser doesn't parse boolean (false) conditions correctly"
        );

        $expectedNegation = QueryString::render(Filter::none(Filter::equal('foo', 'bar')));
        $this->assertEquals(
            $expectedNegation,
            QueryString::render(QueryString::parse($expectedNegation)),
            "Filter\Parser doesn't parse negated conditions correctly"
        );
    }

    public function testParserIdentifiesRelationalOperators()
    {
        $expectedEqual = QueryString::render(Filter::equal('foo', 'bar'));
        $this->assertEquals(
            $expectedEqual,
            QueryString::render(QueryString::parse($expectedEqual)),
            "Filter\Parser doesn't parse = comparisons correctly"
        );

        $expectedLike = QueryString::render(Filter::like('foo', 'ba*'));
        $this->assertEquals(
            $expectedLike,
            QueryString::render(QueryString::parse($expectedLike)),
            "Filter\Parser doesn't parse ~ comparisons correctly for wildcard characters"
        );

        $expectedUnequal = QueryString::render(Filter::unequal('foo', 'bar'));
        $this->assertEquals(
            $expectedUnequal,
            QueryString::render(QueryString::parse($expectedUnequal)),
            "Filter\Parser doesn't parse != comparisons correctly"
        );

        $expectedUnlike = QueryString::render(Filter::unlike('foo', 'ba*'));
        $this->assertEquals(
            $expectedUnlike,
            QueryString::render(QueryString::parse($expectedUnlike)),
            "Filter\Parser doesn't parse !~ comparisons correctly for wildcard characters"
        );

        $expectedGreaterThan = QueryString::render(Filter::greaterThan('length', 3));
        $this->assertEquals(
            $expectedGreaterThan,
            QueryString::render(QueryString::parse($expectedGreaterThan)),
            "Filter\Parser doesn't parse > comparisons correctly"
        );

        $expectedLessThan = QueryString::render(Filter::lessThan('length', 3));
        $this->assertEquals(
            $expectedLessThan,
            QueryString::render(QueryString::parse($expectedLessThan)),
            "Filter\Parser doesn't parse < comparisons correctly"
        );

        $expectedGreaterThanOrEqual = QueryString::render(Filter::greaterThanOrEqual('length', 3));
        $this->assertEquals(
            $expectedGreaterThanOrEqual,
            QueryString::render(QueryString::parse($expectedGreaterThanOrEqual)),
            "Filter\Parser doesn't parse >= comparisons correctly"
        );

        $expectedLessThanOrEqual = QueryString::render(Filter::lessThanOrEqual('length', 3));
        $this->assertEquals(
            $expectedLessThanOrEqual,
            QueryString::render(QueryString::parse($expectedLessThanOrEqual)),
            "Filter\Parser doesn't parse <= comparisons correctly"
        );
    }

    public function testParserHandlesEmptyValuesCorrectly()
    {
        $expectedEqualEmptyString = QueryString::render(Filter::equal('foo', ''));
        $this->assertEquals(
            $expectedEqualEmptyString,
            QueryString::render(QueryString::parse($expectedEqualEmptyString)),
            "Filter\Parser doesn't handle empty strings correctly"
        );

        $expectedEqualEmptyArray = QueryString::render(Filter::equal('foo', []));
        $this->assertEquals(
            $expectedEqualEmptyArray,
            QueryString::render(QueryString::parse($expectedEqualEmptyArray)),
            "Filter\Parser doesn't handle empty arrays correctly"
        );
    }

    public function testParserIdentifiesArrays()
    {
        $expected = QueryString::render(Filter::equal('port', [80, 8080]));

        $this->assertEquals(
            $expected,
            QueryString::render(QueryString::parse($expected)),
            "Filter\Parser doesn't parse array values correctly"
        );
    }

    public function testParserHandlesWhitespaceProperly()
    {
        $expected = QueryString::render(Filter::equal('foo', '  bar  '));

        $this->assertEquals(
            $expected,
            QueryString::render(QueryString::parse($expected)),
            "Filter\Parser doesn't parse leading/trailing whitespace in values"
        );

        $this->assertEquals(
            $expected,
            QueryString::render(QueryString::parse(' foo =  bar  ')),
            "Filter/Parser doesn't ignore leading/trailing whitespace in columns"
        );
    }

    public function testParserDecodesEncodedColumnsAndValues()
    {
        $expectedValue = QueryString::render(Filter::equal('foo', '(bär)'));
        $this->assertEquals(
            $expectedValue,
            QueryString::render(QueryString::parse($expectedValue)),
            "Filter\Parser doesn't decode values correctly"
        );

        $expectedColumn = QueryString::render(Filter::equal('(föö)', 'bar'));
        $this->assertEquals(
            $expectedColumn,
            QueryString::render(QueryString::parse($expectedColumn)),
            "Filter\Parser doesn't decode columns correctly"
        );

        $expectedArray = QueryString::render(Filter::equal('foo', ['(bär)', '(föö)']));
        $this->assertEquals(
            $expectedArray,
            QueryString::render(QueryString::parse($expectedArray)),
            "Filter\Parser doesn't decode array values correctly"
        );

        $expectedSpecialChars = QueryString::render(Filter::equal('foo', '=()&|><!'));
        $this->assertEquals(
            $expectedSpecialChars,
            QueryString::render(QueryString::parse($expectedSpecialChars)),
            "Filter\Parser doesn't decode special characters correctly"
        );
    }

    public function testParserDecodesEncodedOperators()
    {
        $expectedOperator = QueryString::render(Filter::greaterThan('foo', 'bar'));
        $this->assertEquals(
            $expectedOperator,
            QueryString::render(QueryString::parse($expectedOperator)),
            "Filter\Parser doesn't decode operators correctly"
        );

        $expectedOperator = QueryString::render(Filter::greaterThanOrEqual('foo', 'bar'));
        $this->assertEquals(
            $expectedOperator,
            QueryString::render(QueryString::parse($expectedOperator)),
            "Filter\Parser doesn't decode operators correctly"
        );

        $expectedOperator = QueryString::render(Filter::lessThan('date', '-3 days'));
        $this->assertEquals(
            $expectedOperator,
            QueryString::render(QueryString::parse($expectedOperator)),
            "Filter\Parser doesn't decode operators correctly"
        );

        $expectedOperator = QueryString::render(Filter::lessThanOrEqual('date', '-3 days'));
        $this->assertEquals(
            $expectedOperator,
            QueryString::render(QueryString::parse($expectedOperator)),
            "Filter\Parser doesn't decode operators correctly"
        );
    }

    public function testParserIdentifiesSingleChains()
    {
        $expectedAll = QueryString::render(Filter::all(
            Filter::equal('foo', 'bar'),
            Filter::equal('bar', 'foo')
        ));
        $this->assertEquals(
            $expectedAll,
            QueryString::render(QueryString::parse($expectedAll)),
            "Filter\Parser doesn't parse single AND chains correctly"
        );

        $expectedAny = QueryString::render(Filter::any(
            Filter::equal('foo', 'bar'),
            Filter::equal('bar', 'foo')
        ));
        $this->assertEquals(
            $expectedAny,
            QueryString::render(QueryString::parse($expectedAny)),
            "Filter\Parser doesn't parse single OR chains correctly"
        );
    }

    public function testParserIdentifiesMultipleChains()
    {
        $expected = QueryString::render(Filter::any(
            Filter::all(
                Filter::equal('foo', 'bar'),
                Filter::equal('bar', 'foo')
            ),
            Filter::all(
                Filter::unequal('foo', 'bar'),
                Filter::unequal('bar', 'foo')
            )
        ));

        $this->assertEquals(
            $expected,
            QueryString::render(QueryString::parse($expected)),
            "Filter\Parser doesn't parse multiple chains correctly"
        );
    }

    public function testParserIdentifiesNoneChains()
    {
        $expectedNone = QueryString::render(Filter::none(
            Filter::equal('foo', 'bar'),
            Filter::equal('bar', 'foo')
        ));
        $this->assertEquals(
            $expectedNone,
            QueryString::render(QueryString::parse($expectedNone)),
            "Filter\Parser doesn't parse NOT chains correctly"
        );

        $expectedNoneWithSingleAll = QueryString::render(Filter::none(
            Filter::all(
                Filter::equal('foo', 'bar'),
                Filter::equal('bar', 'foo')
            )
        ));
        $this->assertEquals(
            $expectedNoneWithSingleAll,
            QueryString::render(QueryString::parse($expectedNoneWithSingleAll)),
            "Filter\Parser doesn't parse NOT chains with a single AND chain correctly"
        );

        $expectedNestedNone = QueryString::render(Filter::all(
            Filter::equal('foo', 'bar'),
            Filter::none(
                Filter::equal('bar', 'foo'),
                Filter::unequal('foo', 'bar')
            )
        ));
        $this->assertEquals(
            $expectedNestedNone,
            QueryString::render(QueryString::parse($expectedNestedNone)),
            "Filter\Parser doesn't parse nested NOT chains correctly"
        );
    }

    public function testParserIdentifiesNestedChains()
    {
        $expectedTwoLevels = QueryString::render(Filter::any(
            Filter::equal('foo', 'bar'),
            Filter::all(
                Filter::any(
                    Filter::equal('foo', 'bar'),
                    Filter::equal('bar', 'foo')
                ),
                Filter::any(
                    Filter::equal('bar', 'foo'),
                    Filter::unequal('foo', 'bar')
                )
            )
        ));
        $this->assertEquals(
            $expectedTwoLevels,
            QueryString::render(QueryString::parse($expectedTwoLevels)),
            "Filter\Parser doesn't parse nested (two level) chains correctly"
        );

        $expectedThreeLevels = QueryString::render(Filter::all(
            Filter::any(
                Filter::all(
                    Filter::unequal('foo', 'bar'),
                    Filter::any(
                        Filter::equal('bar', 'foo'),
                        Filter::equal('foo', 'bar')
                    )
                ),
                Filter::all(
                    Filter::any(
                        Filter::unequal('bar', 'foo'),
                        Filter::unequal('foo', 'bar')
                    ),
                    Filter::equal('bar', 'foo')
                )
            ),
            Filter::equal('foo', 'bar')
        ));
        $this->assertEquals(
            $expectedThreeLevels,
            QueryString::render(QueryString::parse($expectedThreeLevels)),
            "Filter\Parser doesn't parse nested (three level) chains correctly"
        );
    }

    /**
     * Pretty much a combination of all preceding tests.
     * If only this fails, there's a missing/incomplete test case.
     * If another fails, this misses a test case.
     */
    public function testParserIdentifiesTheHolyGrail()
    {
        $holyGrail = QueryString::render(Filter::all(
            /* testParserIdentifiesSingleConditions */
            Filter::equal('foo', 'bar'),
            /* testParserIdentifiesBooleanConditions */
            Filter::equal('active', true),
            Filter::equal('active', false),
            /* testParserIdentifiesRelationalOperators */
            Filter::unequal('foo', 'bar'),
            Filter::like('foo', 'ba*'),
            Filter::unlike('foo', 'ba*'),
            Filter::greaterThan('length', 3),
            Filter::lessThan('length', 3),
            Filter::greaterThanOrEqual('length', 3),
            Filter::lessThanOrEqual('length', 3),
            /* testParserHandlesEmptyValuesCorrectly */
            Filter::equal('foo', ''),
            Filter::equal('foo', []),
            /* testParserIdentifiesArrays */
            Filter::equal('port', [80, 8080]),
            /* testParserHandlesWhitespaceProperly */
            Filter::equal('foo', '  bar  '),
            /* testParserDecodesEncodedColumnsAndValues */
            Filter::equal('foo', '(bär)'),
            Filter::equal('(föö)', 'bar'),
            Filter::equal('foo', ['(bär)', '(föö)']),
            Filter::equal('foo', '=()&|><!'),
            /* testParserDecodesEncodedOperators */
            Filter::greaterThan('foo', 'bar'),
            Filter::greaterThanOrEqual('foo', 'bar'),
            Filter::lessThan('date', '-3 days'),
            Filter::lessThanOrEqual('date', '-3 days'),
            /* testParserIdentifiesSingleChains */
            Filter::all(
                Filter::equal('foo', 'bar'),
                Filter::equal('bar', 'foo')
            ),
            Filter::any(
                Filter::equal('foo', 'bar'),
                Filter::equal('bar', 'foo')
            ),
            /* testParserIdentifiesMultipleChains */
            Filter::any(
                Filter::all(
                    Filter::equal('foo', 'bar'),
                    Filter::equal('bar', 'foo')
                ),
                Filter::all(
                    Filter::unequal('foo', 'bar'),
                    Filter::unequal('bar', 'foo')
                )
            ),
            /* testParserIdentifiesNoneChains */
            Filter::none(
                Filter::equal('foo', 'bar'),
                Filter::equal('bar', 'foo')
            ),
            Filter::none(
                Filter::all(
                    Filter::equal('foo', 'bar'),
                    Filter::equal('bar', 'foo')
                )
            ),
            Filter::all(
                Filter::equal('foo', 'bar'),
                Filter::none(
                    Filter::equal('bar', 'foo'),
                    Filter::unequal('foo', 'bar')
                )
            ),
            /* testParserIdentifiesNestedChains */
            Filter::any(
                Filter::equal('foo', 'bar'),
                Filter::all(
                    Filter::any(
                        Filter::equal('foo', 'bar'),
                        Filter::equal('bar', 'foo')
                    ),
                    Filter::any(
                        Filter::equal('bar', 'foo'),
                        Filter::unequal('foo', 'bar')
                    )
                )
            ),
            Filter::all(
                Filter::any(
                    Filter::all(
                        Filter::unequal('foo', 'bar'),
                        Filter::any(
                            Filter::equal('bar', 'foo'),
                            Filter::equal('foo', 'bar')
                        )
                    ),
                    Filter::all(
                        Filter::any(
                            Filter::unequal('bar', 'foo'),
                            Filter::unequal('foo', 'bar')
                        ),
                        Filter::equal('bar', 'foo')
                    )
                ),
                Filter::equal('foo', 'bar')
            )
        ));

        $this->assertEquals(
            $holyGrail,
            QueryString::render(QueryString::parse($holyGrail)),
            "Filter\Parser is unable to recognize the holy grail and falls to dust"
        );
    }

    /* All of the following tests only cover hand made query strings */

    public function testParserUnderstandsOperatorPrecedence()
    {
        $this->assertEquals(
            'foo=bar|(bar=foo&foo=bar)',
            QueryString::render(QueryString::parse('foo=bar|bar=foo&foo=bar')),
            "Filter\Parser doesn't understand that AND has a higher precedence over OR"
        );
        $this->assertEquals(
            '(foo=bar&bar=foo)|foo=bar',
            QueryString::render(QueryString::parse('foo=bar&bar=foo|foo=bar')),
            "Filter\Parser doesn't understand that AND has a higher precedence over OR"
        );
        $this->assertEquals(
            '(foo=bar|(bar=foo&foo=bar))&bar=foo',
            QueryString::render(QueryString::parse('(foo=bar|bar=foo&foo=bar)&bar=foo')),
            "Filter\Parser improperly handles operator precedence in nested chains"
        );
    }

    public function testParserIgnoresRedundantOperators()
    {
        $this->assertEquals(
            'foo=bar&bar=foo',
            QueryString::render(QueryString::parse('foo=bar&&bar=foo')),
            "Filter\Parser doesn't ignore a single redundant logical operator (AND)"
        );
        $this->assertEquals(
            'foo=bar|bar=foo',
            QueryString::render(QueryString::parse('foo=bar&|bar=foo')),
            "Filter\Parser doesn't ignore a single redundant logical operator (AND)"
        );
        $this->assertEquals(
            '(foo=bar&bar=foo)',
            QueryString::render(QueryString::parse('foo=bar|&bar=foo')),
            "Filter\Parser doesn't ignore a single redundant logical operator (OR)"
        );
        $this->assertEquals(
            'foo=bar&bar=foo',
            QueryString::render(QueryString::parse('foo=bar&&&bar=foo')),
            "Filter\Parser doesn't ignore multiple redundant logical operators (AND)"
        );
        $this->assertEquals(
            'foo=bar|bar=foo',
            QueryString::render(QueryString::parse('foo=bar|||||bar=foo')),
            "Filter\Parser doesn't ignore multiple redundant logical operators (OR)"
        );
    }

    public function testParserIgnoresRedundantParentheses()
    {
        $this->assertEquals(
            'foo=bar|bar=foo',
            QueryString::render(QueryString::parse('(foo=bar|bar=foo)')),
            "Filter\Parser doesn't ignore parentheses at root level"
        );
        $this->assertEquals(
            '!(foo=bar|bar=foo)',
            QueryString::render(QueryString::parse('!((foo=bar|bar=foo))')),
            "Filter\Parser doesn't ignore parentheses of an OR chain inside a NOT"
        );
        $this->assertEquals(
            'foo=bar|bar=foo',
            QueryString::render(QueryString::parse('((foo=bar|bar=foo))')),
            "Filter\Parser doesn't ignore parentheses at sub-root level"
        );
        $this->assertEquals(
            'foo=bar&!(!(!bar=foo&foo=bar))&!bar=foo',
            QueryString::render(QueryString::parse('foo=bar&!((!((!(bar=foo)&foo=bar))))&!bar=foo')),
            "Filter\Parser doesn't ignore nested redundant parentheses mixed with single element NOTs"
        );
    }

    public function testParserHandlesMissingParentheses()
    {
        $this->assertEquals(
            'foo=bar|(bar=foo&foo=bar)',
            QueryString::render(QueryString::parse('foo=bar|(bar=foo&(foo=bar|!(')),
            ""
        );
        $this->assertEquals(
            'foo=bar|(bar=foo&(foo=bar|bar=foo))',
            QueryString::render(QueryString::parse('foo=bar|(bar=foo&(foo=bar|bar=foo)&(')),
            ""
        );
    }

    /* Strict mode tests */

    public function testRendererDrawsRedundantCharsInStrictMode()
    {
        $rootChainOnlyWithASingleCondition = Filter::all(
            Filter::equal('foo', 'bar')
        );
        $this->assertEquals(
            '(foo=bar)',
            (new Renderer($rootChainOnlyWithASingleCondition))->setStrict()->render(),
            "Filter\Renderer doesn't draw parentheses for the root chain"
        );

        $rootChainOnlyWithMultipleConditions = Filter::all(
            Filter::equal('foo', 'bar'),
            Filter::equal('bar', 'foo')
        );
        $this->assertEquals(
            '(foo=bar&bar=foo)',
            (new Renderer($rootChainOnlyWithMultipleConditions))->setStrict()->render(),
            "Filter\Renderer doesn't draw parentheses for the root chain"
        );

        $nestedAllWithOneCondition = Filter::all(
            Filter::equal('foo', 'bar'),
            Filter::all(
                Filter::equal('bar', 'foo')
            )
        );
        $this->assertEquals(
            '(foo=bar&(bar=foo))',
            (new Renderer($nestedAllWithOneCondition))->setStrict()->render(),
            "Filter\Renderer doesn't draw parentheses for nested chains with a single condition"
        );

        $nestedAnyWithOneCondition = Filter::all(
            Filter::equal('foo', 'bar'),
            Filter::any(
                Filter::equal('bar', 'foo')
            )
        );
        $this->assertEquals(
            '(foo=bar&(bar=foo|))',
            (new Renderer($nestedAnyWithOneCondition))->setStrict()->render(),
            "Filter\Renderer doesn't draw group operator for nested OR chains with a single condition"
        );

        $nestedEmptyAll = Filter::all(
            Filter::equal('foo', 'bar'),
            Filter::all()
        );
        $this->assertEquals(
            '(foo=bar&())',
            (new Renderer($nestedEmptyAll))->setStrict()->render(),
            "Filter\Renderer doesn't draw parentheses for empty nested chains"
        );

        $nestedEmptyAllWithConditionOnSameLevel = Filter::all(
            Filter::equal('foo', 'bar'),
            Filter::all(
                Filter::all(),
                Filter::equal('bar', 'foo')
            )
        );
        $this->assertEquals(
            '(foo=bar&(()&bar=foo))',
            (new Renderer($nestedEmptyAllWithConditionOnSameLevel))->setStrict()->render(),
            "Filter\Renderer doesn't draw parentheses for empty nested chains with non-empty siblings"
        );

        $nestedEmptyAny = Filter::all(
            Filter::equal('foo', 'bar'),
            Filter::any()
        );
        $this->assertEquals(
            '(foo=bar&(|))',
            (new Renderer($nestedEmptyAny))->setStrict()->render(),
            "Filter\Renderer doesn't draw group operator for empty nested OR chains"
        );

        $nestedEmptyAnyWithConditionOnSameLevel = Filter::all(
            Filter::equal('foo', 'bar'),
            Filter::all(
                Filter::any(),
                Filter::equal('bar', 'foo')
            )
        );
        $this->assertEquals(
            '(foo=bar&((|)&bar=foo))',
            (new Renderer($nestedEmptyAnyWithConditionOnSameLevel))->setStrict()->render(),
            "Filter\Renderer doesn't draw group operator for empty nested OR chains with non-empty siblings"
        );
    }

    /**
     * @depends testRendererDrawsRedundantCharsInStrictMode
     */
    public function testParserRespectsRedundantCharsInStrictMode()
    {
        $this->assertEquals(
            '(foo=bar)',
            (new Renderer((new Parser('(foo=bar)'))->setStrict()->parse()))->setStrict()->render(),
            "Filter\Parser doesn't respect parentheses for the root chain"
        );
        $this->assertEquals(
            '(foo=bar&bar=foo)',
            (new Renderer((new Parser('(foo=bar&bar=foo)'))->setStrict()->parse()))->setStrict()->render(),
            "Filter\Parser doesn't respect parentheses for the root chain"
        );
        $this->assertEquals(
            '(foo=bar&(bar=foo))',
            (new Renderer((new Parser('(foo=bar&(bar=foo))'))->setStrict()->parse()))->setStrict()->render(),
            "Filter\Parser doesn't respect parentheses for nested chains with a single condition"
        );
        $this->assertEquals(
            '(foo=bar&(bar=foo|))',
            (new Renderer((new Parser('(foo=bar&(bar=foo|))'))->setStrict()->parse()))->setStrict()->render(),
            "Filter\Parser doesn't respect group operator for nested OR chains with a single condition"
        );
        $this->assertEquals(
            '(foo=bar&())',
            (new Renderer((new Parser('(foo=bar&())'))->setStrict()->parse()))->setStrict()->render(),
            "Filter\Parser doesn't respect parentheses for empty nested chains"
        );
        $this->assertEquals(
            '(foo=bar&(()&bar=foo))',
            (new Renderer((new Parser('(foo=bar&(()&bar=foo))'))->setStrict()->parse()))->setStrict()->render(),
            "Filter\Parser doesn't respect parentheses for empty nested chains with non-empty siblings"
        );
        $this->assertEquals(
            '(foo=bar&(|))',
            (new Renderer((new Parser('(foo=bar&(|))'))->setStrict()->parse()))->setStrict()->render(),
            "Filter\Parser doesn't respect group operator for empty nested OR chains"
        );
        $this->assertEquals(
            '(foo=bar&((|)&bar=foo))',
            (new Renderer((new Parser('(foo=bar&((|)&bar=foo))'))->setStrict()->parse()))->setStrict()->render(),
            "Filter\Parser doesn't respect group operator for empty nested OR chains with non-empty siblings"
        );
    }

    /* Non-Strict mode tests */

    public function testRendererDoesNotDrawRedundantCharsInNonStrictMode()
    {
        $this->assertEquals(
            '',
            (new Renderer(Filter::all()))->render(),
            "Filter\Renderer draws redundant parentheses for an empty root chain"
        );
        $this->assertEquals(
            'foo=bar',
            (new Renderer(Filter::equal('foo', 'bar')))->render(),
            "Filter\Renderer draws redundant parentheses for a root chain with a single condition"
        );
        $this->assertEquals(
            'foo=bar&bar=foo',
            (new Renderer(Filter::all(
                Filter::equal('foo', 'bar'),
                Filter::equal('bar', 'foo')
            )))->render(),
            "Filter\Renderer draws redundant parentheses for a root chain with multiple conditions"
        );
        $this->assertEquals(
            'foo=bar&bar=foo',
            (new Renderer(Filter::all(
                Filter::equal('foo', 'bar'),
                Filter::all(
                    Filter::equal('bar', 'foo')
                )
            )))->render(),
            "Filter\Renderer draws redundant parentheses for nested chains with a single condition"
        );
        $this->assertEquals(
            'foo=bar&bar=foo',
            (new Renderer(Filter::all(
                Filter::equal('foo', 'bar'),
                Filter::any(
                    Filter::equal('bar', 'foo')
                )
            )))->render(),
            "Filter\Renderer draws redundant group operator for nested OR chains with a single condition"
        );
        $this->assertEquals(
            'foo=bar',
            (new Renderer(Filter::all(
                Filter::equal('foo', 'bar'),
                Filter::all()
            )))->render(),
            "Filter\Renderer draws redundant parentheses for empty nested chains"
        );
        $this->assertEquals(
            'foo=bar&(bar=foo)',
            (new Renderer(Filter::all(
                Filter::equal('foo', 'bar'),
                Filter::all(
                    Filter::all(),
                    Filter::equal('bar', 'foo')
                )
            )))->render(),
            "Filter\Renderer draws redundant parentheses for empty nested chains with non-empty siblings"
        );
        $this->assertEquals(
            'foo=bar',
            (new Renderer(Filter::all(
                Filter::equal('foo', 'bar'),
                Filter::any()
            )))->render(),
            "Filter\Renderer draws redundant group operator for empty nested OR chains"
        );
        $this->assertEquals(
            'foo=bar&(bar=foo)',
            (new Renderer(Filter::all(
                Filter::equal('foo', 'bar'),
                Filter::any(
                    Filter::any(),
                    Filter::equal('bar', 'foo')
                )
            )))->render(),
            "Filter\Renderer draws redundant group operator for empty nested OR chains with non-empty siblings"
        );
    }
}
