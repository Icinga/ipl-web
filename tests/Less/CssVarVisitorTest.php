<?php

namespace ipl\Tests\Web\Less;

use ipl\Web\Less\CssVarVisitor;
use Less_Parser;

class CssVarVisitorTest extends LessVisitorTestCase
{
    public function testNonColorVariablesAreNotReplaced(): void
    {
        $less = <<<'LESS'
@content-padding: 1em;
@sidebar-width: 260px;

.sidebar {
    width: @sidebar-width;
    padding: @content-padding;
}
LESS;

        $css = <<<'CSS'
.sidebar {
  width: 260px;
  padding: 1em;
}
CSS;

        $this->assertCss($css, $less);
    }

    public function testColorsAreReplaced(): void
    {
        $less = <<<'LESS'
@state-ok: #44bb77;
@state-warning: #ffaa44;
@state-critical: #ff5566;
@state-unknown: #aa44ff;

.state-ok {
    color: @state-ok;
}

.state-warning {
    color: @state-warning;
}

.state-critical {
    color: @state-critical;
}

.state-unknown {
    color: @state-unknown;
}
LESS;

        $css = <<<'CSS'
.state-ok {
  color: var(--state-ok, #44bb77);
}
.state-warning {
  color: var(--state-warning, #ffaa44);
}
.state-critical {
  color: var(--state-critical, #ff5566);
}
.state-unknown {
  color: var(--state-unknown, #aa44ff);
}
CSS;

        $this->assertCss($css, $less);
    }

    public function testColorValuesArePreserved(): void
    {
        $less = <<<'LESS'
@state-ok: #44bb77;
@icinga-blue: #00C3ED;
@white: #fff;
@light-gray-shade: #eeeeee;

.state-card {
  background-color: @white;
  border: 1px solid @light-gray-shade;
  border-left: 4px solid @icinga-blue;
  color: @icinga-blue;
  padding: 12px 16px;
  border-radius: 4px;

  &.state-ok {
    border-left-color: @state-ok;
    color: @state-ok;
  }
}
LESS;

        $css = <<<'CSS'
.state-card {
  background-color: var(--white, #fff);
  border: 1px solid var(--light-gray-shade, #eeeeee);
  border-left: 4px solid var(--icinga-blue, #00C3ED);
  color: var(--icinga-blue, #00C3ED);
  padding: 12px 16px;
  border-radius: 4px;
}
.state-card.state-ok {
  border-left-color: var(--state-ok, #44bb77);
  color: var(--state-ok, #44bb77);
}
CSS;

        $this->assertCss($css, $less);
    }

    public function testAliasedColorsProduceNestedVarFallbacks(): void
    {
        $less = <<<'LESS'
@state-ok: #44bb77;
@state-up: @state-ok;
@state-critical: #ff5566;
@state-down: @state-critical;

.state-up {
    background-color: @state-up;
}

.state-down {
    background-color: @state-down;
}
LESS;

        $css = <<<'CSS'
.state-up {
  background-color: var(--state-up, var(--state-ok, #44bb77));
}
.state-down {
  background-color: var(--state-down, var(--state-critical, #ff5566));
}
CSS;

        $this->assertCss($css, $less);
    }

    public function testBuiltInFunctionsReceiveResolvedColorValues(): void
    {
        // Built-in Less functions (fade(), lighten(), darken(), …) require resolved color
        // values to compute their result. The visitor disables var() replacement inside
        // their arguments so Less can evaluate them directly.
        $less = <<<'LESS'
@state-ok: #44bb77;
@badge-color: @state-ok;
@default-text-color: #fff;

.hint {
    color: lighten(@badge-color, 10%);
    background-color: darken(@badge-color, 10%);
    border-color: fade(@default-text-color, 75%);
}
LESS;

        $css = <<<'CSS'
.hint {
  color: #69c992;
  background-color: #36965f;
  border-color: rgba(255, 255, 255, 0.75);
}
CSS;

        $this->assertCss($css, $less);
    }

    public function testVariableDerivedFromBuiltInFunctionIsReplaced(): void
    {
        // A variable whose value is computed by a built-in Less function resolves to a color,
        // so it still gets a var() call with the computed value as the fallback.
        $less = <<<'LESS'
@default-text-color: #fff;
@default-text-color-light: fade(@default-text-color, 75%);

.secondary-text {
    color: @default-text-color-light;
}
LESS;

        $css = <<<'CSS'
.secondary-text {
  color: var(--default-text-color-light, rgba(255, 255, 255, 0.75));
}
CSS;

        $this->assertCss($css, $less);
    }

    public function testBoxShadowMixinWithColorArgument(): void
    {
        // The project's .box-shadow mixin uses @arguments to forward all parameters
        // including the color. When a color variable is passed as the color argument,
        // it is replaced with a var() call inside the forwarded @arguments value.
        $less = <<<'LESS'
@state-critical: #ff5566;
@shadow-color: @state-critical;

.box-shadow(@x: 0.2em; @y: 0.2em; @blur: 0.2em; @spread: 0; @color: rgba(83, 83, 83, 0.25)) {
    -webkit-box-shadow: @arguments;
    -moz-box-shadow: @arguments;
    box-shadow: @arguments;
}

.dialog-critical {
    .box-shadow(@color: @shadow-color);
}
LESS;

        $css = <<<'CSS'
.dialog-critical {
  -webkit-box-shadow: 0.2em 0.2em 0.2em 0 var(--shadow-color, var(--state-critical, #ff5566));
  -moz-box-shadow: 0.2em 0.2em 0.2em 0 var(--shadow-color, var(--state-critical, #ff5566));
  box-shadow: 0.2em 0.2em 0.2em 0 var(--shadow-color, var(--state-critical, #ff5566));
}
CSS;

        $this->assertCss($css, $less);
    }

    public function testMixinColorParameterIsNotReplaced(): void
    {
        // The mixin parameter @color acts as a local placeholder — it must not be
        // replaced with var(--color). Only the argument passed at the call site gets replaced.
        $less = <<<'LESS'
@state-ok: #44bb77;

.state-badge(@color) {
    background-color: @color;
    border: 1px solid @color;
}

.state-ok {
    .state-badge(@state-ok);
}
LESS;

        $css = <<<'CSS'
.state-ok {
  background-color: var(--state-ok, #44bb77);
  border: 1px solid var(--state-ok, #44bb77);
}
CSS;

        $this->assertCss($css, $less);
    }

    public function testMixinDefaultColorParametersAreReplaced(): void
    {
        // Default parameter values that reference color variables are replaced
        // when the mixin is called without explicit arguments.
        $less = <<<'LESS'
@base-primary-bg: #00c3ed;
@primary-button-bg: @base-primary-bg;
@default-bg: #282e39;
@default-text-color-inverted: @default-bg;

.button(@bg: @primary-button-bg; @fg: @default-text-color-inverted) {
    background-color: @bg;
    color: @fg;
}

.submit-button {
    .button();
}
LESS;

        $css = <<<'CSS'
.submit-button {
  background-color: var(--primary-button-bg, var(--base-primary-bg, #00c3ed));
  color: var(--default-text-color-inverted, var(--default-bg, #282e39));
}
CSS;

        $this->assertCss($css, $less);
    }

    public function testVariablesInsideMediaQueries(): void
    {
        $less = <<<'LESS'
@base-primary-bg: #00c3ed;
@control-color: @base-primary-bg;

@media (max-width: 768px) {
    .controls {
        color: @control-color;
    }
}
LESS;

        $css = <<<'CSS'
@media (max-width: 768px) {
  .controls {
    color: var(--control-color, var(--base-primary-bg, #00c3ed));
  }
}
CSS;

        $this->assertCss($css, $less);
    }

    public function testVariablesInsideDetachedRuleset(): void
    {
        // Color variables used inside a detached ruleset are replaced when the ruleset is called.
        $less = <<<'LESS'
@card-border-color: #5c5c5c;

@card-styles: {
    border: 1px solid @card-border-color;
};

.card {
    @card-styles();
}
LESS;

        $css = <<<'CSS'
.card {
  border: 1px solid var(--card-border-color, #5c5c5c);
}
CSS;

        $this->assertCss($css, $less);
    }

    public function testColorVariableInsideCssFunctionIsReplaced(): void
    {
        // CSS functions (unlike Less built-ins such as fade()) remain call nodes after compilation.
        // The visitor re-compiles them with var() replacement enabled.
        $less = <<<'LESS'
@state-critical: #ff5566;

.alert-box {
    color: calc(@state-critical);
}
LESS;

        $css = <<<'CSS'
.alert-box {
  color: calc(var(--state-critical, #ff5566));
}
CSS;

        $this->assertCss($css, $less);
    }

    public function testVariableVariables(): void
    {
        $less = <<<'LESS'
@state-ok: #44bb77;
@state-critical: #ff5566;

.status-panel {
    @color-var: state-ok;
    color: @@color-var;
}

.alert-box {
    color: @@alert-color-var;
}

@alert-color-var: state-critical;
LESS;

        $css = <<<'CSS'
.status-panel {
  color: var(--state-ok, #44bb77);
}
.alert-box {
  color: var(--state-critical, #ff5566);
}
CSS;

        $this->assertCss($css, $less);
    }

    public function testPropertyInterpolationWithColorVariable(): void
    {
        $less = <<<'LESS'
@state-property: color;
@state-ok: #44bb77;

.status-indicator {
    @{state-property}: @state-ok;
}
LESS;

        $css = <<<'CSS'
.status-indicator {
  color: var(--state-ok, #44bb77);
}
CSS;

        $this->assertCss($css, $less);
    }

    public function testColorVariablesInMixinBodyAndNestedRules(): void
    {
        $less = <<<'LESS'
@default-text-color: #ecf0f6;

.page-header-styles() {
    color: @default-text-color;

    .badge {
        color: @default-text-color !important;
    }
}

.page-header {
    .page-header-styles();
}
LESS;

        $css = <<<'CSS'
.page-header {
  color: var(--default-text-color, #ecf0f6);
}
.page-header .badge {
  color: var(--default-text-color, #ecf0f6) !important;
}
CSS;

        $this->assertCss($css, $less);
    }

    public function testColorVariablesInNamespacedMixin(): void
    {
        $less = <<<'LESS'
@state-ok: #44bb77;

#icinga {
    .state-color() {
        color: @state-ok;
    }
}

.state-ok-badge {
    #icinga.state-color();
}
LESS;

        $css = <<<'CSS'
.state-ok-badge {
  color: var(--state-ok, #44bb77);
}
CSS;

        $this->assertCss($css, $less);
    }

    public function testColorVariablesInGuardedNamespace(): void
    {
        $less = <<<'LESS'
@icinga-theme: dark;
@state-ok: #44bb77;

#theme when (@icinga-theme = dark) {
    .state-badge() {
        background-color: @state-ok;
    }
}

.status-badge {
    #theme.state-badge();
}
LESS;

        $css = <<<'CSS'
.status-badge {
  background-color: var(--state-ok, #44bb77);
}
CSS;

        $this->assertCss($css, $less);
    }

    public function testOutOfOrderNamedArgumentsAreReplaced(): void
    {
        $less = <<<'LESS'
@default-bg: #282e39;
@state-warning: #ffaa44;
@default-text-color: #ecf0f6;

.status-button(@bg-color: @default-bg, @label-color: @default-text-color) {
    background-color: @bg-color;
    color: @label-color;
}

.warning-button {
    .status-button(@label-color: @default-text-color, @bg-color: @state-warning);
}
LESS;

        $css = <<<'CSS'
.warning-button {
  background-color: var(--state-warning, #ffaa44);
  color: var(--default-text-color, #ecf0f6);
}
CSS;

        $this->assertCss($css, $less);
    }

    public function testRestParameterWithColorVariable(): void
    {
        $less = <<<'LESS'
@default-text-color: #ecf0f6;
@state-ok: #44bb77;

.text-with-shadow(@fg-color, @shadow...) {
    color: @fg-color;
    text-shadow: @shadow;
}

.success-hint {
    .text-with-shadow(@default-text-color, 0, 0, 3px, @state-ok);
}
LESS;

        $css = <<<'CSS'
.success-hint {
  color: var(--default-text-color, #ecf0f6);
  text-shadow: 0 0 3px var(--state-ok, #44bb77);
}
CSS;

        $this->assertCss($css, $less);
    }

    public function testNestedDetachedRulesetCalls(): void
    {
        $less = <<<'LESS'
@state-ok: #44bb77;
@state-critical: #ff5566;

@status-styles: {
    color: @state-ok;
    @focus-styles();
};

@focus-styles: {
    outline-color: @state-critical;
};

.status-badge {
    @status-styles();
}
LESS;

        $css = <<<'CSS'
.status-badge {
  color: var(--state-ok, #44bb77);
  outline-color: var(--state-critical, #ff5566);
}
CSS;

        $this->assertCss($css, $less);
    }

    public function testAliasedColorInsideBuiltInFunctionIsResolved(): void
    {
        // When an aliased color variable is passed to a Less built-in function,
        // the function receives the resolved color value — not a var() call.
        $less = <<<'LESS'
@state-ok: #44bb77;
@badge-color: @state-ok;

.hint-label {
    color: fade(@badge-color, 60%);
}
LESS;

        $css = <<<'CSS'
.hint-label {
  color: rgba(68, 187, 119, 0.6);
}
CSS;

        $this->assertCss($css, $less);
    }

    public function testMapLookupIsNotBrokenByVisitor(): void
    {
        // Less 3.5+ map lookups should pass through unchanged — there is no variable node to replace.
        $less = <<<'LESS'
#state-colors() {
    ok: #44bb77;
    critical: #ff5566;
}

.state-ok-dot {
    color: #state-colors[ok];
}
LESS;

        $css = <<<'CSS'
.state-ok-dot {
  color: #44bb77;
}
CSS;

        $this->assertCss($css, $less);
    }

    public function testVisitorInstanceIsReusableAcrossMultipleParsers(): void
    {
        // run() clones the visitor before each traversal so that $mixinParams and
        // $replaceCssVars from one parse do not leak into the next.
        // If cloning were removed, @color would remain on the mixin-param exclusion
        // stack after the first parse and would be skipped in the second parse,
        // causing @state-critical to pass through unreplaced.
        $visitor = new CssVarVisitor();

        $less1 = <<<'LESS'
@state-ok: #44bb77;
.state-label(@color) { color: @color; }
.state-ok { .state-label(@state-ok); }
LESS;

        $less2 = <<<'LESS'
@state-critical: #ff5566;
.state-label(@color) { color: @color; }
.state-critical { .state-label(@state-critical); }
LESS;

        $parser1 = new Less_Parser(['plugins' => [$visitor]]);
        $css1 = $parser1->parse($less1)->getCss();

        $parser2 = new Less_Parser(['plugins' => [$visitor]]);
        $css2 = $parser2->parse($less2)->getCss();

        $this->assertStringContainsString('var(--state-ok, #44bb77)', $css1);
        $this->assertStringContainsString('var(--state-critical, #ff5566)', $css2);
    }

    public function testColorMathProducesResolvedValue(): void
    {
        $this->markTestSkipped('Math is not yet supported');

        // Color arithmetic requires the resolved color value — not a var() call.
        $less = <<<'LESS'
@default-bg: #282e39;

.card-elevated {
    border-top-color: @default-bg + #111111;
}
LESS;

        $css = <<<'CSS'
.card-elevated {
  border-top-color: #393f4a;
}
CSS;

        $this->assertCss($css, $less);
    }

    public function testColorTypeGuardIsNotBrokenByReplacement(): void
    {
        $this->markTestSkipped('iscolor() guards is not yet supported');

        // iscolor() guards must still match when the argument is a color variable.
        $less = <<<'LESS'
@state-ok: #44bb77;

.state-badge(@c) when (iscolor(@c)) {
    background-color: @c;
}

.state-badge(@c) when (default()) {
    background-color: transparent;
}

.state-ok-badge {
    .state-badge(@state-ok);
}
LESS;

        $css = <<<'CSS'
.state-ok-badge {
  background-color: var(--state-ok, #44bb77);
}
CSS;

        $this->assertCss($css, $less);
    }

    public function testVariableInterpolationInSelectorIsUnaffected(): void
    {
        // @{state-name} is a selector interpolation, not a color variable reference.
        // The visitor must not interfere with it; the color reference is still replaced.
        $less = <<<'LESS'
@state-ok: #44bb77;
@state-name: state-ok;

.@{state-name} {
    color: @state-ok;
}
LESS;

        $css = <<<'CSS'
.state-ok {
  color: var(--state-ok, #44bb77);
}
CSS;

        $this->assertCss($css, $less);
    }

    public function testSameColorUsedInMultiplePlacesProducesSameFallback(): void
    {
        // Regression guard: if the visitor were to modify the Less_Tree_Color instance returned
        // by the environment, later resolutions of the same variable would see the mutated object
        // and could produce a different fallback value.
        $less = <<<'LESS'
@state-ok: #44bb77;

.a {
    color: @state-ok;
}

.b {
    background: @state-ok;
}

.c {
    border-color: @state-ok;
}
LESS;

        $css = <<<'CSS'
.a {
  color: var(--state-ok, #44bb77);
}
.b {
  background: var(--state-ok, #44bb77);
}
.c {
  border-color: var(--state-ok, #44bb77);
}
CSS;

        $this->assertCss($css, $less);
    }

    public function testColorUsedInsideBuiltinFunctionAndDirectly(): void
    {
        // Regression guard: if the visitor were to modify the Less_Tree_Color instance, colors
        // used inside a built-in function (where CSS var() replacement is skipped) could corrupt
        // later direct-use resolutions of the same variable.
        $less = <<<'LESS'
@brand: #ff0000;

.a {
    opacity: fade(@brand, 50%);
}

.b {
    color: @brand;
}
LESS;

        $css = <<<'CSS'
.a {
  opacity: rgba(255, 0, 0, 0.5);
}
.b {
  color: var(--brand, #ff0000);
}
CSS;

        $this->assertCss($css, $less);
    }

    protected function assertCss(string $expectedCss, string $actualLess, array $plugins = []): void
    {
        parent::assertCss($expectedCss, $actualLess, $plugins ?: [new CssVarVisitor()]);
    }
}
