<?php

namespace ipl\Tests\Web\Common;

use InvalidArgumentException;
use ipl\Tests\Web\TestCase;
use ipl\Web\Common\Csp;
use PHPUnit\Framework\Attributes\DataProvider;

class CspTest extends TestCase
{
    public function testEmpty(): void
    {
        $csp = new Csp();

        $this->assertTrue($csp->isEmpty());

        $csp->add('script-src', "'self'");

        $this->assertFalse($csp->isEmpty());
    }

    public function testAddString(): void
    {
        $csp = new Csp();

        $csp->add('script-src', 'https://example.com');

        $this->assertEquals(['https://example.com'], $csp->getDirective('script-src'));
    }

    public function testAddStringEmpty(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $csp = new Csp();

        $csp->add('script-src', '');
    }

    public function testAddNull(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $csp = new Csp();

        $csp->add('script-src', null);
    }

    public function testAddNullOnAllowedEmptyDirective(): void
    {
        $csp = new Csp();

        $csp->add('sandbox', null);

        $this->assertEquals([], $csp->getDirective('sandbox'));
    }

    public function testAddNullOnMandatoryEmptyDirective(): void
    {
        $csp = new Csp();

        $csp->add('block-all-mixed-content', null);

        $this->assertEquals([], $csp->getDirective('block-all-mixed-content'));
    }

    public function testAddStringOnMandatoryEmptyDirective(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $csp = new Csp();

        $csp->add('block-all-mixed-content', 'example');
    }

    public function testAddStringTrim(): void
    {
        $csp = new Csp();

        $csp->add('script-src', ' https://example.com');

        $this->assertEquals(['https://example.com'], $csp->getDirective('script-src'));
    }

    public function testAddStringDuplicate(): void
    {
        $csp = new Csp();

        $csp->add('script-src', 'https://example.com');
        $csp->add('script-src', 'https://example.com');

        $this->assertEquals(['https://example.com'], $csp->getDirective('script-src'));
    }

    public function testAddStringCombined(): void
    {
        $csp = new Csp();

        $csp->add('script-src', 'https://example.com https://example.org');

        $this->assertEquals(['https://example.com', 'https://example.org'], $csp->getDirective('script-src'));
    }

    public function testAddStringCombinedMultipleSpaces(): void
    {
        $csp = new Csp();

        $csp->add('script-src', 'https://example.com     https://example.org');

        $this->assertEquals(['https://example.com', 'https://example.org'], $csp->getDirective('script-src'));
    }

    public function testAddArray(): void
    {
        $csp = new Csp();

        $csp->add('img-src', ['https://example.com', 'https://example.org', 'https://example.com']);

        $this->assertEquals(['https://example.com', 'https://example.org'], $csp->getDirective('img-src'));
    }

    public function testFallbackToDefault(): void
    {
        $csp = new Csp();
        $csp->add('default-src', "'self'");

        $this->assertEquals(["'self'"], $csp->getDirective('script-src'));
    }

    public function testAddDirectiveNameCapitals(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $csp = new Csp();

        $csp->add('Default-src', 'https://example.com');
    }

    public function testAddDirectiveNameSpecialCharacters(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $csp = new Csp();

        $csp->add('default-src:', 'https://example.com');
    }

    public function testAddWildcardEverything(): void
    {
        $csp = new Csp();
        $csp->add('script-src', '*');

        $this->assertEquals(['*'], $csp->getDirective('script-src'));
    }

    public function testAddWildcard(): void
    {
        $csp = new Csp();
        $csp->add('script-src', 'https://*.example.com');
        $csp->add('script-src', 'https://*.int.example.com');

        $this->assertEquals(
            ['https://*.example.com', 'https://*.int.example.com'],
            $csp->getDirective('script-src'),
        );
    }

    public function testAddMissingEndQuote(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $csp = new Csp();

        $csp->add('script-src', "'self");
    }

    public function testAddMissingStartQuote(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $csp = new Csp();

        $csp->add('script-src', "self'");
    }

