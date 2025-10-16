<?php

namespace ipl\Tests\Web\Compat;

use ipl\Html\FormElement\SubmitElement;
use ipl\I18n\NoopTranslator;
use ipl\I18n\StaticTranslator;
use ipl\Tests\Html\TestCase;
use ipl\Web\Compat\CompatForm;

class CompatFormTest extends TestCase
{
    /** @var CompatForm */
    private $form;

    protected function setUp(): void
    {
        $this->form = new CompatForm();
        StaticTranslator::$instance = new NoopTranslator();
    }

    public function testDuplicateSubmitButtonApplied(): void
    {
        $this->form->addElement('submit', 'submitCreate');
        $this->form->addElement('submit', 'submitDelete');

        $expected = <<<'HTML'
    <form class="icinga-form icinga-controls" method="POST">
      <input class="primary-submit-btn-duplicate" name="submitCreate" type="submit" value="submitCreate"/>
      <div class="control-group form-controls">
        <input class="btn-primary" name="submitCreate" type="submit" value="submitCreate"/>
      </div>
      <div class="control-group form-controls">
        <input class="btn-primary" name="submitDelete" type="submit" value="submitDelete"/>
      </div>
    </form>
HTML;

        $this->assertHtml($expected, $this->form);
    }

    public function testSubmitElementDuplication(): void
    {
        $this->form->addElement('submit', 'submit', [
            'label' => 'Submit label',
            'class' => 'btn-primary'
        ]);
        $this->form->addElement('submit', 'delete', [
            'label' => 'Delete label',
            'class' => 'btn-danger'
        ]);
        $this->form->setSubmitButton($this->form->getElement('submit'));

        $expected = <<<'HTML'
    <form class="icinga-form icinga-controls" method="POST">
      <input class="primary-submit-btn-duplicate" name="submit" type="submit" value="Submit label"/>
      <div class="control-group form-controls">
        <input class="btn-primary btn-primary" name="submit" type="submit" value="Submit label"/>
      </div>
      <div class="control-group form-controls">
        <input class="btn-danger btn-primary" name="delete" type="submit" value="Delete label"/>
      </div>
    </form>
HTML;

        $this->assertHtml($expected, $this->form);
    }


    public function testSubmitButtonElementDuplication(): void
    {
        $this->form->addElement('submitButton', 'submit', [
            'label' => 'Submit label',
            'class' => 'btn-primary',
            'value' => 'submit_value'
        ]);
        $this->form->addElement('submitButton', 'delete', [
            'label' => 'Delete label',
            'class' => 'btn-danger'
        ]);
        $this->form->setSubmitButton($this->form->getElement('submit'));

        $expected = <<<'HTML'
    <form class="icinga-form icinga-controls" method="POST">
      <button class="primary-submit-btn-duplicate" name="submit" type="submit" value="submit_value" />
      <div class="control-group form-controls">
        <button class="btn-primary btn-primary" name="submit" type="submit" value="submit_value">Submit label</button>
      </div>
      <div class="control-group form-controls">
        <button class="btn-danger btn-primary" name="delete" type="submit" value="y">Delete label</button>
      </div>
    </form>
HTML;

        $this->assertHtml($expected, $this->form);
    }

    public function testDuplicateSubmitButtonOmitted(): void
    {
        $this->form->addElement('submit', 'submitCreate');

        $expected = <<<'HTML'
    <form class="icinga-form icinga-controls" method="POST">
      <div class="control-group form-controls">
        <input class="btn-primary" name="submitCreate" type="submit" value="submitCreate"/>
      </div>
    </form>
HTML;

        $this->assertHtml($expected, $this->form);
    }

    public function testDuplicateSubmitButtonAddedOnlyOnce(): void
    {
        $this->form->addElement('submit', 'submitCreate', ['id' => 'submit_id']);
        $this->form->addElement('submit', 'submitDelete');

        $expected = <<<'HTML'
    <form class="icinga-form icinga-controls" method="POST">
      <input class="primary-submit-btn-duplicate" name="submitCreate" type="submit" value="submitCreate"/>
      <div class="control-group form-controls">
        <input id="submit_id" class="btn-primary" name="submitCreate" type="submit" value="submitCreate"/>
      </div>
      <div class="control-group form-controls">
        <input class="btn-primary" name="submitDelete" type="submit" value="submitDelete"/>
      </div>
    </form>
HTML;

        // Call render twice to ensure that the submit button is only prepended once.
        $this->form->render();
        $this->assertHtml($expected, $this->form);
    }

    public function testDuplicateSubmitButtonRespectsOriginalAttributes(): void
    {
        $submitButton = new SubmitElement('test_submit', [
            'class'          => 'autosubmit',
            'formnovalidate' => true
        ]);

        $prefixButton = $this->form->duplicateSubmitButton($submitButton);

        // Name should stay the same
        $this->assertSame($submitButton->getName(), 'test_submit');
        $this->assertSame($prefixButton->getName(), 'test_submit');

        // Added attributes should stay the same
        $this->assertSame($submitButton->getAttributes()->get('formnovalidate')->getValue(), true);
        $this->assertSame($prefixButton->getAttributes()->get('formnovalidate')->getValue(), true);

        // Class attribute should change to `primary-submit-btn-duplicate`
        $this->assertSame($submitButton->getAttributes()->get('class')->getValue(), 'autosubmit');
        $this->assertSame($prefixButton->getAttributes()->get('class')->getValue(), 'primary-submit-btn-duplicate');
    }

    public function testDefaultElementDecoratorsWithRequiredField(): void
    {
        $this->form->applyDefaultElementDecorators()
            ->addElement('text', 'test_text', ['required' => true, 'label' => 'test'])->render();

        $this->assertStringContainsString(
            '<span class="required-hint" aria-hidden="true" title="Required"> *</span>',
            $this->form->render()
        );

        $this->assertStringEndsWith(
            '<ul class="form-info"><li>* Required field</li></ul></form>',
            $this->form->render()
        );
    }

    public function testDefaultElementDecoratorsWithNonRequiredField(): void
    {
        $this->form->applyDefaultElementDecorators()
            ->addElement('text', 'test_text', ['required' => false, 'label' => 'test'])->render();

        $this->assertStringNotContainsString(
            '<span class="required-hint" aria-hidden="true" title="Required"> *</span>',
            $this->form->render()
        );

        $this->assertStringNotContainsString(
            '<ul class="form-info"><li>* Required field</li></ul>',
            $this->form->render()
        );
    }

    public function testDefaultElementDecoratorsWithoutLabels(): void
    {
        $this->form->applyDefaultElementDecorators()
            ->addElement('text', 'test_text')->render();

        $this->assertStringNotContainsString(
            "\&nbsp;",
            $this->form->render()
        );

        $this->assertStringNotContainsString(
            '<span class="required-hint" aria-hidden="true" title="Required"> *</span>',
            $this->form->render()
        );

        $this->assertStringNotContainsString(
            '<ul class="form-info"><li>* Required field</li></ul>',
            $this->form->render()
        );
    }
}
