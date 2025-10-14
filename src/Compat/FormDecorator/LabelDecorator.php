<?php

namespace ipl\Web\Compat\FormDecorator;

use ipl\Html\Contract\DecorationResult;
use ipl\Html\Contract\Form;
use ipl\Html\Contract\FormElement;
use ipl\Html\Contract\FormDecoration;
use ipl\Html\HtmlString;
use ipl\Html\FormDecoration\LabelDecorator as IplHtmlLabelDecorator;
use ipl\Html\ValidHtml;
use ipl\Html\Attributes;
use ipl\Html\HtmlElement;


class LabelDecorator extends IplHtmlLabelDecorator implements FormDecoration
{
    /**
     * Decorates the label of the form element and adds a tooltip if it is required
     */
    protected function getElementLabel(FormElement $formElement): ?ValidHtml
    {
        $elementAttrs = $formElement->getAttributes();
        $label = $formElement->getLabel();

        if (! $elementAttrs->has('id')) {
            $elementAttrs->set('id', uniqid('form-element-'));
        }

        $result = new HtmlElement(
            'label',
            null,
            HtmlString::create($label)
        );
        if ($formElement->isRequired()) {
            $requiredHint = new HtmlElement(
                'span',
                Attributes::create([
                    'id' => uniqid('required-hint-'),
                    'class' => 'form-info',
                    'aria-required' => true,
                    'title' => 'Required'
                ]),
                HtmlString::create(" *")
            );
            $formElement->setAttribute('has-required-hint', true);
            $result->addHtml($requiredHint);
        }
        return $result;
    }
    /**
     * appends an explanation of the asterisk for required fields if at least one such field exists
     */
    public function decorateForm(DecorationResult $result, Form $form): void
    {
        // if the explanation already exists it won't be added again
        if (! $form->hasAttribute('required-explanation-added')) {
            $requiredHintExists = false;
            foreach ($form->getElements() as $element) {
                if ($element->getAttributes()->has('has-required-hint')) {
                    $requiredHintExists = true;
                    break;
                }
            }
            if ($requiredHintExists) {
                $requiredExplanation = new HtmlElement(
                    'ul',
                    Attributes::create(['class' => 'form-info',]),
                    new HtmlElement(
                        'li',
                        null,
                        HtmlString::create('* Required')
                    )
                );
                $form->setAttribute('required-explanation-added', true);
                $result->append($requiredExplanation);
            }
        }
    }
}
