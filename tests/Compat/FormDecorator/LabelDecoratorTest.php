<?php

namespace ipl\Tests\Web\Compat\FormDecorator;

use ipl\Html\FormDecoration\FormElementDecorationResult;
use ipl\Html\FormElement\TextElement;
use ipl\Tests\Html\TestCase as IplHtmlTestCase;
use ipl\Web\Compat\FormDecorator\LabelDecorator;

class LabelDecoratorTest extends IplHtmlTestCase
{
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
}
