<?php

namespace ipl\Tests\Web;

use Icinga\Web\Request;
use ipl\Stdlib\Filter;
use ipl\Web\Url;

class UrlTest extends TestCase
{
    protected function setUp(): void
    {
        if (! class_exists('Icinga\Web\Request')) {
            $this->markTestSkipped('UrlTest only runs locally');
        }
    }

    public function testSetFilterRendersFilterCorrectly()
    {
        $url = Url::fromPath('test', [], $this->createRequestMock());

        $url->setFilter(Filter::any(
            Filter::equal('foo', 'bar'),
            Filter::equal('bar', 'foo')
        ));

        $this->assertSame(
            '/test?foo=bar|bar=foo',
            $url->getAbsoluteUrl()
        );
    }

    public function testSetFilterPreservesExistingParameters()
    {
        $url = Url::fromPath('test', ['oof' => 'rab'], $this->createRequestMock());

        $url->setFilter(Filter::any(
            Filter::equal('foo', 'bar'),
            Filter::equal('bar', 'foo')
        ));

        $this->assertSame(
            '/test?foo=bar|bar=foo&oof=rab',
            $url->getAbsoluteUrl()
        );
    }

    /** @depends testSetFilterPreservesExistingParameters */
    public function testSetFilterAcceptsNullAndStillPreservesExistingParameters()
    {
        $url = Url::fromPath('test', ['oof' => 'rab'], $this->createRequestMock());

        $url->setFilter(Filter::equal('bar', 'foo'));
        $url->setFilter(null);

        $this->assertSame(
            '/test?oof=rab',
            $url->getAbsoluteUrl()
        );
    }

    /** @depends testSetFilterPreservesExistingParameters */
    public function testSetFilterOverridesCurrentFilterButKeepsOtherParameters()
    {
        $url = Url::fromPath('test', ['oof' => 'rab'], $this->createRequestMock());

        $url->setFilter(Filter::equal('bar', 'foo'));
        $url->setFilter(Filter::equal('foo', 'bar'));

        $this->assertSame(
            '/test?foo=bar&oof=rab',
            $url->getAbsoluteUrl()
        );
    }

    protected function createRequestMock()
    {
        return $this->createConfiguredMock(Request::class, [
            'getBaseUrl' => '/'
        ]);
    }
}
