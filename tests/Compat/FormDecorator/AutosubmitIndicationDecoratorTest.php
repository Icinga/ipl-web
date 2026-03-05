<?php

namespace ipl\Tests\Web\Compat\FormDecorator;

use ipl\Html\FormDecoration\FormElementDecorationResult;
use ipl\Html\FormElement\CheckboxElement;
use ipl\Html\FormElement\TextElement;
use ipl\I18n\NoopTranslator;
use ipl\I18n\StaticTranslator;
use ipl\Tests\Html\TestCase as IplHtmlTestCase;
use ipl\Web\Compat\FormDecorator\AutosubmitIndicationDecorator;

class AutosubmitIndicationDecoratorTest extends IplHtmlTestCase
{
    protected AutosubmitIndicationDecorator $decorator;

    public function setUp(): void
    {
        $this->decorator = new AutosubmitIndicationDecorator();
        StaticTranslator::$instance = new NoopTranslator();
    }

    public function testNoDecorationForNonCheckboxElement(): void
    {
        $result = new FormElementDecorationResult();
        $this->decorator->decorateFormElement(
            $result,
            new TextElement('test', ['class' => 'autosubmit', 'id' => 'test-id'])
        );

        $this->assertSame('', $result->assemble()->render());
    }

    public function testNoDecorationForCheckboxWithoutAutosubmitClass(): void
    {
        $element = new CheckboxElement('test', ['id' => 'test-id']);
        $result = new FormElementDecorationResult();
        $this->decorator->decorateFormElement($result, $element);

        $this->assertSame('', $result->assemble()->render());
        $this->assertFalse($element->getAttributes()->has('aria-describedby'));
    }

    public function testDecoratorAddAriaDescribedbyToAutosubmitCheckboxWithId(): void
    {
        $element = new CheckboxElement('test', ['class' => 'autosubmit', 'id' => 'test-id']);
        $result = new FormElementDecorationResult();
        $this->decorator->decorateFormElement($result, $element);

        $title = 'This page will be automatically updated upon change of the value';
        $html = <<<HTML
<i class="icon fa-rotate-right spinner fa" aria-hidden="true" role="img" title="$title"></i>
<span class="sr-only" id="autosubmit_indicator_test-id">$title</span>
HTML;

        $this->assertHtml($html, $result->assemble());
        $this->assertSame(
            'autosubmit_indicator_test-id',
            $element->getAttributes()->get('aria-describedby')->getValue()
        );
    }

    public function testDecoratorAddAriaDescribedbyToAutosubmitCheckboxWithoutId(): void
    {
        $element = new CheckboxElement('test', ['class' => 'autosubmit']);
        $result = new FormElementDecorationResult();
        $this->decorator->decorateFormElement($result, $element);

        $ariaDescribedBy = $element->getAttributes()->get('aria-describedby')->getValue();
        $this->assertNotEmpty($ariaDescribedBy);
        $this->assertStringStartsWith('autosubmit_indicator_', $ariaDescribedBy);
    }

    public function testCustomUniqueNameCallback(): void
    {
        $this->decorator->getAttributes()->set('uniqueName', fn($name) => 'custom-' . $name);

        $element = new CheckboxElement('test', ['class' => 'autosubmit']);
        $result = new FormElementDecorationResult();
        $this->decorator->decorateFormElement($result, $element);

        $this->assertSame(
            'autosubmit_indicator_custom-test',
            $element->getAttributes()->get('aria-describedby')->getValue()
        );
    }
}
