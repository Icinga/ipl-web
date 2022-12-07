<?php

namespace ipl\Web\FormElement;

use ipl\Html\Attributes;
use ipl\Html\FormElement\FieldsetElement;
use ipl\Html\HtmlElement;
use ipl\Validator\DeferredInArrayValidator;
use ipl\Web\Common\FieldsProtector;
use ipl\Web\Common\ScheduleFieldsUtils;

class MonthlyFields extends FieldsetElement
{
    use ScheduleFieldsUtils;
    use FieldsProtector;

    /** @var string Used as radio option to run each selected days/months */
    public const RUNS_EACH = 'each';

    /** @var string Used as radio option to build complex job schedules */
    public const RUNS_ONTHE = 'onthe';

    /** @var int Number of days in a week */
    public const WEEK_DAYS = 7;

    /** @var int Day of the month to preselect by default */
    protected $default = 1;

    /** @var int Available fields to be rendered */
    protected $availableFields;

    protected function init(): void
    {
        parent::init();
        $this->initUtils();

        $this->availableFields = (int) date('t');
    }

    /**
     * Set the available fields/days of the month to be rendered
     *
     * @param int $fields
     *
     * @return $this
     */
    public function setAvailableFields(int $fields): self
    {
        $this->availableFields = $fields;

        return $this;
    }

    /**
     * Set the default field/day to be selected by default
     *
     * @param int $default
     *
     * @return $this
     */
    public function setDefault(int $default): self
    {
        $this->default = $default;

        return $this;
    }

    /**
     * Get all the selected weekdays
     *
     * @return array
     */
    public function getSelectedDays(): array
    {
        $selectedDays = [];
        foreach (range(1, $this->availableFields) as $day) {
            if ($this->getValue("day$day", 'n') === 'y') {
                $selectedDays[] = $day;
            }
        }

        if (empty($selectedDays)) {
            $selectedDays[] = $this->default;
        }

        return $selectedDays;
    }

    protected function assemble()
    {
        $this->getAttributes()->set('id', $this->protectId('monthly-fields'));

        $runsOn = $this->getPopulatedValue('runsOn', static::RUNS_EACH);
        $this->addElement('radio', 'runsOn', [
            'required' => true,
            'class'    => 'autosubmit',
            'value'    => $runsOn,
            'options'  => [static::RUNS_EACH => $this->translate('Each')],
        ]);

        $listItems = HtmlElement::create('ul', ['class' => ['schedule-element-fields', 'multiple-fields']]);
        if ($runsOn === static::RUNS_ONTHE) {
            $listItems->getAttributes()->add('class', 'disabled');
        }

        $foundCheckedDay = false;
        foreach (range(1, $this->availableFields) as $day) {
            $checkbox = $this->createElement('checkbox', "day$day", [
                'class' => 'sr-only autosubmit',
                'value' => $this->getPopulatedValue("day$day", 'n')
            ]);
            $this->registerElement($checkbox);

            $foundCheckedDay = $foundCheckedDay || $checkbox->isChecked();
            $htmlId = $this->protectId("day$day");
            $checkbox->getAttributes()->set('id', $htmlId);

            $listItem = HtmlElement::create('li');
            $checkbox->prependWrapper($listItem);

            $listItem->addHtml($checkbox, HtmlElement::create('label', ['for' => $htmlId], $day));
            $listItems->addHtml($checkbox);
        }

        if (! $foundCheckedDay) {
            $this->getElement("day{$this->default}")->setChecked(true);
        }

        $monthlyWrapper = HtmlElement::create('div', ['class' => 'monthly']);
        $runsEach = $this->getElement('runsOn');
        $runsEach->prependWrapper($monthlyWrapper);
        $monthlyWrapper->addHtml($runsEach, $listItems);

        $this->addElement('radio', 'runsOn', [
            'required' => $runsOn !== static::RUNS_EACH,
            'class'    => 'autosubmit',
            'options'  => [static::RUNS_ONTHE => $this->translate('On the')]
        ]);

        $runsOnThe = $this->getElement('runsOn');
        $runsOnValidators = $runsOnThe->getValidators();
        $runsOnValidators
            ->clearValidators()
            ->add(
                new DeferredInArrayValidator(function (): array {
                    return [static::RUNS_EACH, static::RUNS_ONTHE];
                }),
                true
            );

        $ordinalWrapper = HtmlElement::create('div', ['class' => 'ordinal']);
        $runsOnThe->prependWrapper($ordinalWrapper);
        $ordinalWrapper->addHtml($runsOnThe);

        $enumerations = $this->createOrdinalElement();
        $enumerations->getAttributes()->set('disabled', $runsOn === static::RUNS_EACH);
        $this->registerElement($enumerations);

        $selectableDays = $this->createOrdinalSelectableDays();
        $selectableDays->getAttributes()->set('disabled', $runsOn === static::RUNS_EACH);
        $this->registerElement($selectableDays);

        $ordinalWrapper->addHtml($enumerations, $selectableDays);
    }

    protected function registerAttributeCallbacks(Attributes $attributes)
    {
        parent::registerAttributeCallbacks($attributes);

        $attributes
            ->registerAttributeCallback('default', null, [$this, 'setDefault'])
            ->registerAttributeCallback('availableFields', null, [$this, 'setAvailableFields'])
            ->registerAttributeCallback('protector', null, [$this, 'setIdProtector']);
    }
}