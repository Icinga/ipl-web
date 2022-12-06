<?php

namespace ipl\Web\FormElement;

use ipl\Html\Attributes;
use ipl\Html\FormElement\InputElement;
use ipl\Html\FormElement\RadioElement;
use ipl\Html\HtmlElement;
use ipl\Web\Common\FieldsProtector;

class ScheduleFieldsRadio extends RadioElement
{
    use FieldsProtector;

    /** @var bool Whether to disable the "on the" radio options */
    protected $disable;

    /**
     * En/Disable the "on the" radio options of this element
     *
     * @param bool $value
     *
     * @return $this
     */
    public function disable(bool $value): self
    {
        $this->disable = $value;

        return $this;
    }

    protected function assemble()
    {
        $listItems = HtmlElement::create('ul', ['class' => ['schedule-element-fields', 'single-fields']]);
        foreach ($this->options as $option) {
            $radio = (new InputElement($this->getValueOfNameAttribute()))
                ->setValue($option->getValue())
                ->setType($this->type);

            $radio->setAttributes(clone $this->getAttributes());

            $htmlId = $this->protectId($option->getValue());
            $radio->getAttributes()
                ->registerAttributeCallback('id', function () use ($htmlId) {
                    return $htmlId;
                })
                ->registerAttributeCallback('checked', function () use ($option) {
                    return (string) $this->getValue() === (string) $option->getValue();
                })
                ->registerAttributeCallback('required', function () {
                    return $this->getRequiredAttribute();
                })
                ->registerAttributeCallback('disabled', function () use ($option) {
                    return $option->isDisabled();
                })
                ->registerAttributeCallback('class', function () use ($option) {
                    return Attributes::create(['class', $option->getLabelCssClass()])->get('class');
                });

            $listItem = HtmlElement::create('li');
            $radio->prependWrapper($listItem);

            $listItem->addHtml($radio, HtmlElement::create('label', ['for' => $htmlId], $option->getLabel()));
            $listItems->addHtml($radio);
        }

        if ($this->disable) {
            $listItems->getAttributes()->add('class', 'disabled');
        }

        $this->addHtml($listItems);
    }

    protected function registerAttributeCallbacks(Attributes $attributes)
    {
        parent::registerAttributeCallbacks($attributes);

        $attributes
            ->registerAttributeCallback('protector', null, [$this, 'setIdProtector']);
    }
}
