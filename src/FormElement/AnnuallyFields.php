<?php

namespace ipl\Web\FormElement;

use InvalidArgumentException;
use ipl\Html\Attributes;
use ipl\Html\Form;
use ipl\Html\FormElement\FieldsetElement;
use ipl\Html\HtmlElement;
use ipl\Web\Common\FieldsProtector;
use ipl\Web\Common\ScheduleFieldsUtils;

class AnnuallyFields extends FieldsetElement
{
    use ScheduleFieldsUtils;
    use FieldsProtector;

    /** @var bool Whether the form is auto submitted */
    protected $isAutoSubmitted = true;

    /** @var array A list of valid months */
    protected $months = [];

    /** @var string A month to preselect by default */
    protected $default = 'JAN';

    protected function init(): void
    {
        parent::init();
        $this->initUtils();

        $this->months = [
            'JAN' => $this->translate('Jan'),
            'FEB' => $this->translate('Feb'),
            'MAR' => $this->translate('Mar'),
            'APR' => $this->translate('Apr'),
            'MAY' => $this->translate('May'),
            'JUN' => $this->translate('Jun'),
            'JUL' => $this->translate('Jul'),
            'AUG' => $this->translate('Aug'),
            'SEP' => $this->translate('Sep'),
            'OCT' => $this->translate('Oct'),
            'NOV' => $this->translate('Nov'),
            'DEC' => $this->translate('Dec')
        ];
    }

    public function onRegistered(Form $form)
    {
        $form->on(Form::ON_SENT, function ($form) {
            $this->isAutoSubmitted = ! $form->hasBeenSubmitted();
        });
    }

    /**
     * Set the default month to be activated
     *
     * @param string $default
     *
     * @return $this
     */
    public function setDefault(string $default): self
    {
        // Attributes are registered far before the initialization of this element!
        if (! empty($this->months) && ! isset($this->months[strtoupper($this->default)])) {
            throw new InvalidArgumentException(sprintf('Invalid month provided: %s', $default));
        }

        $this->default = strtoupper($default);

        return $this;
    }

    protected function assemble()
    {
        $this->getAttributes()->set('id', $this->protectId('annually-fields'));

        $fieldsSelector = new ScheduleFieldsRadio('month', [
            'class'     => 'autosubmit sr-only',
            'value'     => $this->default,
            'options'   => $this->months,
            'protector' => function ($value) {
                return $this->protectId($value);
            }
        ]);
        $this->registerElement($fieldsSelector);

        $runsOnThe = $this->getPopulatedValue('runsOnThe', 'n');
        $this->addElement('checkbox', 'runsOnThe', [
            'class' => 'autosubmit',
            'value' => $runsOnThe
        ]);

        $checkboxControls = HtmlElement::create('div', ['class' => 'toggle-slider-controls']);
        $checkbox = $this->getElement('runsOnThe');
        $checkbox->prependWrapper($checkboxControls);
        $checkboxControls->addHtml($checkbox, HtmlElement::create('span', null, $this->translate('On the')));

        $annuallyWrapper = HtmlElement::create('div', ['class' => 'annually']);
        $checkboxControls->prependWrapper($annuallyWrapper);
        $annuallyWrapper->addHtml($fieldsSelector);

        if ($runsOnThe === 'n' && $this->isAutoSubmitted) {
            $this->clearPopulatedValue('ordinal');
            $this->clearPopulatedValue('day');
        }

        $enumerations = $this->createOrdinalElement();
        $enumerations->getAttributes()->set('disabled', $runsOnThe === 'n');
        $this->registerElement($enumerations);

        $selectableDays = $this->createOrdinalSelectableDays();
        $selectableDays->getAttributes()->set('disabled', $runsOnThe === 'n');
        $this->registerElement($selectableDays);

        $ordinalWrapper = HtmlElement::create('div', ['class' => ['ordinal', 'annually']]);
        $this
            ->decorate($enumerations)
            ->addHtml($enumerations);

        $enumerations->prependWrapper($ordinalWrapper);
        $ordinalWrapper->addHtml($enumerations, $selectableDays);
    }

    protected function registerAttributeCallbacks(Attributes $attributes)
    {
        parent::registerAttributeCallbacks($attributes);

        $attributes
            ->registerAttributeCallback('default', null, [$this, 'setDefault'])
            ->registerAttributeCallback('protector', null, [$this, 'setIdProtector']);
    }
}