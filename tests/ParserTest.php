<?php

namespace ipl\Tests\Web;

use ipl\Stdlib\Filter;
use ipl\Web\Filter\ParseException;
use ipl\Web\Filter\Parser;

class ParserTest extends TestCase
{
    public function testMissingLogicalOperatorsAfterConditionsAreDetected()
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage(
            'Invalid filter "(a=b|c=d)e=f", unexpected e at pos 10: Expected logical operator'
        );

        (new Parser('(a=b|c=d)e=f'))->parse();
    }

    public function testMissingLogicalOperatorsAfterOperatorsAreDetected()
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage(
            'Invalid filter "(a=b|c=d|)e=f", unexpected e at pos 11: Expected logical operator'
        );

        (new Parser('(a=b|c=d|)e=f'))->parse();
    }

    public function testSimpleIndexRecognition()
    {
        $filter = (new Parser('a=b&c=d|e=|g'))->parse();
        $indices = iterator_to_array($this->extractIndices($filter));

        $this->assertEquals(
            [0, 1, 2, 4, 5, 6, 8, 9, 11],
            $indices
        );
    }

    public function testNestedIndexRecognition()
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

    protected function extractIndices(Filter\Rule $filter)
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