    public function testAddSchemeWithoutWildcardOrHostThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $csp = new Csp();
        $csp->add('script-src', 'https://');
    }

    public function testAddScheme(): void
    {
        $csp = new Csp();
        $csp->add('script-src', 'https://*');
        $csp->add('script-src', 'http:');

        $this->assertEquals(['https://*', 'http:'], $csp->getDirective('script-src'));
    }

    public function testAddReportingName(): void
    {
        $csp = new Csp();

        $csp->add('report-to', 'reporting-endpoint');

        $this->assertEquals(['reporting-endpoint'], $csp->getDirective('report-to'));
    }

    public function testAddNumericLikeReportingNamesAreDistinct(): void
    {
        $csp = new Csp();

        $csp->add('report-to', '0');
        $csp->add('report-to', '0e123');

        $this->assertEquals(['0', '0e123'], $csp->getDirective('report-to'));
    }

    #[DataProvider('providerInvalidWildcards')]
    public function testAddInvalidWildcard(string $policy): void
    {
        $this->expectException(InvalidArgumentException::class);

        $csp = new Csp();

        $csp->add('script-src', $policy);
    }

    /**
     * @return array<list<string>>
     */
    public static function providerInvalidWildcards(): array
    {
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
            ['https://*.*.example.com'],
            ['https://example.com/path*'],
            ['https://example.com:*/path*'],
            ['https://example.com:*/*'],
        ];
    }

    public function testGetDirectives(): void
    {
        $csp = new Csp();
        $csp->add('script-src', 'https://example.com');
        $csp->add('img-src', "'self'");

        $this->assertEquals(
            ['script-src' => ['https://example.com'], 'img-src' => ["'self'"]],
            $csp->getDirectives(),
        );
    }

    public function testGetHeader(): void
    {
        $csp = new Csp();
        $csp->add('script-src', 'https://example.com');
        $csp->add('img-src', "'none'");

        $this->assertEquals(
            "script-src https://example.com; img-src 'none'",
            $csp->getHeader()
        );

        $this->assertEquals(
            $csp->getHeader(),
            (string) $csp,
        );
    }

    public function testGetHeaderWithNullableDirectives(): void
    {
        $csp = new Csp();
        $csp->add('sandbox', null);

        $this->assertEquals(
            'sandbox',
            $csp->getHeader(),
        );
    }

    public function testNonce(): void
    {
        $csp = new Csp();

        $csp->add('style-src', "'nonce-example'");

        $this->assertEquals('example', $csp->getNonce());
    }

    public function testNonceTwice(): void
    {
        $csp = new Csp();

        $csp->add('style-src', "'nonce-example'");
        $csp->add('style-src', "'nonce-demo'");

        $this->assertEquals('example', $csp->getNonce());
    }

    public function testNonceEmpty(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $csp = new Csp();
        $csp->add('style-src', "'nonce-'");
    }

    public function testFromString(): void
    {
        $csp = Csp::fromString(" script-src 'nonce-example';\n\n\r\nimg-src 'self' https://example.com");

        $this->assertEquals(
            [
                'script-src' => ["'nonce-example'"],
                'img-src'    => ["'self'", 'https://example.com'],
            ],
            $csp->getDirectives(),
        );
    }

    public function testFromStringOptionalEmpty(): void
    {
        $csp = Csp::fromString("script-src 'nonce-example';\nsandbox;");

        $this->assertEquals(
            [
                'script-src' => ["'nonce-example'"],
                'sandbox'    => [],
            ],
            $csp->getDirectives(),
        );
    }

    public function testFromStringOptionalEmptyWithValue(): void
    {
        $csp = Csp::fromString("script-src 'nonce-example';\nsandbox allow-scripts allow-forms;");

        $this->assertEquals(
            [
                'script-src' => ["'nonce-example'"],
                'sandbox'    => ['allow-scripts', 'allow-forms'],
            ],
            $csp->getDirectives(),
        );
    }

    public function testFromStringMandatoryEmpty(): void
    {
        $csp = Csp::fromString("script-src 'nonce-example';\nblock-all-mixed-content;");

        $this->assertEquals(
            [
                'script-src'              => ["'nonce-example'"],
                'block-all-mixed-content' => [],
            ],
            $csp->getDirectives(),
        );
    }

    public function testFromStringMandatoryEmptyWithValue(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Csp::fromString("script-src 'nonce-example';\nblock-all-mixed-content foo;");
    }

    #[DataProvider('providerExpressionInjectionCharacters')]
    public function testAddExpressionWithInjectionCharacterIsRejected(string $expression): void
    {
        $this->expectException(InvalidArgumentException::class);

        $csp = new Csp();
        $csp->add('script-src', $expression);
    }

    /**
     * @return array<string, list<string>>
     */
    public static function providerExpressionInjectionCharacters(): array
    {
        return [
            'semicolon in URL injects directive'   => ["https://example.com;object-src *"],
            'semicolon at end of URL'              => ["https://example.com;"],
            'semicolon in quoted token'            => ["'self';img-src *"],
            'semicolon in reporting name'          => ["endpoint;object-src *"],
            'CR in URL enables header injection'   => ["https://example.com\r"],
            'LF in URL enables header injection'   => ["https://example.com\n"],
            'CRLF in URL enables header injection' => ["https://example.com\r\n"],
            'tab in URL expression'                => ["https://example.com\t/path"],
            'CR in quoted token'                   => ["'self'\r"],
            'LF in quoted token'                   => ["'self'\n"],
            'tab in quoted token'                  => ["'self'\t"],
        ];
    }

    #[DataProvider('providerInvalidQuotedExpressions')]
    public function testAddInvalidQuotedExpressionIsRejected(string $expression): void
    {
        $this->expectException(InvalidArgumentException::class);

        $csp = new Csp();
        $csp->add('script-src', $expression);
    }

    /**
     * @return array<string, list<string>>
     */
    public static function providerInvalidQuotedExpressions(): array
    {
        return [
            'arbitrary quoted string' => ["'foobar'"],
            'misspelled self'         => ["'selff'"],
            'unknown keyword'         => ["'unsafe-everything'"],
            'invalid hash algorithm'  => ["'sha999-abc123'"],
            'sha without value'       => ["'sha256-'"],
        ];
    }

    public function testAddValidQuotedKeywordsAreAccepted(): void
    {
        $csp = new Csp();
        $csp->add('script-src', "'none'");
        $csp->add('script-src', "'unsafe-inline'");
        $csp->add('script-src', "'unsafe-eval'");
        $csp->add('script-src', "'unsafe-hashes'");
        $csp->add('script-src', "'strict-dynamic'");
        $csp->add('script-src', "'report-sample'");
        $csp->add('script-src', "'wasm-unsafe-eval'");

        $this->assertEquals(
            [
                "'none'",
                "'unsafe-inline'",
                "'unsafe-eval'",
                "'unsafe-hashes'",
                "'strict-dynamic'",
                "'report-sample'",
                "'wasm-unsafe-eval'",
            ],
            $csp->getDirective('script-src'),
        );
    }

    public function testAddValidHashSourcesAreAccepted(): void
    {
        $sha256 = "'sha256-abc123def456=='";
        $sha384 = "'sha384-abc123def456=='";
        $sha512 = "'sha512-abc123def456=='";

        $csp = new Csp();
        $csp->add('script-src', $sha256);
        $csp->add('script-src', $sha384);
        $csp->add('script-src', $sha512);

        $this->assertEquals([$sha256, $sha384, $sha512], $csp->getDirective('script-src'));
    }

    #[DataProvider('providerAllowedUnquotedKeywords')]
    public function testAddUnquotedKeywordIsAccepted(string $directive, string $expression): void
    {
        $csp = new Csp();
        $csp->add($directive, $expression);

        $this->assertContains($expression, $csp->getDirective($directive));
    }

    /**
     * @return array<string, list<string>>
     */
    public static function providerAllowedUnquotedKeywords(): array
    {
        return [
            'sandbox allow-downloads'   => ['sandbox', 'allow-downloads'],
            'sandbox allow-forms'       => ['sandbox', 'allow-forms'],
            'sandbox allow-modals'      => ['sandbox', 'allow-modals'],
            'sandbox allow-popups'      => ['sandbox', 'allow-popups'],
            'sandbox allow-same-origin' => ['sandbox', 'allow-same-origin'],
            'sandbox allow-scripts'     => ['sandbox', 'allow-scripts'],
            'report-to named endpoint'  => ['report-to', 'reporting-endpoint'],
        ];
    }

    public function testMergeSingleCspCopiesDirectives(): void
    {
        $a = new Csp();
        $a->add('script-src', 'https://example.com');
        $a->add('img-src', 'https://images.example.com');

        $merged = new Csp();
        $merged->merge($a);

        $this->assertEquals(['https://example.com'], $merged->getDirective('script-src'));
        $this->assertEquals(['https://images.example.com'], $merged->getDirective('img-src'));
    }

    public function testMergeDoesNotMutateInputCsps(): void
    {
        $a = new Csp();
        $a->add('script-src', 'https://example.com');

        $merged = new Csp();
        $merged->merge($a);

        $this->assertEquals(['https://example.com'], $a->getDirective('script-src'));
    }

    public function testMergeCombinesNonOverlappingDirectives(): void
    {
        $a = new Csp();
        $a->add('script-src', 'https://scripts.example.com');

        $b = new Csp();
        $b->add('img-src', 'https://images.example.com');

        $merged = new Csp();
        $merged->merge($a, $b);

        $this->assertEquals(['https://scripts.example.com'], $merged->getDirective('script-src'));
        $this->assertEquals(['https://images.example.com'], $merged->getDirective('img-src'));
    }

    public function testMergeCombinesOverlappingDirectives(): void
    {
        $a = new Csp();
        $a->add('script-src', 'https://a.example.com');

        $b = new Csp();
        $b->add('script-src', 'https://b.example.com');

        $merged = new Csp();
        $merged->merge($a, $b);

        $this->assertEquals(
            ['https://a.example.com', 'https://b.example.com'],
            $merged->getDirective('script-src'),
        );
    }

    public function testMergeDeduplicatesExpressions(): void
    {
        $a = new Csp();
        $a->add('script-src', 'https://example.com');

        $b = new Csp();
        $b->add('script-src', 'https://example.com');

        $merged = new Csp();
        $merged->merge($a, $b);

        $this->assertEquals(['https://example.com'], $merged->getDirective('script-src'));
    }

    public function testMergeMultipleCsps(): void
    {
        $a = new Csp();
        $a->add('script-src', 'https://a.example.com');

        $b = new Csp();
        $b->add('script-src', 'https://b.example.com');

        $c = new Csp();
        $c->add('script-src', 'https://c.example.com');

        $merged = new Csp();
        $merged->merge($a, $b, $c);

        $this->assertEquals(
            ['https://a.example.com', 'https://b.example.com', 'https://c.example.com'],
            $merged->getDirective('script-src'),
        );
    }

    public function testMergePreservesOptionalEmptyDirective(): void
    {
        $a = new Csp();
        $a->add('sandbox', null);

        $merged = new Csp();
        $merged->merge($a);

        $this->assertEquals([], $merged->getDirective('sandbox'));
    }

    public function testMergePreservesMandatoryEmptyDirective(): void
    {
        $a = new Csp();
        $a->add('block-all-mixed-content', null);

        $merged = new Csp();
        $merged->merge($a);

        $this->assertEquals([], $merged->getDirective('block-all-mixed-content'));
    }

    public function testMergePreservesEmptyDirectiveAlongsideNonEmpty(): void
    {
        $a = new Csp();
        $a->add('sandbox', null);
        $a->add('script-src', 'https://example.com');

        $merged = new Csp();
        $merged->merge($a);

        $this->assertEquals([], $merged->getDirective('sandbox'));
        $this->assertEquals(['https://example.com'], $merged->getDirective('script-src'));
    }

    #[DataProvider('providerSchemelessHostSources')]
    public function testAddSchemelessHostSourceIsAccepted(string $expression): void
    {
        $csp = new Csp();
        $csp->add('script-src', $expression);

        $this->assertContains($expression, $csp->getDirective('script-src'));
    }

    /**
     * @return array<string, list<string>>
     */
    public static function providerSchemelessHostSources(): array
    {
        return [
            'plain host'         => ['example.com'],
            'wildcard subdomain' => ['*.example.com'],
            'host with path'     => ['example.com/path'],
        ];
    }

    #[DataProvider('providerInvalidPorts')]
    public function testAddExpressionWithInvalidPortIsRejected(string $expression): void
    {
        $this->expectException(InvalidArgumentException::class);

        $csp = new Csp();
        $csp->add('script-src', $expression);
    }

    /**
     * @return array<string, list<string>>
     */
    public static function providerInvalidPorts(): array
    {
        return [
            'zero port with scheme'        => ['https://example.com:0'],
            'negative port with scheme'    => ['https://example.com:-1'],
            'port too large with scheme'   => ['https://example.com:65536'],
            'zero port schemeless'         => ['example.com:0'],
            'negative port schemeless'     => ['example.com:-1'],
            'port too large schemeless'    => ['example.com:65536'],
        ];
    }

    public function testInvalidNonceDoesNotMutateState(): void
    {
        $csp = new Csp();

        try {
            $csp->add('style-src', "'nonce-'");
        } catch (InvalidArgumentException) {
        }

        $this->assertArrayNotHasKey('style-src', $csp->getDirectives());
        $this->assertNull($csp->getNonce());
    }

    public function testInvalidNonceDoesNotAppendToExistingDirective(): void
    {
        $csp = new Csp();
        $csp->add('style-src', "'self'");

        try {
            $csp->add('style-src', "'nonce-'");
        } catch (InvalidArgumentException) {
        }

        $this->assertEquals(["'self'"], $csp->getDirective('style-src'));
        $this->assertNull($csp->getNonce());
    }

    public function testGetDirectiveDefaultSrcReturnsDefaultSourceExpressions(): void
    {
        $csp = new Csp();
        $csp->add('default-src', "'self'");

        $this->assertEquals(["'self'"], $csp->getDirective('default-src'));
    }

    public function testGetDirectiveUnsetFetchDirectiveFallsBackToDefaultSrc(): void
    {
        $csp = new Csp();
        $csp->add('default-src', "'self'");

        $this->assertEquals(["'self'"], $csp->getDirective('img-src'));
    }

    public function testGetDirectiveUsesOwnValueWhenExplicitlySet(): void
    {
        $csp = new Csp();
        $csp->add('img-src', 'https://images.example.com');
        $this->assertEquals(['https://images.example.com'], $csp->getDirective('img-src'));
    }

    public function testGetDirectiveChildFallsBackToIntermediateParent(): void
    {
        $csp = new Csp();
        $csp->add('script-src', 'https://scripts.example.com');
        $this->assertEquals(['https://scripts.example.com'], $csp->getDirective('script-src-elem'));
    }

    public function testGetDirectiveChildFallsBackThroughFullInheritanceChain(): void
    {
        $csp = new Csp();
        $csp->add('default-src', "'self'");

        $this->assertEquals(["'self'"], $csp->getDirective('script-src-elem'));
    }

    public function testGetDirectiveStyleSrcAttrFallsBackToStyleSrc(): void
    {
        $csp = new Csp();
        $csp->add('style-src', 'https://styles.example.com');
        $this->assertEquals(['https://styles.example.com'], $csp->getDirective('style-src-attr'));
    }

    public function testGetRawDirectiveReturnsNullWhenNotSet(): void
    {
        $csp = new Csp();
        $this->assertNull($csp->getRawDirective('script-src'));
    }

    public function testGetRawDirectiveReturnsValuesWhenSet(): void
    {
        $csp = new Csp();
        $csp->add('script-src', 'https://example.com');
        $this->assertEquals(['https://example.com'], $csp->getRawDirective('script-src'));
    }

    public function testGetRawDirectiveDoesNotInheritFromParent(): void
    {
        $csp = new Csp();
        $csp->add('script-src', 'https://scripts.example.com');
        $this->assertNull($csp->getRawDirective('script-src-elem'));
    }

    public function testGetRawDirectiveReturnsEmptyArrayForEmptyDirective(): void
    {
        $csp = new Csp();
        $csp->add('sandbox', null);
        $this->assertEquals([], $csp->getRawDirective('sandbox'));
    }

    public function testHasDirectiveReturnsTrueWhenExplicitlySet(): void
    {
        $csp = new Csp();
        $csp->add('script-src', 'https://example.com');
        $this->assertTrue($csp->hasDirective('script-src'));
    }

    public function testHasDirectiveReturnsTrueWhenParentIsSet(): void
    {
        $csp = new Csp();
        $csp->add('script-src', 'https://scripts.example.com');
        $this->assertTrue($csp->hasDirective('script-src-elem'));
    }

    public function testHasDirectiveReturnsFalseWhenNeitherDirectiveNorParentIsSet(): void
    {
        $csp = new Csp();
        $this->assertFalse($csp->hasDirective('script-src-elem'));
    }

    public function testHasRawDirectiveReturnsTrueWhenSet(): void
    {
        $csp = new Csp();
        $csp->add('script-src', 'https://example.com');
        $this->assertTrue($csp->hasRawDirective('script-src'));
    }

    public function testHasRawDirectiveReturnsFalseWhenNotSet(): void
    {
        $csp = new Csp();
        $this->assertFalse($csp->hasRawDirective('script-src'));
    }

    public function testHasRawDirectiveDoesNotInheritFromParent(): void
    {
        $csp = new Csp();
        $csp->add('script-src', 'https://scripts.example.com');
        $this->assertFalse($csp->hasRawDirective('script-src-elem'));
    }

    public function testHasRawDirectiveReturnsTrueForEmptyDirective(): void
    {
        $csp = new Csp();
        $csp->add('sandbox', null);
        $this->assertTrue($csp->hasRawDirective('sandbox'));
    }

    public function testFromStringRoundTrip(): void
    {
        $csp = new Csp();
        $csp->add('script-src', "'self'");
        $csp->add('script-src', 'https://example.com');
        $csp->add('img-src', 'https://images.example.com');
        $csp->add('sandbox', null);

        $parsed = Csp::fromString($csp->getHeader());

        $this->assertEquals($csp->getDirectives(), $parsed->getDirectives());
    }

    public function testFromStringWithDefaultSrc(): void
    {
        $csp = Csp::fromString("default-src 'self'");

        $this->assertEquals(["'self'"], $csp->getDirective('default-src'));
        $this->assertEquals(["'self'"], $csp->getDirective('img-src'));
    }

    public function testFirstNonceWinsInMergedCsp(): void
    {
        $a = new Csp();
        $a->add('script-src', "'nonce-first'");

        $b = new Csp();
        $b->add('script-src', "'nonce-second'");

        $merged = new Csp();
        $merged->merge($a, $b);

        $this->assertEquals('first', $merged->getNonce());
    }

    public function testFirstNonceWinsInParsedHeader(): void
    {
        $csp = Csp::fromString("script-src 'nonce-first'; style-src 'nonce-second'");

        $this->assertEquals('first', $csp->getNonce());
    }

    public function testAddWildcardPort(): void
    {
        $csp = new Csp();
        $csp->add('script-src', 'https://example.com:*');

        $this->assertEquals(['https://example.com:*'], $csp->getDirective('script-src'));
    }

    public function testNonFetchDirectiveDoesNotFallBack(): void
    {
        $csp = new Csp();
        $csp->add('default-src', "'self'");

        $this->assertEquals([], $csp->getDirective('form-action'));
        $this->assertEquals([], $csp->getDirective('frame-ancestors'));
    }

    public function testUpgradeInsecureRequests(): void
    {
        $csp = new Csp();
        $csp->add('upgrade-insecure-requests', null);

        $this->assertEquals([], $csp->getRawDirective('upgrade-insecure-requests'));
        $this->assertEquals('upgrade-insecure-requests', $csp->getHeader());
    }

    public function testUpgradeInsecureRequestsWithValueIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $csp = new Csp();
        $csp->add('upgrade-insecure-requests', 'something');
    }

    public function testGetNonceReturnsNullWhenNoNonceExists(): void
    {
        $csp = new Csp();

        $this->assertNull($csp->getNonce());
    }

    public function testAddAtomicityForInvalidExpression(): void
    {
        $csp = new Csp();

        try {
            $csp->add('script-src', "'foobar'");
        } catch (InvalidArgumentException) {
        }

        $this->assertArrayNotHasKey('script-src', $csp->getDirectives());
    }

    public function testAddAtomicityForInvalidExpressionPreservesExistingValues(): void
    {
        $csp = new Csp();
        $csp->add('script-src', "'self'");

        try {
            $csp->add('script-src', "'foobar'");
        } catch (InvalidArgumentException) {
        }

        $this->assertEquals(["'self'"], $csp->getDirective('script-src'));
    }

    #[DataProvider('providerValidReportHashes')]
    public function testAddValidReportHashIsAccepted(string $expression): void
    {
        $csp = new Csp();
        $csp->add('script-src', $expression);

        $this->assertContains($expression, $csp->getDirective('script-src'));
    }

    /**
     * @return array<string, list<string>>
     */
    public static function providerValidReportHashes(): array
    {
        return [
            'report-sha256' => ["'report-sha256'"],
            'report-sha384' => ["'report-sha384'"],
            'report-sha512' => ["'report-sha512'"],
        ];
    }

    #[DataProvider('providerInvalidReportHashes')]
    public function testAddInvalidReportHashIsRejected(string $expression): void
    {
        $this->expectException(InvalidArgumentException::class);

        $csp = new Csp();
        $csp->add('script-src', $expression);
    }

    /**
     * @return array<string, list<string>>
     */
    public static function providerInvalidReportHashes(): array
    {
        return [
            'report-md5'           => ["'report-md5'"],
            'report-unknown'       => ["'report-foobar'"],
            'report-sha256-hash'   => ["'report-sha256-abc123'"],
            'report-sha256-sha384' => ["'report-sha256-sha384'"],
        ];
    }

    public function testMergeWithNoArgumentsIsNoOp(): void
    {
        $csp = new Csp();
        $csp->add('script-src', 'https://example.com');

        $csp->merge();

        $this->assertEquals(['https://example.com'], $csp->getDirective('script-src'));
        $this->assertCount(1, $csp->getDirectives());
    }

    public function testMergeReturnsSameInstance(): void
    {
        $csp = new Csp();
        $other = new Csp();
        $other->add('script-src', 'https://example.com');

        $result = $csp->merge($other);

        $this->assertSame($csp, $result);
    }

    public function testMergeMergesIntoNonEmptyBase(): void
    {
        $base = new Csp();
        $base->add('script-src', 'https://base.example.com');

        $other = new Csp();
        $other->add('script-src', 'https://other.example.com');
        $other->add('img-src', 'https://images.example.com');

        $base->merge($other);

        $this->assertEquals(
            ['https://base.example.com', 'https://other.example.com'],
            $base->getDirective('script-src'),
        );
        $this->assertEquals(['https://images.example.com'], $base->getDirective('img-src'));
    }

    public function testAddEmptyArrayOnNullableDirective(): void
    {
        $csp = new Csp();
        $csp->add('sandbox', []);

        $this->assertEquals([], $csp->getRawDirective('sandbox'));
    }

    public function testAddEmptyArrayOnNonNullableDirectiveThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $csp = new Csp();
        $csp->add('script-src', []);
    }

    public function testMergeBaseNonceTakesPriorityOverMergedNonce(): void
    {
        $base = new Csp();
        $base->add('script-src', "'nonce-base'");

        $other = new Csp();
        $other->add('script-src', "'nonce-other'");

        $base->merge($other);

        $this->assertEquals('base', $base->getNonce());
    }

    #[DataProvider('providerValidPortBoundaries')]
    public function testAddValidPortBoundaryIsAccepted(string $expression): void
    {
        $csp = new Csp();
        $csp->add('script-src', $expression);

        $this->assertContains($expression, $csp->getDirective('script-src'));
    }

    /**
     * @return array<string, list<string>>
     */
    public static function providerValidPortBoundaries(): array
    {
        return [
            'minimum port'            => ['https://example.com:1'],
            'maximum port'            => ['https://example.com:65535'],
            'minimum port schemeless' => ['example.com:1'],
            'maximum port schemeless' => ['example.com:65535'],
        ];
    }
}
