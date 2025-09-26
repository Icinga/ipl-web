<?php

namespace ipl\Tests\Web\Compat\FormDecorator;

use ipl\Html\FormDecoration\FormElementDecorationResult;
use ipl\Html\FormElement\CheckboxElement;
use ipl\Html\Html;
use ipl\Tests\Html\TestCase as IplHtmlTestCase;
use ipl\Web\Compat\FormDecorator\CheckboxDecorator;

class CheckboxDecoratorTest extends IplHtmlTestCase
{
    protected CheckboxDecorator $decorator;

    public function setUp(): void
    {
        $this->decorator = new CheckboxDecorator();
    }

    public function testDecoration(): void
    {
        $element = new CheckboxElement('test', ['id' => 'test-id']);
        $this->decorator->decorateFormElement(new FormElementDecorationResult(), $element);

        $html = <<<'HTML'
<input name="test" type="hidden" value="n"/>
<input class="sr-only" id="test-id" name="test" type="checkbox" value="y"/>
<label aria-hidden="true" class="toggle-switch" for="test-id">
  <span class="toggle-slider"></span>
</label>
HTML;

        $this->assertHtml($html, $element);
    }

    public function testDecoratorCreatesRandomIdIfNotSpecified(): void
    {
        $element = new CheckboxElement('test');

        $this->assertFalse($element->getAttributes()->has('id'));

        $this->decorator->decorateFormElement(new FormElementDecorationResult(), $element);

        $this->assertTrue($element->getAttributes()->has('id'));
        $this->assertNotEmpty($element->getAttributes()->get('id')->getValue());
    }

    public function testDecoratorSetDisabledClassToLabelElementIfCheckboxIsDisabled(): void
    {
        $element = new CheckboxElement('test', ['disabled' => true, 'id' => 'test-id']);
        $this->decorator->decorateFormElement(new FormElementDecorationResult(), $element);

        $html = <<<'HTML'
<input name="test" type="hidden" value="n"/>
<input class="sr-only" id="test-id" name="test" type="checkbox" value="y" disabled/>
<label aria-hidden="true" class="toggle-switch disabled" for="test-id">
  <span class="toggle-slider"></span>
</label>
HTML;

        $this->assertHtml($html, $element);
    }

    public function testWrapperDoesNotAffectDecoration(): void
    {
        $element = new CheckboxElement('test', ['id' => 'test-id']);
        $element->addWrapper(Html::tag('div'));

        $this->decorator->decorateFormElement( new FormElementDecorationResult(), $element);

        $html = <<<'HTML'
<div>
  <input name="test" type="hidden" value="n"/>
  <input class="sr-only" id="test-id" name="test" type="checkbox" value="y"/>
  <label aria-hidden="true" class="toggle-switch" for="test-id">
    <span class="toggle-slider"></span>
  </label>
</div>
HTML;

        $this->assertHtml($html, $element);
    }
}
