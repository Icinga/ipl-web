<?php

namespace ipl\Tests\Web\Compat;

use ipl\Html\FormElement\SubmitElement;
use ipl\I18n\NoopTranslator;
use ipl\I18n\StaticTranslator;
use ipl\Tests\Html\TestCase;
use ipl\Web\Compat\CompatForm;

class CompatFormTest extends TestCase
{
    protected function setUp(): void
    {
        StaticTranslator::$instance = new NoopTranslator();
    }

    public function testDuplicateSubmitButtonApplied(): void
    {
        $form = new CompatForm();
        $form->addElement('submit', 'submitCreate');
        $form->addElement('submit', 'submitDelete');

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

        $this->assertHtml($expected, $form);
    }

    public function testSubmitElementDuplication(): void
    {
        $form = new CompatForm();
        $form->addElement('submit', 'submit', [
            'label' => 'Submit label',
            'class' => 'btn-primary'
        ]);
        $form->addElement('submit', 'delete', [
            'label' => 'Delete label',
            'class' => 'btn-danger'
        ]);
        $form->setSubmitButton($form->getElement('submit'));

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

        $this->assertHtml($expected, $form);
    }


    public function testSubmitButtonElementDuplication(): void
    {
        $form = new CompatForm();
        $form->addElement('submitButton', 'submit', [
            'label' => 'Submit label',
            'class' => 'btn-primary',
            'value' => 'submit_value'
        ]);
        $form->addElement('submitButton', 'delete', [
            'label' => 'Delete label',
            'class' => 'btn-danger'
        ]);
        $form->setSubmitButton($form->getElement('submit'));

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

        $this->assertHtml($expected, $form);
    }

    public function testDuplicateSubmitButtonOmitted(): void
    {
        $form = new CompatForm();
        $form->addElement('submit', 'submitCreate');

        $expected = <<<'HTML'
    <form class="icinga-form icinga-controls" method="POST">
      <div class="control-group form-controls">
        <input class="btn-primary" name="submitCreate" type="submit" value="submitCreate"/>
      </div>
    </form>
HTML;

        $this->assertHtml($expected, $form);
    }

    public function testDuplicateSubmitButtonAddedOnlyOnce(): void
    {
        $form = new CompatForm();
        $form->addElement('submit', 'submitCreate', ['id' => 'submit_id']);
        $form->addElement('submit', 'submitDelete');

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
        $form->render();
        $this->assertHtml($expected, $form);
    }

