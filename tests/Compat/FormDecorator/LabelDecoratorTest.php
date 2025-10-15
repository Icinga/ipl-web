<?php

namespace ipl\Tests\Web\Compat\FormDecorator;

use ipl\Html\FormDecoration\FormElementDecorationResult;
use ipl\Html\FormElement\TextElement;
use ipl\Tests\Html\TestCase as IplHtmlTestCase;
use ipl\Web\Compat\FormDecorator\LabelDecorator;
use ipl\Web\Compat\CompatForm;
use ipl\I18n\Translation;

class LabelDecoratorTest extends IplHtmlTestCase
{
    use Translation;

    protected LabelDecorator $decorator;

    public function setUp(): void
    {
        $this->decorator = new LabelDecorator();
    }

    public function testWithoutLabelAttribute(): void
    {
        $results = new FormElementDecorationResult();
        $this->decorator->decorateFormElement($results, new TextElement('test'));

        $this->assertSame('&nbsp;', $results->assemble()->render());
    }

    public function testWithRequiredField(): void
    {
        $formElement = new TextElement('test', [
            'required' => true,
            'label' => 'test-label'
        ]);

        $results = new FormElementDecorationResult();
        $form = (new CompatForm())->addElement($formElement);
        $this->decorator->decorateFormElement($results, $formElement);
        $this->decorator->decorateForm($results, $form);
        $renderedResults = $results->assemble()->render();

        $this->assertStringContainsString(
            '<span class="required-hint" aria-hidden="true" title="Required"> *</span>',
            $renderedResults
        );

        $this->assertStringContainsString(
            '<ul class="form-info"><li>* Required field</li></ul>',
            $renderedResults
        );
    }

    public function testWithoutRequiredField(): void
    {
        $formElement = new TextElement('test', [
            'required' => false,
            'label' => 'test-label'
        ]);

        $results = new FormElementDecorationResult();
        $form = (new CompatForm())->addElement($formElement);
        $this->decorator->decorateFormElement($results, $formElement);
        $this->decorator->decorateForm($results, $form);
        $renderedResults = $results->assemble()->render();

        $this->assertStringNotContainsString(
            '<span class="required-hint" aria-hidden="true" title="Required"> *</span>',
            $renderedResults
        );

        $this->assertStringNotContainsString(
            '<ul class="form-info"><li>* Required field</li></ul>',
            $renderedResults
        );
    }
}
