<?php

namespace ipl\Tests\Web\Less;

use InvalidArgumentException;
use ipl\Web\Less\DetachedRulesetCallVisitor;
use Less_Parser;
use Less_Tree_Declaration;
use Less_Tree_Mixin_Definition;
use Less_Tree_Ruleset;

class DetachedRulesetCallVisitorTest extends LessVisitorTestCase
{
    public function testRulesetsAreWrappedInPrintMediaQuery(): void
    {
        $visitor = new DetachedRulesetCallVisitor('print', <<<'LESS'
@media print {
    {ruleset}
}
LESS);

        $less = <<<'LESS'
@print: {
    .page-sidebar {
        display: none;
    }
    .page-header {
        position: static;
        box-shadow: none;
    }
}
LESS;

        $css = <<<'CSS'
@media print {
  .page-sidebar {
    display: none;
  }
  .page-header {
    position: static;
    box-shadow: none;
  }
}
CSS;

        $this->assertCss($css, $less, [$visitor]);
    }

    public function testRulesetsAreWrappedInReducedMotionQuery(): void
    {
        $visitor = new DetachedRulesetCallVisitor('reduced-motion', <<<'LESS'
@media (prefers-reduced-motion: reduce) {
    {ruleset}
}
LESS);

        $less = <<<'LESS'
@reduced-motion: {
    .spinner {
        animation: none;
    }
    .page-transition {
        transition: none;
    }
}
LESS;

        $css = <<<'CSS'
@media (prefers-reduced-motion: reduce) {
  .spinner {
    animation: none;
  }
  .page-transition {
    transition: none;
  }
}
CSS;

        $this->assertCss($css, $less, [$visitor]);
    }

    public function testMultipleDeclarationsAreEachExpandedIndependently(): void
    {
        // Multiple Less files (or scopes) may each contribute their own block.
        // Each declaration is expanded into its own wrapped block independently.
        $visitor = new DetachedRulesetCallVisitor('print', <<<'LESS'
@media print {
    {ruleset}
}
LESS);

        $less = <<<'LESS'
@print: {
    .navigation {
        display: none;
    }
}

@print: {
    .advertisement {
        display: none;
    }
}
LESS;

        $css = <<<'CSS'
@media print {
  .navigation {
    display: none;
  }
}
@media print {
  .advertisement {
    display: none;
  }
}
CSS;

        $this->assertCss($css, $less, [$visitor]);
    }

    public function testSurroundingRulesAreUnaffected(): void
    {
        $visitor = new DetachedRulesetCallVisitor('print', <<<'LESS'
@media print {
    {ruleset}
}
LESS);

        $less = <<<'LESS'
.component {
    font-size: 1rem;
}

@print: {
    .component {
        font-size: 12pt;
    }
}

.other-component {
    color: black;
}
LESS;

        $css = <<<'CSS'
.component {
  font-size: 1rem;
}
@media print {
  .component {
    font-size: 12pt;
  }
}
.other-component {
  color: black;
}
CSS;

        $this->assertCss($css, $less, [$visitor]);
    }

    public function testNonMatchingVariableNamesAreNotExpanded(): void
    {
        // A visitor configured for 'print' must not expand unrelated detached rulesets.
        $visitor = new DetachedRulesetCallVisitor('print', <<<'LESS'
@media print {
    {ruleset}
}
LESS);

        $less = <<<'LESS'
@reduced-motion: {
    .spinner {
        animation: none;
    }
}

.selector {
    font-size: 1em;
}
LESS;

        $css = <<<'CSS'
.selector {
  font-size: 1em;
}
CSS;

        $this->assertCss($css, $less, [$visitor]);
    }

    public function testNonDetachedRulesetValuesAreNotExpanded(): void
    {
        // A variable with the right name but a scalar value is left unchanged.
        $visitor = new DetachedRulesetCallVisitor('brand-color', <<<'LESS'
@media print {
    {ruleset}
}
LESS);

        $less = <<<'LESS'
@brand-color: #00c3ed;

.link {
    color: @brand-color;
}
LESS;

        $css = <<<'CSS'
.link {
  color: #00c3ed;
}
CSS;

        $this->assertCss($css, $less, [$visitor]);
    }

