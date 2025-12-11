<?php

namespace ipl\Tests\Web;

use ipl\I18n\NoopTranslator;
use ipl\I18n\StaticTranslator;
use ipl\Web\Control\SortControl;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;

class SortControlTest extends TestCase
{
    protected function setUp(): void
    {
        StaticTranslator::$instance = new NoopTranslator();
    }

    public function testCustomDefaultIsSelected(): void
    {
        $request = $this->createConfiguredMock(ServerRequestInterface::class, [
            'getMethod' => 'GET',
            'getUploadedFiles' => [],
            'getUri' => $this->createConfiguredMock(UriInterface::class, [
                'getQuery' => ''
            ])
        ]);

        $control = SortControl::create(['foo asc' => 'Foo', 'bar desc' => 'Bar']);
        $control->handleRequest($request);
        $control->setDefault('bar desc');

        $this->assertMatchesRegularExpression(
            '/value="bar desc"[^>]+selected/',
            $control->render(),
            'The default sort column is not selected'
        );
    }
}
