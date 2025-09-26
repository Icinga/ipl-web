<?php

namespace ipl\Web\Compat\FormDecorator;

use ipl\Html\Attributes;
use ipl\Html\Contract\DecorationResult;
use ipl\Html\Contract\FormElementDecoration;
use ipl\Html\Contract\FormElement;
use ipl\Html\FormElement\CheckboxElement;
use ipl\Html\HtmlDocument;
use ipl\Html\HtmlElement;

/**
 * Decorates the checkbox element
 */
class CheckboxDecorator implements FormElementDecoration
{
    public function decorateFormElement(DecorationResult $result, FormElement $formElement): void
    {
        if (! $formElement instanceof CheckboxElement) {
            return;
        }

        $elementAttrs = $formElement->getAttributes();
        $elementAttrs->add('class', 'sr-only');

        if (! $elementAttrs->has('id')) {
            $elementAttrs->set('id', uniqid('checkbox_'));
        }

        $attributes = new Attributes([
            'class'         => 'toggle-switch',
            'aria-hidden'   => 'true',
            'for'           => $elementAttrs->get('id')->getValue()
        ]);

        if ($elementAttrs->get('disabled')->getValue()) {
            $attributes->add('class', 'disabled');
        }

        $formElement->prependWrapper(
            (new HtmlDocument())->addHtml(
                $formElement,
                new HtmlElement(
                    'label',
                    $attributes,
                    new HtmlElement('span', Attributes::create(['class' => 'toggle-slider']))
                )
            )
        );
    }
}
