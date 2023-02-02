<?php

namespace ipl\Web\FormElement;

use DateTime;
use InvalidArgumentException;
use ipl\Html\FormElement\FieldsetElement;
use ipl\Html\HtmlElement;
use ipl\Scheduler\RRule;
use ipl\Validator\BetweenValidator;
use ipl\Web\FormElement\ScheduleElement\AnnuallyFields;
use ipl\Web\FormElement\ScheduleElement\Common\FieldsProtector;
use ipl\Web\FormElement\ScheduleElement\MonthlyFields;
use ipl\Web\FormElement\ScheduleElement\WeeklyFields;

class ScheduleElement extends FieldsetElement
{
    use FieldsProtector;

    protected $defaultAttributes = ['class' => 'schedule-element'];

    /** @var array A list of allowed frequencies used to configure custom expressions */
    protected $frequencies = [];

    /** @var string Schedule frequency of this element */
    protected $frequency = RRule::DAILY;

    /** @var DateTime */
    protected $start;

    /** @var WeeklyFields Weekly parts of this schedule element */
    protected $weeklyField;

    /** @var MonthlyFields Monthly parts of this schedule element */
    protected $monthlyFields;

    /** @var AnnuallyFields Annually parts of this schedule element */
    protected $annuallyFields;

    protected function init(): void
    {
        $this->start = new DateTime();
        $this->weeklyField = new WeeklyFields('weekly-fields', [
            'default'   => $this->start->format('D'),
            'protector' => function (string $day) {
                return $this->protectId($day);
            },
        ]);

        $this->monthlyFields = new MonthlyFields('monthly-fields', [
            'default'         => $this->start->format('j'),
            'availableFields' => (int) $this->start->format('t'),
            'protector'       => function ($day) {
                return $this->protectId($day);
            }
        ]);

        $this->annuallyFields = new AnnuallyFields('annually-fields', [
            'default'   => $this->start->format('M'),
            'protector' => function ($month) {
                return $this->protectId($month);
            }
        ]);

        $this->frequencies = [
            RRule::DAILY   => $this->translate('Daily'),
            RRule::WEEKLY  => $this->translate('Weekly'),
            RRule::MONTHLY => $this->translate('Monthly'),
            RRule::YEARLY  => $this->translate('Annually')
        ];
    }

    public function setDefaultElementDecorator($decorator)
    {
        parent::setDefaultElementDecorator($decorator);

        $this->weeklyField->setDefaultElementDecorator($this->getDefaultElementDecorator());
        $this->monthlyFields->setDefaultElementDecorator($this->getDefaultElementDecorator());
        $this->annuallyFields->setDefaultElementDecorator($this->getDefaultElementDecorator());
    }

    /**
     * Get the frequency of this element
     *
     * @return string
     */
    public function getFrequency(): string
    {
        return $this->getValue('custom_frequency', $this->frequency);
    }

    /**
     * Set the custom frequency of this cron
     *
     * @param string $frequency
     *
     * @return $this
     */
    public function setFrequency(string $frequency): self
    {
        if (! isset($this->frequencies[$frequency])) {
            throw new InvalidArgumentException(sprintf('Invalid frequency provided: %s', $frequency));
        }

        $this->frequency = $frequency;

        return $this;
    }

    /**
     * Set start time of the parsed expressions
     *
     * @param DateTime $start
     *
     * @return $this
     */
    public function setStart(DateTime $start): self
    {
        $this->start = $start;

        // Forward the start time update to the sub elements as well!
        $this->weeklyField->setDefault($start->format('D'));
        $this->annuallyFields->setDefault($start->format('M'));
        $this->monthlyFields
            ->setDefault((int) $start->format('j'))
            ->setAvailableFields((int) $start->format('t'));

        return $this;
    }

    /**
     * Get the monthly fields of this element
     *
     * Is only used when using multipart updates
     *
     * @return MonthlyFields
     */
    public function getMonthlyFields(): MonthlyFields
    {
        return $this->monthlyFields;
    }

