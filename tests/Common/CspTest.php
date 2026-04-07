<?php

namespace ipl\Tests\Web\Common;

use InvalidArgumentException;
use ipl\Tests\Web\TestCase;
use ipl\Web\Common\Csp;

class CspTest extends TestCase
{
    public function testEmpty()
    {
        $csp = new Csp();

        $this->assertInstanceOf(Csp::class, $csp);
        $this->assertTrue($csp->isEmpty());

        $csp->add('foo', 'bar');

        $this->assertFalse($csp->isEmpty());
    }

    public function testAddString()
    {
        $csp = new Csp();

        $csp->add('script-src', 'https://example.com');

        $this->assertEquals(['https://example.com'], $csp->getDirective('script-src'));
    }

    public function testAddStringEmpty()
    {
        $csp = new Csp();

        $csp->add('script-src', '');

        $this->assertTrue($csp->isEmpty());
    }

    public function testAddStringTrim()
    {
        $csp = new Csp();

        $csp->add('script-src', ' https://example.com');

        $this->assertEquals(['https://example.com'], $csp->getDirective('script-src'));
    }

    public function testAddStringDuplicate()
    {
        $csp = new Csp();

        $csp->add('script-src', 'https://example.com');
        $csp->add('script-src', 'https://example.com');

        $this->assertEquals(['https://example.com'], $csp->getDirective('script-src'));
    }

    public function testAddStringCombined()
    {
        $csp = new Csp();

        $csp->add('script-src', 'https://example.com https://example.org');

        $this->assertEquals(['https://example.com', 'https://example.org'], $csp->getDirective('script-src'));
    }

    public function testAddArray()
    {
        $csp = new Csp();

        $csp->add('img-src', ['https://example.com', 'https://example.org', 'https://example.com']);

        $this->assertEquals(['https://example.com', 'https://example.org'], $csp->getDirective('img-src'));
    }

    public function testAddDefaultSource()
    {
        $this->expectException(InvalidArgumentException::class);

        $csp = new Csp();

        $csp->add('default-src', 'https://example.com');
    }

    public function testFallbackToDefault()
    {
        $csp = new Csp();

        $this->assertEquals(["'self'"], $csp->getDirective('script-src'));
    }

    public function testAddDirectiveNameCapitals()
    {
        $this->expectException(InvalidArgumentException::class);

        $csp = new Csp();

        $csp->add('Default-src', 'https://example.com');
    }

    public function testAddDirectiveNameSpecialCharacters()
    {
        $this->expectException(InvalidArgumentException::class);

        $csp = new Csp();

        $csp->add('default-src:', 'https://example.com');
    }

    public function testAddWildcardEverything()
    {
        $csp = new Csp();
        $csp->add('script-src', '*');

        $this->assertEquals(['*'], $csp->getDirective('script-src'));
    }

    public function testAddWildcard()
    {
        $csp = new Csp();
        $csp->add('script-src', 'https://*.example.com');
        $csp->add('script-src', 'https://*.int.example.com');

        $this->assertEquals(
            ['https://*.example.com', 'https://*.int.example.com'],
            $csp->getDirective('script-src'),
        );
    }

    public function testAddMissingEndQuote()
    {
        $this->expectException(InvalidArgumentException::class);

        $csp = new Csp();

        $csp->add('script-src', "'self");
    }

    public function testAddMissingStartQuote()
    {
        $this->expectException(InvalidArgumentException::class);

        $csp = new Csp();

        $csp->add('script-src', "self'");
    }

    public function testAddScheme()
    {
        $csp = new Csp();
        $csp->add('script-src', 'https://*');
        $csp->add('script-src', 'http:');

        $this->assertEquals(['https://*', 'http:'], $csp->getDirective('script-src'));
    }

    public function testAddReportingName()
    {
        $csp = new Csp();

        $csp->add('report-to', 'reporting-endpoint');

        $this->assertEquals(['reporting-endpoint'], $csp->getDirective('report-to'));
    }

    /**
     * @dataProvider providerInvalidWildcards
     */
    public function testAddInvalidWildcard(string $policy)
    {
        $this->expectException(InvalidArgumentException::class);

        $csp = new Csp();

        $csp->add('script-src', $policy);
    }

    public function providerInvalidWildcards(): array {
        return [
            ['https://example.com*'],
            ['https://a*.example.com'],
            ['https://*c.example.com'],
            ['https://a*c.example.com'],
            ['https://a*.int.example.com'],
            ['https://*c.int.example.com'],
            ['https://a*c.int.example.com'],
            ['https://int.a*.example.com'],
            ['https://int.*c.example.com'],
            ['https://int.a*c.example.com'],
            ['https://example.*'],
            ['https://example.*om'],
            ['https://example.c*m'],
            ['https://example.co*'],
            ['https://exa*ple.com'],
        ];
    }

    public function testGetDirectives()
    {
        $csp = new Csp();
        $csp->add('script-src', 'https://example.com');
        $csp->add('imc-src', "'self'");

        $this->assertEquals(
            ['script-src' => ['https://example.com'], 'imc-src' => ["'self'"]],
            $csp->getDirectives(),
        );
    }

