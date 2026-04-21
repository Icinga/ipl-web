<?php

namespace ipl\Tests\Web;

use ipl\Html\Html;
use ipl\Web\Style;

class StyleTest extends TestCase
{
    public function testNonceIsCorrectlyRendered()
    {
        $style = $this->createTestableStyle();
        $style->setNonce('12345rtzujiklö');

        $this->assertSame(
            '<style nonce="12345rtzujiklö"></style>',
            $style->render()
        );
    }

    public function testModuleScopeIsCorrectlyRendered()
    {
        $style = $this->createTestableStyle();
        $style->setModule('foo');
        $style->add('#bar', ['width' => 'auto']);

        $this->assertSame(
            <<<'EOT'
<style>.icinga-module.module-foo {
#bar {
width: auto;
}
}</style>
EOT
            ,
            $style->render()
        );
    }

    public function testAddForAutomaticallyGeneratesIds()
    {
        $div = Html::tag('div');

        $style = new Style();
        $style->addFor($div, ['width' => 'auto']);

        $this->assertNotNull($div->getAttribute('id')->getValue());
    }

    public function testAddForRespectsExistingIds()
    {
        $div = Html::tag('div', ['id' => 'foo']);

        $style = $this->createTestableStyle();
        $style->addFor($div, ['width' => 'auto']);

        $this->assertSame(
            <<<'EOT'
<style>#foo {
width: auto;
}</style>
EOT
            ,
            $style->render()
        );
    }

    /**
     * @depends testNonceIsCorrectlyRendered
     * @depends testModuleScopeIsCorrectlyRendered
     * @depends testAddForRespectsExistingIds
     */
    public function testCanBeCastedToString()
    {
        $div = Html::tag('div', ['id' => 'foo']);

        $style = $this->createTestableStyle();
        $style->setNonce('12345rtzujiklö');
        $style->setModule('foo');
        $style->addFor($div, ['width' => 'auto']);

        $this->assertSame(
            <<<'EOT'
<style nonce="12345rtzujiklö">.icinga-module.module-foo {
#foo {
width: auto;
}
}</style>
EOT
            ,
            (string) $style
        );
    }

    protected function createTestableStyle(): Style
    {
        return new class extends Style {
            public function renderCss(): string
            {
                return $this->renderLess();
            }
        };
    }
}
