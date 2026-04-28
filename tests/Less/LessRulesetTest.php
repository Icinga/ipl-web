<?php

namespace ipl\Tests\Web\Less;

use ipl\Tests\Web\TestCase;
use ipl\Web\Less\LessRuleset;
use OutOfBoundsException;

class LessRulesetTest extends TestCase
{
    public function testSetWithSelectorIsCorrectlyRendered()
    {
        $set = $this->createTestableRuleset('.foo', ['width' => 'auto']);

        $this->assertSame('.foo', $set->getSelector());
        $this->assertSame(
            <<<'EOT'
.foo {
width: auto;
}
EOT
            ,
            $set->renderCss()
        );
    }

    public function testSetWithoutSelectorIsCorrectlyRendered()
    {
        $set = $this->createTestableRuleset();
        $set->setProperties(['width' => 'auto', 'height' => 'auto']);

        $this->assertSame(['width' => 'auto', 'height' => 'auto'], $set->getProperties());
        $this->assertSame(
            <<<'EOT'
width: auto;
height: auto;
EOT
            ,
            $set->renderCss()
        );
    }

    public function testNestedSetsAreCorrectlyRendered()
    {
        $set = $this->createTestableRuleset('.level1', ['width' => 'auto']);
        $set->addRuleset(
            $this->createTestableRuleset('.level2', ['width' => '2em'])
                ->add('.level3', ['width' => '1em'])
        );

        $this->assertSame(
            <<<'EOT'
.level1 {
width: auto;
.level2 {
width: 2em;
.level3 {
width: 1em;
}
}
}
EOT
            ,
            $set->renderCss()
        );
    }

    public function testSetsCanBeAdjustedAfterCreation()
    {
        $set = $this->createTestableRuleset('.foo', ['width' => 'auto']);
        $set->setProperty('line-height', 1.5);
        $set['color'] = '#abc';

        $this->assertSame('#abc', $set['color']);
        $this->assertSame('1.5', $set->getProperty('line-height'));
        $this->assertSame(
            <<<'EOT'
.foo {
width: auto;
line-height: 1.5;
color: #abc;
}
EOT
            ,
            $set->renderCss()
        );
    }

    public function testEmptyStringPropertyIsOmitted()
    {
        $set = $this->createTestableRuleset('.foo', ['color' => '', 'width' => 'auto']);

        $this->assertSame(
            <<<'EOT'
.foo {
width: auto;
}
EOT
            ,
            $set->renderCss()
        );
    }

    public function testWhitespaceOnlyPropertyIsOmitted()
    {
        $set = $this->createTestableRuleset('.foo', ['color' => '  ', 'width' => 'auto']);

        $this->assertSame(
            <<<'EOT'
.foo {
width: auto;
}
EOT
            ,
            $set->renderCss()
        );
    }

    public function testNullPropertyIsOmitted()
    {
        $set = $this->createTestableRuleset('.foo', ['width' => 'auto']);
        $set['color'] = null;

        $this->assertSame(
            <<<'EOT'
.foo {
width: auto;
}
EOT
            ,
            $set->renderCss()
        );
    }

    public function testRulesetWithOnlyEmptyPropertiesRendersEmpty()
    {
        $set = $this->createTestableRuleset('.foo', ['color' => '', 'width' => '']);

        $this->assertSame('', $set->renderCss());
    }

    public function testNestedRulesetWithOnlyEmptyPropertiesRendersEmpty()
    {
        $parent = $this->createTestableRuleset('.parent', []);
        $child = $this->createTestableRuleset('.child', ['color' => '']);
        $parent->addRuleset($child);

        $this->assertSame('', $parent->renderCss());
    }

    public function testAccessingAMissingPropertyThrowsIfGetPropertyIsUsed()
    {
        $this->expectException(OutOfBoundsException::class);
        (new LessRuleset())->getProperty('missing');
    }

    public function testAccessingAMissingPropertyThrowsIfOffsetAccessIsUsed()
    {
        $this->expectException(OutOfBoundsException::class);
        $set = new LessRuleset();
        $_ = $set['missing'];
    }

    protected function createTestableRuleset(?string $selector = null, array $properties = []): LessRuleset
    {
        $ruleset = new class extends LessRuleset {
            public function renderCss(): string
            {
                return $this->renderLess();
            }
        };

        return $selector !== null ? $ruleset::create($selector, $properties) : $ruleset;
    }
}