    public function testGetHeader()
    {
        $csp = new Csp();
        $csp->add('script-src', 'https://example.com');
        $csp->add('imc-src', "'none'");

        $this->assertEquals(
            "default-src 'self'; script-src https://example.com; imc-src 'none'",
            $csp->getHeader()
        );

        $this->assertEquals(
            $csp->getHeader(),
            (string) $csp,
        );
    }

    public function testNonce()
    {
        $csp = new Csp();

        $csp->add('style-src', "'nonce-example'");

        $this->assertEquals('example', $csp->getNonce());
    }

    public function testNonceEmpty()
    {
        $this->expectException(InvalidArgumentException::class);

        $csp = new Csp();
        $csp->add('style-src', "'nonce-'");
    }

    public function testFromString()
    {
        $csp = Csp::fromString(" script-src 'nonce-example';\n\n\r\nimg-src ");

        $this->assertEquals(
            [
                'script-src' => ["'nonce-example'"],
            ],
            $csp->getDirectives(),
        );
    }

    public function testEvaluateWildcardEverything()
    {
        $csp = new Csp();
        $csp->add('script-src', '*');

        $this->assertTrue($csp->evaluateUrl('script-src', 'https://example.com'));
    }

    public function testEvaluateNone()
    {
        $csp = new Csp();
        $csp->add('script-src', "'none'");

        $this->assertFalse($csp->evaluateUrl('script-src', 'https://example.com'));
        $this->assertFalse($csp->evaluateUrl('script-src', 'http://example.com'));
        $this->assertFalse($csp->evaluateUrl('script-src', 'test'));
    }

    public function testEvaluateNoneWithMultiplePolicies()
    {
        $csp = new Csp();
        $csp->add('script-src', "'none'");
        $csp->add('script-src', 'https://example.com');

        $this->assertTrue($csp->evaluateUrl('script-src', 'https://example.com'));
        $this->assertFalse($csp->evaluateUrl('script-src', 'https://foo.com'));
    }

    public function testEvaluateSelf()
    {
        $csp = new Csp();
        $csp->add('script-src', "'self'");

        // Note: This works because the request url for unit tests is always http://localhost
        $this->assertTrue($csp->evaluateUrl('script-src', 'http://localhost'));
    }

    public function testEvaluateSchema()
    {
        $csp = new Csp();
        $csp->add('script-src', 'https:');

        $this->assertTrue($csp->evaluateUrl('script-src', 'https://example.com'));
        $this->assertTrue($csp->evaluateUrl('script-src', 'https://int.example.com'));
        $this->assertTrue($csp->evaluateUrl('script-src', 'https://icinga.com'));
        $this->assertFalse($csp->evaluateUrl('script-src', 'http://example.com'));
    }

    public function testEvaluateWildcardSchema()
    {
        $csp = new Csp();
        $csp->add('script-src', 'https://*');

        $this->assertTrue($csp->evaluateUrl('script-src', 'https://example.com'));
        $this->assertTrue($csp->evaluateUrl('script-src', 'https://int.example.com'));
        $this->assertTrue($csp->evaluateUrl('script-src', 'https://icinga.com'));
        $this->assertFalse($csp->evaluateUrl('script-src', 'http://example.com'));
    }

    public function testEvaluatePathDirectory()
    {
        $csp = new Csp();
        $csp->add('frame-src', 'https://example.com/blog/');

        $this->assertTrue($csp->evaluateUrl('frame-src', 'https://example.com/blog/some-article.html'));
        $this->assertTrue($csp->evaluateUrl('frame-src', 'https://example.com/blog/year/month/date/some-article.html'));
        $this->assertFalse($csp->evaluateUrl('frame-src', 'http://example.com/blog/'));
        $this->assertFalse($csp->evaluateUrl('frame-src', 'http://example.com/blog'));
        $this->assertFalse($csp->evaluateUrl('frame-src', 'http://example.com'));
    }

    public function testEvaluatePathFile()
    {
        $csp = new Csp();
        $csp->add('frame-src', 'https://example.com/blog/some-article.html');

        $this->assertTrue($csp->evaluateUrl('frame-src', 'https://example.com/blog/some-article.html'));
        $this->assertFalse($csp->evaluateUrl('frame-src', 'https://blog.example.com/blog/some-article.html'));
        $this->assertFalse($csp->evaluateUrl('frame-src', 'https://example.com/blog/another-article.html'));
        $this->assertFalse($csp->evaluateUrl('frame-src', 'https://example.com/feed/some-article.html'));
        $this->assertFalse($csp->evaluateUrl('frame-src', 'https://example.com/feed/some-article'));
        $this->assertFalse($csp->evaluateUrl('frame-src', 'https://icinga.com/blog/some-article.html'));
    }

    public function testEvaluateWildcardHost()
    {
        $csp = new Csp();
        $csp->add('frame-src', 'https://*.example.com');

        $this->assertTrue($csp->evaluateUrl('frame-src', 'https://example.com'));
        $this->assertTrue($csp->evaluateUrl('frame-src', 'https://cdn.example.com'));
        $this->assertTrue($csp->evaluateUrl('frame-src', 'https://monitoring.int.example.com'));
        $this->assertFalse($csp->evaluateUrl('frame-src', 'https://icinga.com'));
    }
}