    public function testLightModeBlockIsWrappedInColorSchemeMediaQuery(): void
    {
        // Dark-mode-first design: var() calls reference custom properties and carry dark
        // fallback values. The @light-mode block overrides them for light color schemes.
        $visitor = new DetachedRulesetCallVisitor('light-mode', <<<'LESS'
@media (prefers-color-scheme: light) {
    {ruleset}
}
LESS);

        $less = <<<'LESS'
.state-ok {
    color: var(--color-ok, #44bb77);
}

.state-critical {
    color: var(--color-critical, #ff5566);
}

.state-unknown {
    color: var(--color-unknown, #aa44ff);
}

.page {
    background: var(--color-background, #1c1c1e);
    color: var(--color-text, #d9d9d9);
}

@light-mode: {
    :root {
        --color-ok: #2ecc71;
        --color-critical: #e74c3c;
        --color-unknown: #9b59b6;
        --color-background: #ffffff;
        --color-text: #1c1c1e;
    }
}
LESS;

        $css = <<<'CSS'
.state-ok {
  color: var(--color-ok, #44bb77);
}
.state-critical {
  color: var(--color-critical, #ff5566);
}
.state-unknown {
  color: var(--color-unknown, #aa44ff);
}
.page {
  background: var(--color-background, #1c1c1e);
  color: var(--color-text, #d9d9d9);
}
@media (prefers-color-scheme: light) {
  :root {
    --color-ok: #2ecc71;
    --color-critical: #e74c3c;
    --color-unknown: #9b59b6;
    --color-background: #ffffff;
    --color-text: #1c1c1e;
  }
}
CSS;

        $this->assertCss($css, $less, [$visitor]);
    }

    public function testVisitorIsReusableAcrossParsers(): void
    {
        // A single visitor instance must be safe to pass to two separate Less_Parser instances.
        // Pre-fix: $mixinDefInjected is already true after the first parse, so the mixin
        // definition is never injected into the second tree, causing an unresolvable mixin call.
        $visitor = new DetachedRulesetCallVisitor('print', <<<'LESS'
@media print {
    {ruleset}
}
LESS);

        $less1 = <<<'LESS'
@print: {
    .sidebar { display: none; }
}
LESS;

        $less2 = <<<'LESS'
@print: {
    .header { position: static; }
}
LESS;

        $parser1 = new Less_Parser(['plugins' => [$visitor]]);
        $css1 = $parser1->parse($less1)->getCss();

        $parser2 = new Less_Parser(['plugins' => [$visitor]]);
        $css2 = $parser2->parse($less2)->getCss();

        $this->assertStringContainsString('@media print', $css1, 'First parse must contain @media print block');
        $this->assertStringContainsString('.sidebar', $css1, 'First parse must expand @print ruleset');
        $this->assertStringContainsString('@media print', $css2, 'Second parse must contain @media print block');
        $this->assertStringContainsString('.header', $css2, 'Second parse must expand @print ruleset');
    }

    public function testMixinDefIsInjectedIntoEachNewTree(): void
    {
        // run() must inject the mixin def into every new tree it is called with.
        // Pre-fix: $mixinDefInjected is set on $this after the first call, so the second
        // tree never receives the definition.
        $visitor = new DetachedRulesetCallVisitor('screen-only', '@media screen { {ruleset} }');

        $tree1 = new Less_Tree_Ruleset([], []);
        $tree2 = new Less_Tree_Ruleset([], []);

        $visitor->run($tree1);
        $visitor->run($tree2);

        $defs1 = array_values(array_filter(
            $tree1->rules,
            static fn ($rule) => $rule instanceof Less_Tree_Mixin_Definition,
        ));
        $defs2 = array_values(array_filter(
            $tree2->rules,
            static fn ($rule) => $rule instanceof Less_Tree_Mixin_Definition,
        ));

        $this->assertCount(1, $defs1, 'Mixin definition must be injected into the first tree');
        $this->assertCount(1, $defs2, 'Mixin definition must be injected into the second tree');
    }

    public function testRunThrowsOnNonRulesetRoot(): void
    {
        // run() requires a Less_Tree_Ruleset as its root node. Passing any other
        // Less_Tree instance must throw InvalidArgumentException immediately.
        $this->expectException(InvalidArgumentException::class);

        $visitor = new DetachedRulesetCallVisitor('print', '@media print { {ruleset} }');
        $visitor->run(new Less_Tree_Declaration('@print', null));
    }

    public function testRunInjectsMixinDefExactlyOnce(): void
    {
        // Guard against duplicate injection when run() is called more than once on the
        // same tree (can happen in tests or when a parser is reused).
        $visitor = new DetachedRulesetCallVisitor('screen-only', '@media screen { {ruleset} }');

        $root = new Less_Tree_Ruleset([], []);

        $visitor->run($root);
        $visitor->run($root);

        $mixinDefs = array_values(array_filter(
            $root->rules,
            static fn ($rule) => $rule instanceof Less_Tree_Mixin_Definition,
        ));

        $this->assertCount(1, $mixinDefs, 'Mixin definition must be injected exactly once');
    }
}
