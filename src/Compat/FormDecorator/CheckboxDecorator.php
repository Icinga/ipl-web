<?php

namespace ipl\Web\Compat\FormDecorator;

use ipl\Html\Attributes;
use ipl\Html\Contract\Decorator;
use ipl\Html\Contract\DecoratorOptions;
use ipl\Html\Contract\FormElement;
use ipl\Html\FormDecorator\DecorationResults;
use ipl\Html\FormElement\CheckboxElement;
use ipl\Html\HtmlElement;

/**
 * Decorates the checkbox element
 */
class CheckboxDecorator implements Decorator
{
    use DecoratorOptions;

    /** @var string|string[] CSS classes to apply */
    protected string|array $class = 'toggle-switch';

    public function decorate(DecorationResults $results, FormElement $formElement): void
    {
        if (! $formElement instanceof CheckboxElement) {
            return;
        }

        $elementAttrs = $formElement->getAttributes();
        $elementAttrs->add('class', 'sr-only');

        if (! $elementAttrs->has('id')) {
            $elementAttrs->set('id', $formElement->getName() . '_' . mt_rand(5, 10));
        }

        $attributes = new Attributes([
            'class'         => $this->class,
            'aria-hidden'   => 'true',
            'for'           => $elementAttrs->get('id')->getValue()
        ]);

        if ($elementAttrs->get('disabled')->getValue()) {
            $attributes->add('class', 'disabled');
        }

        $results->append(new HtmlElement(
            'label',
            $attributes,
            new HtmlElement('span', Attributes::create(['class' => 'toggle-slider']))
        ));
    }

    protected function registerAttributeCallbacks(Attributes $attributes): void
    {
        $attributes->registerAttributeCallback('class', null, fn(string|array $value) => $this->class = $value);
    }
}
