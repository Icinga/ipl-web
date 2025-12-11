<?php

namespace ipl\Tests\Web\Compat\FormDecorator;

use ipl\Html\FormDecoration\FormElementDecorationResult;
use ipl\Tests\Web\TestCase;
use ipl\Web\Compat\CompatForm;
use ipl\Web\Compat\FormDecorator\PrimaryButtonDecorator;

class PrimaryButtonDecoratorTest extends TestCase
{
    protected PrimaryButtonDecorator $decorator;

    public function setUp(): void
    {
        $this->decorator = new PrimaryButtonDecorator();
    }

    public function testEveryButtonIsMarkedAsPrimaryButton(): void
    {
        $form = (new CompatForm())
            ->addElement('submit', 'btn-1')
            ->addElement('submit', 'btn-2', ['class' => 'test'])
            ->addElement('submit', 'btn-3', ['class' => 'btn-remove'])
            ->addElement('submit', 'btn-4', ['class' => 'btn-primary']);

        $this->decorator->decorateForm(new FormElementDecorationResult(), $form);

        $this->assertSame(
            'class="btn-primary"',
            $form->getElement('btn-1')->getAttributes()->get('class')->render()
        );

        $this->assertSame(
            'class="test btn-primary"',
            $form->getElement('btn-2')->getAttributes()->get('class')->render()
        );

        $this->assertSame(
            'class="btn-remove btn-primary"',
            $form->getElement('btn-3')->getAttributes()->get('class')->render()
        );

        $this->assertSame(
            'class="btn-primary btn-primary"',
            $form->getElement('btn-4')->getAttributes()->get('class')->render()
        );
    }

    public function testElementsOtherThanSubmitButtonsAreIgnored(): void
    {
        $form = (new CompatForm())
            ->addElement('text', 'username', ['class' => 'test'])
            ->addElement('password', 'pwd', ['class' => 'test']);

        $this->decorator->decorateForm(new FormElementDecorationResult(), $form);

        $this->assertSame('class="test"', $form->getElement('username')->getAttributes()->get('class')->render());
        $this->assertSame('class="test"', $form->getElement('pwd')->getAttributes()->get('class')->render());
    }
}
