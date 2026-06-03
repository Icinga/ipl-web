<?php

namespace ipl\Tests\Web\Widget;

use ipl\Html\Html;
use ipl\Html\Test\TestCase;
use ipl\Web\Common\CalloutType;
use ipl\Web\Widget\Callout;

class CalloutTest extends TestCase
{
    public function testCalloutWithoutTitle(): void
    {
        $callout = new Callout(CalloutType::Info, 'Content');

        $html = <<<'HTML'
<div class="callout callout-type-info">
    <i class="icon fa-circle-info fa"></i>
    <div class="callout-text">
        Content
    </div>
</div>
HTML;

        $this->assertHtml($html, $callout);
    }

    public function testCalloutWithTitle(): void
    {
        $callout = new Callout(CalloutType::Info, 'Content', 'Title');

        $html = <<<'HTML'
<div class="callout callout-type-info">
    <i class="icon fa-circle-info fa"></i>
    <div class="callout-text">
        <strong class="callout-title">Title</strong>
        Content
    </div>
</div>
HTML;

        $this->assertHtml($html, $callout);
    }

    public function testCalloutFalsyTitle(): void
    {
        $callout = new Callout(CalloutType::Warning, 'Content', '0');

        $html = <<<'HTML'
<div class="callout callout-type-warning">
    <i class="icon fa-warning fa"></i>
    <div class="callout-text">
        <strong class="callout-title">0</strong>
        Content
    </div>
</div>
HTML;
        $this->assertHtml($html, $callout);
    }

    public function testCalloutEmptyTitle(): void
    {
        $callout = new Callout(CalloutType::Error, 'Content', '');

        $html = <<<'HTML'
<div class="callout callout-type-error">
    <i class="icon fa-circle-xmark fa"></i>
    <div class="callout-text">
        Content
    </div>
</div>
HTML;
        $this->assertHtml($html, $callout);
    }

    public function testCalloutWithEmptyUtf8Title(): void
    {
        $callout = new Callout(CalloutType::Error, 'Content', "\u{2000}");

        $html = <<<'HTML'
<div class="callout callout-type-error">
    <i class="icon fa-circle-xmark fa"></i>
    <div class="callout-text">
        Content
    </div>
</div>
HTML;
        $this->assertHtml($html, $callout);
    }

    public function testCalloutValidHtmlContent(): void
    {
        $callout = new Callout(
            CalloutType::Success,
            Html::tag('p', ['class' => 'test-class'], 'This is a Test'),
            'Test Title',
        );

        $html = <<<'HTML'
<div class="callout callout-type-success">
    <i class="icon fa-circle-check fa"></i>
    <div class="callout-text">
        <strong class="callout-title">Test Title</strong>
        <p class="test-class">This is a Test</p>
    </div>
</div>
HTML;

        $this->assertHtml($html, $callout);
    }

    public function testCalloutFitContent(): void
    {
        $callout = (new Callout(CalloutType::Error, 'Content'))
            ->setFitContent(true);

        $html = <<<'HTML'
<div class="callout callout-type-error callout-fit-content">
    <i class="icon fa-circle-xmark fa"></i>
    <div class="callout-text">
        Content
    </div>
</div>
HTML;
        $this->assertHtml($html, $callout);

        $callout->setFitContent(false);

        $html2 = <<<'HTML'
<div class="callout callout-type-error">
    <i class="icon fa-circle-xmark fa"></i>
    <div class="callout-text">
        Content
    </div>
</div>
HTML;
        $this->assertHtml($html2, $callout);
    }
}
