<?php

namespace ipl\Tests\Web\Compat\FormDecorator;

use ipl\Html\Contract\Form;
use ipl\Html\FormDecoration\FormElementDecorationResult;
use ipl\Html\FormElement\TextElement;
use ipl\I18n\NoopTranslator;
use ipl\I18n\StaticTranslator;
use ipl\Tests\Html\TestCase as IplHtmlTestCase;
use ipl\Web\Compat\FormDecorator\LabelDecorator;

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
            'label' => 'test-label',
            'id' => 'test-id'
        ]);

        $results = new FormElementDecorationResult();
        $this->decorator->decorateFormElement($results, $formElement);

        $html = <<<'HTML'
        <label class="form-element-label" for="test-id">test-label
        <span class="required-hint" aria-hidden="true" title="Required"> *</span></label>
        HTML;

        $this->assertHtml($html, $results->assemble());
        $this->assertSame($formElement->getAttribute('aria-required')->getValue(), 'true');
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
        $this->assertFalse($formElement->hasAttribute('aria-required'));
    }

    public function testFormDecoration(): void
    {
        $formElements = [
            new TextElement('test_required_1', [
                'required' => true,
                'label' => 'test-label'
            ]),
            new TextElement('test_required_2', [
                'required' => true,
                'label' => 'test-label'
            ]),
            new TextElement('test_no_label'),
            new TextElement('test_non_required', [
                'required' => false,
                'label' => 'test-label'
            ]),
        ];

        $results = new FormElementDecorationResult();
        $formStub = $this->createStub(Form::class);
        foreach ($formElements as $formElement) {
            $this->decorator->decorateFormElement($results, $formElement);
        }

        $this->decorator->decorateForm($results, $formStub);
        $assembledResults = $results->assemble();
        $this->assertStringEndsWith(
            '</label><ul class="form-info"><li>* Required field</li></ul>',
            $assembledResults
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

        $this->assertStringEndsNotWith(
            '<ul class="form-info"><li>* Required field</li></ul>',
            $results->assemble()
        );
    }
}
