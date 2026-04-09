<?php

namespace ipl\Tests\Web\Less;

use Less_Exception_Parser;
use Less_Parser;
use PHPUnit\Framework\TestCase;

abstract class LessVisitorTestCase extends TestCase
{
    /**
     * Assert that Less source compiles to the expected CSS with the given visitor plugins
     *
     * @param string $expectedCss
     * @param string $actualLess
     * @param list<object> $plugins Visitor plugins to register with the parser
     *
     * @return void
     */
    protected function assertCss(string $expectedCss, string $actualLess, array $plugins = []): void
    {
        try {
            (new Less_Parser())->parse($expectedCss);
        } catch (Less_Exception_Parser $e) {
            $this->fail("Expected CSS is not valid: {$e->getMessage()}");
        }

        try {
            $parser = new Less_Parser(['plugins' => $plugins]);
            $actualCss = $parser->parse($actualLess)->getCss();
        } catch (Less_Exception_Parser $e) {
            $this->fail("Actual Less is not valid: {$e->getMessage()}");
        }

        $this->assertSame(trim($expectedCss), trim($actualCss));
    }
}
