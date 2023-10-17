<?php

namespace ipl\Tests\Web;

use ipl\Stdlib\Filter;
use ipl\Web\Common\StateBadges;
use ipl\Web\Filter\QueryString;
use ipl\Web\Url;

class StateBadgesTest extends TestCase
{
    public function testCreateLinkRendersBaseFilterCorrectly()
    {
        if (! class_exists('\Icinga\Web\Url')) {
            $this->markTestSkipped('Icinga Web is required to run this test');
        }

        $stateBadges = $this->createStateBadges()
            ->setBaseFilter(Filter::any(
                Filter::equal('foo', 'bar'),
                Filter::equal('bar', 'foo')
            ));

        $link = $stateBadges->createLink('test', ['rab' => 'oof']);

        $this->assertSame(
            'rab=oof&(foo=bar|bar=foo)',
            $link->getUrl()->getQueryString()
        );
    }

    private function createStateBadges()
    {
        $queryString = null;

        $urlMock = $this->createMock(Url::class);
        $urlMock->method('setFilter')->willReturnCallback(
            function ($filter) use ($urlMock, &$queryString) {
                $queryString = QueryString::render($filter);

                return $urlMock;
            }
        );
        $urlMock->method('getQueryString')->willReturnCallback(
            function () use (&$queryString) {
                return $queryString;
            }
        );

        return new class ($urlMock) extends StateBadges {
            private $urlMock;

            public function __construct($urlMock)
            {
                $this->urlMock = $urlMock;

                parent::__construct((object) []);
            }

            protected function getBaseUrl(): Url
            {
                return $this->urlMock;
            }

            protected function getType(): string
            {
                return 'test';
            }

            protected function getPrefix(): string
            {
                return 'Test';
            }

            protected function getStateInt(string $state): int
            {
                return 0;
            }
        };
    }
}
