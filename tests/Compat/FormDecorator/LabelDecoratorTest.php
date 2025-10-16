<?php

namespace ipl\Tests\Web\Compat\FormDecorator;

use ipl\Html\Form;
use ipl\Html\FormDecoration\FormElementDecorationResult;
use ipl\Html\FormElement\TextElement;
use ipl\I18n\NoopTranslator;
use ipl\I18n\StaticTranslator;
use ipl\Tests\Html\TestCase as IplHtmlTestCase;
use ipl\Web\Compat\FormDecorator\LabelDecorator;
use ipl\Web\Compat\CompatForm;

class LabelDecoratorTest extends IplHtmlTestCase
{
    protected LabelDecorator $decorator;

    public function setUp(): void
    {
        $this->decorator = new LabelDecorator();
        StaticTranslator::$instance = new NoopTranslator();
    }

    public function testWithoutLabelAttribute(): void
    {
        $results = new FormElementDecorationResult();
        $this->decorator->decorateFormElement($results, new TextElement('test'));

        $this->assertSame('&nbsp;', $results->assemble()->render());
    }

    public function testRequiredElement(): void
    {
        $formElement = new TextElement('test', [
            'required' => true,
            'label' => 'test-label'
        ]);

        $results = new FormElementDecorationResult();
        $this->decorator->decorateFormElement($results, $formElement);

        $this->assertStringContainsString(
            '<span class="required-hint" aria-hidden="true" title="Required"> *</span>',
            $results->assemble()->render()
        );
    }

    public function testNonRequiredElement(): void
    {
        $formElement = new TextElement('test', [
            'required' => false,
            'label' => 'test-label'
        ]);

        $results = new FormElementDecorationResult();
        $this->decorator->decorateFormElement($results, $formElement);

        $this->assertStringNotContainsString(
            '<span class="required-hint" aria-hidden="true" title="Required"> *</span>',
            $results->assemble()->render()
        );
    }

    public function testFormWithRequiredElement(): void
    {
        $formElement = new TextElement('test', [
            'required' => true,
            'label' => 'test-label'
        ]);

        $results = new FormElementDecorationResult();
        $formStub = $this->createStub(Form::class);

        $this->decorator->decorateFormElement($results, $formElement);
        $this->decorator->decorateForm($results, $formStub);

        $this->assertStringEndsWith(
            '<ul class="form-info"><li>* Required field</li></ul>',
            $results->assemble()->render()
        );
    }

    public function testFormWithoutRequiredElements(): void
    {
        $formElement = new TextElement('test', [
            'required' => false,
            'label' => 'test-label'
        ]);

        $results = new FormElementDecorationResult();
        $formStub = $this->createStub(Form::class);

        $this->decorator->decorateFormElement($results, $formElement);
        $this->decorator->decorateForm($results, $formStub);

        $this->assertStringNotContainsString(
            '<ul class="form-info"><li>* Required field</li></ul>',
            $results->assemble()->render()
        );
    }
}
