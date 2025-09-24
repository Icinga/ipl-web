<?php

namespace ipl\Tests\Web\Compat\FormDecorator;

use ipl\Html\FormDecoration\FormElementDecorationResult;
use ipl\Html\FormElement\TextElement;
use ipl\Web\Compat\FormDecorator\DescriptionDecorator;
use ipl\Tests\Html\TestCase as IplHtmlTestCase;

class DescriptionDecoratorTest extends IplHtmlTestCase
{
    protected DescriptionDecorator $decorator;

    public function setUp(): void
    {
        $this->decorator = new DescriptionDecorator();
    }

    public function testWithDescriptionAndIdAttribute(): void
    {
        $result = new FormElementDecorationResult();
        $this->decorator->decorateFormElement(
            $result,
            new TextElement('test', ['description' => 'Testing', 'id' => 'test-id']) // added id to avoid random id
        );

        $html = <<<'HTML'
<i class="icon fa-info-circle control-info fa" role="img" title="Testing" aria-hidden="true"></i>
<span id="desc_test-id" class="sr-only">Testing</span>
HTML;

        $this->assertHtml($html, $result->assemble());
    }

    public function testWithEmptyDescriptionAttribute(): void
    {
        $result = new FormElementDecorationResult();
        $this->decorator->decorateFormElement(
            $result,
            new TextElement('test', ['description' => '', 'id' => 'test-id']) // added id to avoid random id
        );

        $html = <<<'HTML'
<i class="icon fa-info-circle control-info fa" role="img" title="" aria-hidden="true"></i>
<span id="desc_test-id" class="sr-only"></span>
HTML;

        $this->assertHtml($html, $result->assemble());
    }
}
