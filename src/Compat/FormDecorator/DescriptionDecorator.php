<?php

namespace ipl\Web\Compat\FormDecorator;

use ipl\Html\Attributes;
use ipl\Html\Contract\Decorator;
use ipl\Html\Contract\FormElement;
use ipl\Html\FormDecorator\DecorationResults;
use ipl\Html\FormDecorator\DecoratorOptions;
use ipl\Html\FormElement\FieldsetElement;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\Web\Widget\Icon;

/**
 * Decorate the description of the form element
 */
class DescriptionDecorator implements Decorator
{
    use DecoratorOptions;

    /** @var string|string[] CSS classes to apply */
    protected string|array $class = 'control-info';

    public function decorate(DecorationResults $results, FormElement $formElement): void
    {
        $description = $formElement->getDescription();

        if ($description === null || $formElement instanceof FieldsetElement) {
            return;
        }

        $descriptionId = null;
        if ($formElement->getAttributes()->has('id')) {
            $descriptionId = 'desc_' . $formElement->getAttributes()->get('id')->getValue();
            $formElement->getAttributes()->set('aria-describedby', $descriptionId);
        }

        $attributes = new Attributes([
            'class' => $this->class,
            'role'  => 'image',
            'title' => $description
        ]);

        $describedBy = null;
        if ($descriptionId) {
            $attributes->set('aria-hidden', 'true');
            $describedBy = new HtmlElement('span', Attributes::create([
                'id'    => $descriptionId,
                'class' => 'sr-only'
            ]), Text::create($description));
        }

        $results->append(new Icon('info-circle', $attributes));
        if ($describedBy) {
            $results->append($describedBy);
        }
    }

    protected function registerAttributeCallbacks(Attributes $attributes): void
    {
        $attributes
            ->registerAttributeCallback(
                'class',
                null,
                function ($value) {
                    $this->class = $value;
                }
            );
    }
}
