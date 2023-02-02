<?php

namespace ipl\Web\FormElement\ScheduleElement;

use InvalidArgumentException;
use ipl\Html\Attributes;
use ipl\Html\FormElement\FieldsetElement;
use ipl\Html\HtmlElement;
use ipl\Web\FormElement\ScheduleElement\Common\FieldsProtector;

class WeeklyFields extends FieldsetElement
{
    use FieldsProtector;

    /** @var array A list of valid week days */
    protected $weekdays = [];

    /** @var string A valid weekday to be selected by default */
    protected $default = 'MO';

    protected function init(): void
    {
        parent::init();

        $this->weekdays = [
            'MO' => $this->translate('Mon'),
            'TU' => $this->translate('Tue'),
            'WE' => $this->translate('Wed'),
            'TH' => $this->translate('Thu'),
            'FR' => $this->translate('Fri'),
            'SA' => $this->translate('Sat'),
            'SU' => $this->translate('Sun')
        ];
    }

    /**
     * Set the default weekday to be preselected
     *
     * @param string $default
     *
     * @return $this
     */
    public function setDefault(string $default): self
    {
        $weekday = strlen($default) > 2 ? substr($default, 0, -1) : $default;
        // Attributes are registered far before the initialization of this element!
        if (! empty($this->weekdays) && ! isset($this->weekdays[strtoupper($weekday)])) {
            throw new InvalidArgumentException(sprintf('Invalid weekday provided: %s', $default));
        }

        $this->default = strtoupper($weekday);

        return $this;
    }

    /**
     * Get all the selected weekdays
     *
     * @return array
     */
    public function getSelectedWeekDays(): array
    {
        $selectedDays = [];
        foreach ($this->weekdays as $day => $_) {
            if ($this->getValue($day, 'n') === 'y') {
                $selectedDays[] = $day;
            }
        }

        if (empty($selectedDays)) {
            $selectedDays[] = $this->default;
        }

        return $selectedDays;
    }

    /**
     * Transform the given weekdays into key=>value array that can be populated
     *
     * @param array $days
     *
     * @return array
     */
    public function loadWeekDays(array $days): array
    {
        $values = [];
        foreach ($days as $day) {
            $weekDays = strtoupper($day);
            if (! isset($this->weekdays[$weekDays])) {
                throw new InvalidArgumentException(sprintf('Invalid weekday provided: %s', $day));
            }

            $values[$weekDays] = 'y';
        }

        return $values;
    }

    protected function assemble()
    {
        $this->getAttributes()->set('id', $this->protectId('weekly-fields'));

        $fieldsWrapper = HtmlElement::create('div', ['class' => 'weekly']);
        $listItems = HtmlElement::create('ul', ['class' => ['schedule-element-fields', 'multiple-fields']]);

        $foundCheckedDay = false;
        foreach ($this->weekdays as $day => $value) {
            $checkbox = $this->createElement('checkbox', $day, [
                'class' => 'sr-only autosubmit',
                'value' => $this->getPopulatedValue($day, 'n')
            ]);
            $this->registerElement($checkbox);

            $foundCheckedDay = $foundCheckedDay || $checkbox->isChecked();
            $htmlId = $this->protectId("weekday-$day");
            $checkbox->getAttributes()->set('id', $htmlId);

            $listItem = HtmlElement::create('li');
            $checkbox->prependWrapper($listItem);

            $listItem->addHtml($checkbox, HtmlElement::create('label', ['for' => $htmlId], $value));
            $listItems->addHtml($checkbox);
        }

        if (! $foundCheckedDay) {
            $this->getElement($this->default)->setChecked(true);
        }

        $listItems->prependWrapper($fieldsWrapper);
        $this->addHtml($listItems);
    }

    protected function registerAttributeCallbacks(Attributes $attributes)
    {
        parent::registerAttributeCallbacks($attributes);

        $attributes
            ->registerAttributeCallback('default', null, [$this, 'setDefault'])
            ->registerAttributeCallback('protector', null, [$this, 'setIdProtector']);
    }
}