    /**
     * Parse this schedule element and derive a {@see RRule} instance from it
     *
     * @return RRule
     */
    public function getRRule(): RRule
    {
        $repeat = $this->getFrequency();
        $interval = $this->getValue('interval', 1);
        switch ($repeat) {
            case RRule::DAILY:
                if ($interval === '*') {
                    $interval = 1;
                }

                return new RRule("FREQ=DAILY;INTERVAL=$interval");
            case RRule::WEEKLY:
                $byDay = implode(',', $this->weeklyField->getSelectedWeekDays());

                return new RRule("FREQ=WEEKLY;INTERVAL=$interval;BYDAY=$byDay");
            /** @noinspection PhpMissingBreakStatementInspection */
            case RRule::MONTHLY:
                $runsOn = $this->monthlyFields->getValue('runsOn', MonthlyFields::RUNS_EACH);
                if ($runsOn === MonthlyFields::RUNS_EACH) {
                    $byMonth = implode(',', $this->monthlyFields->getSelectedDays());

                    return new RRule("FREQ=MONTHLY;INTERVAL=$interval;BYMONTHDAY=$byMonth");
                }
            // Fall-through to the next switch case
            case RRule::YEARLY:
                $rule = "FREQ=MONTHLY;INTERVAL=$interval;";
                if ($repeat === RRule::YEARLY) {
                    $runsOn = $this->annuallyFields->getValue('runsOnThe', 'n');
                    $month = $this->annuallyFields->getValue('month', (int) $this->start->format('m'));
                    if (is_string($month)) {
                        $datetime = DateTime::createFromFormat('!M', $month);
                        if (! $datetime) {
                            throw new InvalidArgumentException(sprintf('Invalid month provided: %s', $month));
                        }

                        $month = (int) $datetime->format('m');
                    }

                    $rule = "FREQ=YEARLY;INTERVAL=1;BYMONTH=$month;";
                    if ($runsOn === 'n') {
                        return new RRule($rule);
                    }
                }

                $element = $this->monthlyFields;
                if ($repeat === RRule::YEARLY) {
                    $element = $this->annuallyFields;
                }

                $runDay = $element->getValue('day', $element::$everyDay);
                $ordinal = $element->getValue('ordinal', $element::$first);
                $position = $element->getOrdinalAsInteger($ordinal);

                if ($runDay === $element::$everyDay) {
                    $rule .= "BYDAY=MO,TU,WE,TH,FR,SA,SU;BYSETPOS=$position";
                } elseif ($runDay === $element::$everyWeekday) {
                    $rule .= "BYDAY=MO,TU,WE,TH,FR;BYSETPOS=$position";
                } elseif ($runDay === $element::$everyWeekend) {
                    $rule .= "BYDAY=SA,SU;BYSETPOS=$position";
                } else {
                    $rule .= sprintf('BYDAY=%d%s', $position, $runDay);
                }

                return new RRule($rule);
        }
        // Oops!!
    }

    /**
     * Load the given RRule instance into a list of key=>value pairs
     *
     * @param RRule $rule
     *
     * @return array
     */
    public function loadRRule(RRule $rule): array
    {
        $values = [
            'interval'         => $rule->getInterval(),
            'custom_frequency' => $rule->getFrequency()
        ];
        switch ($rule->getFrequency()) {
            case RRule::WEEKLY:
                $values['weekly-fields'] = $this->weeklyField->loadWeekDays($rule->getByDay());

                break;
            case RRule::MONTHLY:
                $values['monthly-fields'] = $this->monthlyFields->loadRRule($rule);

                break;
            case RRule::YEARLY:
                $values['annually-fields'] = $this->annuallyFields->loadRRule($rule);
        }

        return $values;
    }

    protected function assemble()
    {
        $this->addElement('select', 'custom_frequency', [
            'required'    => false,
            'class'       => 'autosubmit',
            'value'       => $this->getFrequency(),
            'options'     => $this->frequencies,
            'label'       => $this->translate('Custom Frequency'),
            'description' => $this->translate('Specifies how often this job run should be recurring')
        ]);

        switch ($this->getFrequency()) {
            case RRule::DAILY:
                $this->assembleCommonElements();

                break;
            case RRule::WEEKLY:
                $this->assembleCommonElements();
                $this->addElement($this->weeklyField);

                break;
            case RRule::MONTHLY:
                $this->assembleCommonElements();
                $this
                    ->registerElement($this->monthlyFields)
                    ->addHtml($this->monthlyFields);

                break;
            case RRule::YEARLY:
                $this
                    ->registerElement($this->annuallyFields)
                    ->addHtml($this->annuallyFields);
        }
    }

    /**
     * Assemble common parts for all the frequencies
     */
    private function assembleCommonElements(): void
    {
        $repeat = $this->getFrequency();
        if ($repeat === RRule::WEEKLY) {
            $text = $this->translate('week(s) on');
            $max = 53;
        } elseif ($repeat === RRule::MONTHLY) {
            $text = $this->translate('month(s)');
            $max = 12;
        } else {
            $text = $this->translate('day(s)');
            $max = 31;
        }

        $options = ['min' => 1, 'max' => $max];
        $this->addElement('number', 'interval', [
            'class'      => 'autosubmit',
            'value'      => 1,
            'min'        => 1,
            'max'        => $max,
            'validators' => [new BetweenValidator($options)]
        ]);

        $numberSpecifier = HtmlElement::create('div', ['class' => 'number-specifier']);
        $element = $this->getElement('interval');
        $element->prependWrapper($numberSpecifier);

        $numberSpecifier->prependHtml(HtmlElement::create('span', null, $this->translate('Every')));
        $numberSpecifier->addHtml($element);
        $numberSpecifier->addHtml(HtmlElement::create('span', null, $text));
    }
}
