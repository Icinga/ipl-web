<?php

namespace ipl\Tests\Web\Less;

use ipl\Web\Less\WikimediaLessCompiler;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class WikimediaLessCompilerTest extends TestCase
{
    private const COMPRESSED = '.state-ok{color:#44bb77}.state-critical{color:#ff5566}';

    private const PRETTY = ".state-ok {\n  color: #44bb77;\n}\n.state-critical {\n  color: #ff5566;\n}";

    public function testBasicCompilation(): void
    {
        // Just test variable substitution and arithmetic - @spacing * 2 must resolve to 16px.
        $less = <<<'LESS'
@spacing: 8px;
@border-radius: 3px;

.action-link {
    padding: @spacing (@spacing * 2);
    border-radius: @border-radius;
}
LESS;

        $css = (new WikimediaLessCompiler())->compile($less);

        $this->assertStringContainsString('padding: 8px 16px', $css, '@spacing * 2 must resolve to 16px');
        $this->assertStringContainsString('border-radius: 3px', $css, '@border-radius must pass through unchanged');
    }

    public function testInvalidLessThrowsRuntimeException(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/Less compilation failed/i');

        (new WikimediaLessCompiler())->compile('.invalid {');
    }

    public function testMinifyMatrix(): void
    {
        // The block comment acts as a sentinel: present in pretty-printed output, absent when compressed.
        $comment = 'Icinga state colors';
        $less = <<<LESS
/* $comment */
@state-ok: #44bb77;
@state-critical: #ff5566;

.state-ok {
    color: @state-ok;
}

.state-critical {
    color: @state-critical;
}
LESS;

        $noCompress = new WikimediaLessCompiler();
        $compressFalse = new WikimediaLessCompiler(['compress' => false]);
        $compressTrue = new WikimediaLessCompiler(['compress' => true]);

        // Passing null defers to the constructor-level compress option.
        $this->assertStringContainsString(
            $comment,
            $noCompress->compile($less),
            'minify=null, constructor default: must be pretty-printed'
        );
        $this->assertStringContainsString(
            $comment,
            $compressFalse->compile($less),
            'minify=null, constructor compress=false: must be pretty-printed'
        );
        $this->assertStringNotContainsString(
            $comment,
            $compressTrue->compile($less),
            'minify=null, constructor compress=true: must be compressed'
        );

        // Passing false always produces pretty-printed output, even when the constructor has compress enabled.
        $this->assertStringContainsString(
            $comment,
            $noCompress->compile($less, false),
            'minify=false, constructor default: must be pretty-printed'
        );
        $this->assertStringContainsString(
            $comment,
            $compressFalse->compile($less, false),
            'minify=false, constructor compress=false: must be pretty-printed'
        );
        $this->assertStringContainsString(
            $comment,
            $compressTrue->compile($less, false),
            'minify=false, constructor compress=true: explicit false must override constructor'
        );

        // Passing true always produces compressed output, even when the constructor does not enable compress.
        $this->assertStringNotContainsString(
            $comment,
            $noCompress->compile($less, true),
            'minify=true, constructor default: must be compressed'
        );
        $this->assertStringNotContainsString(
            $comment,
            $compressFalse->compile($less, true),
            'minify=true, constructor compress=false: explicit true must override constructor'
        );
        $this->assertStringNotContainsString(
            $comment,
            $compressTrue->compile($less, true),
            'minify=true, constructor compress=true: must be compressed'
        );
    }
}
