<?php

namespace ipl\Tests\Web;

use Generator;
use ipl\Stdlib\Filter;
use ipl\Web\Filter\ParseException;
use ipl\Web\Filter\Parser;

class ParserTest extends TestCase
{
    public function testMissingLogicalOperatorsAfterConditionsAreDetected(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage(
            'Invalid filter "(a=b|c=d)e=f", unexpected e at pos 9: Expected logical operator'
        );

        (new Parser('(a=b|c=d)e=f'))->parse();
    }

    public function testMissingLogicalOperatorsAfterOperatorsAreDetected(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage(
            'Invalid filter "(a=b|c=d|)e=f", unexpected e at pos 10: Expected logical operator'
        );

        (new Parser('(a=b|c=d|)e=f'))->parse();
    }

    public function testInvalidComparisonOperatorAreDetected(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage(
            'Invalid filter "foo!bar", unexpected b at pos 4: Invalid operator in column expression'
        );

        (new Parser('foo!bar'))->parse();
    }

    public function testUnclosedParenthesesAreDetected(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage(
            'Invalid filter "a=(b", unexpected EOL at pos 4: Expected ")"'
        );

        (new Parser('a=(b'))->parse();
    }

    public function testUnexpectedClosingParenthesesAreDetected(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage(
            'Invalid filter "a=b)", unexpected ) at pos 3'
        );

        (new Parser('a=b)'))->parse();
    }

    public function testMissingColumnsAreDetected(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage(
            'Invalid filter "!=b", unexpected = at pos 1: level 0'
        );

        (new Parser('!=b'))->parse();
    }

    public function testMissingColumnsInNegatedChainsAreDetected(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage(
            'Invalid filter "a>4|!(=b=c)", unexpected = at pos 6: ! level 1'
        );

        (new Parser('a>4|!(=b=c)'))->parse();
    }

    public function testSimpleIndexRecognition(): void
    {
        $filter = (new Parser('a=b&c=d|e=|g'))->parse();
        $indices = iterator_to_array($this->extractIndices($filter));

        $this->assertEquals(
            [0, 1, 2, 4, 5, 6, 8, 9, 11],
            $indices
        );
    }

    public function testNestedIndexRecognition(): void
    {
        $filter = (new Parser('a=b&(c=d|(e=f&g=h))'))->parse();
        $indices = iterator_to_array($this->extractIndices($filter));

        $this->assertEquals(
            [0, 1, 2, 5, 6, 7, 10, 11, 12, 14, 15, 16],
            $indices
        );

        $filter = (new Parser('(a=b&(c=d|(e=f&g=h)))'))->parse();
        $indices = iterator_to_array($this->extractIndices($filter));

        $this->assertEquals(
            [1, 2, 3, 6, 7, 8, 11, 12, 13, 15, 16, 17],
            $indices
        );
    }

    protected function extractIndices(Filter\Rule $filter): Generator
    {
        if ($filter instanceof Filter\Condition) {
            $columnIndex = $filter->metaData()->get('columnIndex');
            if ($columnIndex !== null) {
                yield $columnIndex;
            }

            $operatorIndex = $filter->metaData()->get('operatorIndex');
            if ($operatorIndex !== null) {
                yield $operatorIndex;
            }

            $valueIndex = $filter->metaData()->get('valueIndex');
            if ($valueIndex) {
                yield $valueIndex;
            }
        } else {
            foreach ($filter as $rule) {
                foreach ($this->extractIndices($rule) as $index) {
                    yield $index;
                }
            }
        }
    }
}
