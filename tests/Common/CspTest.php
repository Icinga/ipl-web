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
}
