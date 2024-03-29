<?php

namespace ipl\Web\FormDecorator;

use Icinga\Web\Window;
use ipl\Html\Attributes;
use ipl\Html\Contract\FormSubmitElement;
use ipl\Html\FormDecorator\DivDecorator;
use ipl\Html\FormElement\CheckboxElement;
use ipl\Html\FormElement\FieldsetElement;
use ipl\Html\HtmlDocument;
use ipl\Html\HtmlElement;
use ipl\Html\HtmlString;
use ipl\Html\Text;
use ipl\Web\Widget\Icon;

class IcingaFormDecorator extends DivDecorator
{
    const SUBMIT_ELEMENT_CLASS = 'form-controls';
    const INPUT_ELEMENT_CLASS = 'control-group';
    const ERROR_CLASS = 'errors';

    protected function assembleElement()
    {
        if ($this->formElement instanceof FormSubmitElement) {
            $this->formElement->getAttributes()->add('class', 'btn-primary');
        }

        $element = parent::assembleElement();

        if ($element instanceof CheckboxElement) {
            return $this->createCheckbox($element);
        }

        return $element;
    }

    protected function createCheckbox(CheckboxElement $checkbox)
    {
        if (! $checkbox->getAttributes()->has('id')) {
            $checkbox->setAttribute(
                'id',
                $checkbox->getName() . '_' . Window::getInstance()->getContainerId()
            );
        }

        $checkbox->getAttributes()->add('class', 'sr-only');

        $classes = ['toggle-switch'];
        if ($checkbox->getAttributes()->get('disabled')->getValue()) {
            $classes[] = 'disabled';
        }

        $document = new HtmlDocument();
        $document->addHtml(
            $checkbox,
            new HtmlElement(
                'label',
                Attributes::create([
                    'class'       => $classes,
                    'aria-hidden' => 'true',
                    'for'         => $checkbox->getAttributes()->get('id')->getValue()
                ]),
                new HtmlElement('span', Attributes::create(['class' => 'toggle-slider']))
            )
        );

        $checkbox->prependWrapper($document);

        return $checkbox;
    }

    protected function assembleLabel()
    {
        $label = parent::assembleLabel();
        if (! $this->formElement instanceof FieldsetElement) {
            if ($label !== null) {
                $label->addWrapper(new HtmlElement('div', Attributes::create(['class' => 'control-label-group'])));
            } elseif (! $this->formElement instanceof FormSubmitElement) {
                $label = new HtmlElement(
                    'div',
                    Attributes::create(['class' => 'control-label-group']),
                    HtmlString::create('&nbsp')
                );
            }
        }

        return $label;
    }

    protected function assembleDescription()
    {
        if ($this->formElement instanceof FieldsetElement) {
            return parent::assembleDescription();
        }

        if (($description = $this->formElement->getDescription()) !== null) {
            $iconAttributes = [
                'class' => 'control-info',
                'role'  => 'image',
                'title' => $description
            ];

            $describedBy = null;
            if ($this->formElement->getAttributes()->has('id')) {
                $iconAttributes['aria-hidden'] = 'true';

                $descriptionId = 'desc_' . $this->formElement->getAttributes()->get('id')->getValue();
                $describedBy = new HtmlElement('span', Attributes::create([
                    'id'    => $descriptionId,
                    'class' => 'sr-only'
                ]), Text::create($description));

                $this->formElement->getAttributes()->set('aria-describedby', $descriptionId);
            }

            return [
                new Icon('info-circle', $iconAttributes),
                $describedBy
            ];
        }
    }
}