    public function testDuplicateSubmitButtonRespectsOriginalAttributes(): void
    {
        $submitButton = new SubmitElement('test_submit', [
            'class'          => 'autosubmit',
            'formnovalidate' => true
        ]);

        $prefixButton = (new CompatForm())->duplicateSubmitButton($submitButton);

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

    public function testLabelDecoration(): void
    {
        $form = new CompatForm();
        $form->applyDefaultElementDecorators()
            ->addElement(
                'text',
                'test_text_non_required',
                ['required' => false, 'label' => 'test_non_required', 'id' => 'test-id-required']
            )
            ->addElement('text', 'test_text_no_label')
            ->addElement(
                'text',
                'test_text_required',
                ['required' => true, 'label' => 'test_required', 'id' => 'test-id-non-required']
            );

        $expected = <<<'HTML'
    <form class="icinga-form icinga-controls" method="POST">
        <div class="control-group">
            <div class="control-label-group">
                <label class="form-element-label" for="test-id-required">
                    test_non_required
                </label>
            </div>
            <input name="test_text_non_required" type="text" id="test-id-required"/>
        </div>
        <div class="control-group">
            <div class="control-label-group">
                &nbsp;
            </div>
            <input name="test_text_no_label" type="text"/>
        </div>
        <div class="control-group">
            <div class="control-label-group">
                <label class="form-element-label" for="test-id-non-required">
                    test_required
                    <span class="required-hint" aria-hidden="true" title="Required"> *</span>
                </label>
            </div>
            <input required aria-required="true" name="test_text_required" type="text" id="test-id-non-required"/>
        </div>
        <ul class="form-info">
            <li>
                * Required field
            </li>
        </ul>
    </form>
HTML;

        $this->assertHtml($expected, $form);
    }

    public function testFieldsetDecoration(): void
    {
        $form = new CompatForm();
        $form
            ->applyDefaultElementDecorators()
            ->addElement('fieldset', 'foo', [
                'label'     => 'Legend here',
                'description' => 'Description here',
                'id' => 'foo-id'
            ]);

        $expected = <<<'HTML'
    <form class="icinga-form icinga-controls" method="POST">
        <div class="control-group">
            <fieldset name="foo" id="foo-id" aria-describedby="desc_foo-id">
                <legend>Legend here</legend>
                <p id="desc_foo-id">Description here</p>
            </fieldset>
        </div>
    </form>
HTML;

        $this->assertHtml($expected, $form);
    }

    public function testCheckboxDecoration(): void
    {
        $form = new CompatForm();
        $form
            ->applyDefaultElementDecorators()
            ->addElement('checkbox', 'foo', ['label' => 'Label here', 'id' => 'foo-id']);

        $expected = <<<'HTML'
    <form class="icinga-form icinga-controls" method="POST">
        <div class="control-group">
            <div class="control-label-group">
                <label class="form-element-label" for="foo-id">Label here</label>
            </div>
            <input name="foo" type="hidden" value="n"/>
            <input class="sr-only" id="foo-id" name="foo" type="checkbox" value="y"/>
            <label class="toggle-switch" aria-hidden="true" for="foo-id">
                <span class="toggle-slider"></span>
            </label>
        </div>
    </form>
HTML;

        $this->assertHtml($expected, $form);
    }

    public function testDescriptionDecoration(): void
    {
        $form = new CompatForm();
        $form
            ->applyDefaultElementDecorators()
            ->addElement('text', 'foo', ['description' => 'Description here', 'id' => 'foo-id']);

        $expected = <<<'HTML'
    <form class="icinga-form icinga-controls" method="POST">
        <div class="control-group">
            <div class="control-label-group">&nbsp;</div>
            <input name="foo" type="text" id="foo-id" aria-describedby="desc_foo-id"/>
             <i aria-hidden="true" class="icon fa-info-circle control-info fa" role="img" title="Description here"/>
            <span class="sr-only" id="desc_foo-id">Description here</span>
        </div>
    </form>
HTML;

        $this->assertHtml($expected, $form);
    }

    public function testErrorsDecoration(): void
    {
        $form = new CompatForm();
        $form
            ->applyDefaultElementDecorators()
            ->addElement('text', 'foo');

        $el = $form->getElement('foo');
        $el->addMessage('First error');
        $el->addMessage('Second error');

        $expected = <<<'HTML'
    <form class="icinga-form icinga-controls" method="POST">
        <div class="control-group">
            <div class="control-label-group">&nbsp;</div>
            <input name="foo" type="text"/>
            <ul class="errors">
                <li>First error</li>
                <li>Second error</li>
            </ul>
        </div>
    </form>
HTML;

        $this->assertHtml($expected, $form);
    }

    public function testFormControlsDecoration(): void
    {
        $form = new CompatForm();
        $form
            ->applyDefaultElementDecorators()
            ->addElement('submit', 'foo', ['label' => 'Submit Form']);

        $expected = <<<'HTML'
    <form class="icinga-form icinga-controls" method="POST">
        <div class="control-group form-controls">
            <input name="foo" type="submit" value="Submit Form"/>
        </div>
    </form>
HTML;

        $this->assertHtml($expected, $form);
    }

    public function testMethodApplyDefaultElementDecorators(): void
    {
        // A fieldset, a text element, a required checkbox, and a submit button should cover
        // all default element decorators.
        $form = new CompatForm();
        $form->applyDefaultElementDecorators();

        $fieldset = $form->createElement('fieldset', 'foo', [
            'label' => 'Fieldset Label',
            'description' => 'Fieldset Description',
            'id' => 'foo-id'
        ]);

        $fieldset->addElement('text', 'bar', [
            'label' => 'Legend here',
            'description' => 'Description here',
            'id' => 'bar-id'
        ]);

        $form
            ->addElement($fieldset)
            ->addElement('checkbox', 'fooBar', [
                'label' => 'Fieldset Label',
                'description' => 'Fieldset Description',
                'id' => 'fooBar-id'
            ])
            ->addElement('submit', 'submit_form', ['label' => 'Submit Form']);

        $expected = <<<'HTML'
    <form class="icinga-form icinga-controls" method="POST">
      <div class="control-group">
        <fieldset aria-describedby="desc_foo-id" id="foo-id" name="foo">
          <legend>Fieldset Label</legend>
          <p id="desc_foo-id">Fieldset Description</p>
          <div class="control-group">
            <div class="control-label-group">
              <label class="form-element-label" for="bar-id">Legend here</label>
            </div>
            <input aria-describedby="desc_bar-id" id="bar-id" name="foo[bar]" type="text"/>
            <i aria-hidden="true" class="icon fa-info-circle control-info fa" role="img" title="Description here"/>
            <span class="sr-only" id="desc_bar-id">Description here</span>
          </div>
        </fieldset>
      </div>
      <div class="control-group">
        <div class="control-label-group">
          <label class="form-element-label" for="fooBar-id">Fieldset Label</label>
        </div>
        <input name="fooBar" type="hidden" value="n"/>
        <input aria-describedby="desc_fooBar-id" class="sr-only" id="fooBar-id" name="fooBar" type="checkbox" value="y"/>
        <label aria-hidden="true" class="toggle-switch" for="fooBar-id">
          <span class="toggle-slider"/>
        </label>
        <i aria-hidden="true" class="icon fa-info-circle control-info fa" role="img" title="Fieldset Description"/>
        <span class="sr-only" id="desc_fooBar-id">Fieldset Description</span>
      </div>
      <div class="control-group form-controls">
        <input name="submit_form" type="submit" value="Submit Form"/>
      </div>
    </form>
HTML;

        $this->assertHtml($expected, $form);
    }
}
