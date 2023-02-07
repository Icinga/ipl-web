<?php

namespace ipl\Web\FormElement;

use DateTime;
use InvalidArgumentException;
use ipl\Html\FormElement\FieldsetElement;
use ipl\Html\HtmlElement;
use ipl\Scheduler\Contract\Frequency;
use ipl\Scheduler\Cron;
use ipl\Scheduler\OneOff;
use ipl\Scheduler\RRule;
use ipl\Validator\BetweenValidator;
use ipl\Validator\CallbackValidator;
use ipl\Web\FormElement\ScheduleElement\AnnuallyFields;
use ipl\Web\FormElement\ScheduleElement\Common\FieldsProtector;
use ipl\Web\FormElement\ScheduleElement\MonthlyFields;
use ipl\Web\FormElement\ScheduleElement\Recurrence;
use ipl\Web\FormElement\ScheduleElement\WeeklyFields;
use LogicException;
use Psr\Http\Message\RequestInterface;

class ScheduleElement extends FieldsetElement
{
    use FieldsProtector;

    /** @var string Plain cron expressions */
    protected const CRON_EXPR = 'cron_expr';

    /** @var string Configure the individual expression parts manually */
    protected const CUSTOM_EXPR = 'custom';

    /** @var string Used to run a one-off task */
    protected const NO_REPEAT = 'none';

    protected $defaultAttributes = ['class' => 'schedule-element'];

    /** @var array A list of allowed frequencies used to configure custom expressions */
    protected $customFrequencies = [];

    /** @var array */
    protected $advanced = [];

    /** @var array */
    protected $regulars = [];

    /** @var string Schedule frequency of this element */
    protected $frequency = self::NO_REPEAT;

    /** @var string */
    protected $customFrequency = RRule::DAILY;

    /** @var DateTime */
    protected $start;

    /** @var DateTime */
    protected $end;

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


        $this->regulars = [
            RRule::MINUTELY  => $this->translate('Minutely'),
            RRule::HOURLY    => $this->translate('Hourly'),
            RRule::DAILY     => $this->translate('Daily'),
            RRule::WEEKLY    => $this->translate('Weekly'),
            RRule::MONTHLY   => $this->translate('Monthly'),
            RRule::QUARTERLY => $this->translate('Quarterly'),
            RRule::YEARLY    => $this->translate('Annually'),
        ];

        $this->customFrequencies = array_slice($this->regulars, 2);
        unset($this->customFrequencies[RRule::QUARTERLY]);

        $this->advanced = [
            static::CUSTOM_EXPR => $this->translate('Custom…'),
            static::CRON_EXPR   => $this->translate('Cron Expression…')
        ];
    }

    /**
     * Get whether this element is rendering a cron expression
     *
     * @return bool
     */
    public function hasCronExpression(): bool
    {
        return $this->getFrequency() === static::CRON_EXPR;
    }

    /**
     * Get the frequency of this element
     *
     * @return string
     */
    public function getFrequency(): string
    {
        return $this->getValue('frequency', $this->frequency);
    }

    /**
     * Set the custom frequency of this schedule element
     *
     * @param string $frequency
     *
     * @return $this
     */
    public function setFrequency(string $frequency): self
    {
        if (
            $frequency !== static::NO_REPEAT
            && ! isset($this->regulars[$frequency])
            && ! isset($this->advanced[$frequency])
        ) {
            throw new InvalidArgumentException(sprintf('Invalid frequency provided: %s', $frequency));
        }

        $this->frequency = $frequency;

        return $this;
    }

    /**
     * Get custom frequency of this element
     *
     * @return ?string
     */
    public function getCustomFrequency(): ?string
    {
        return $this->getValue('custom_frequency', $this->customFrequency);
    }

    /**
     * Set custom frequency of this element
     *
     * @param string $frequency
     *
     * @return $this
     */
    public function setCustomFrequency(string $frequency): self
    {
        if (! isset($this->customFrequencies[$frequency])) {
            throw new InvalidArgumentException(sprintf('Invalid custom frequency provided: %s', $frequency));
        }

        $this->customFrequency = $frequency;

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
     * Set the end time of this schedule element
     *
     * @param DateTime $end
     *
     * @return $this
     */
    public function setEnd(DateTime $end): self
    {
        $this->end = $end;

        return $this;
    }

    /**
     * Parse this schedule element and derive a {@see Frequency} instance from it
     *
     * @return Frequency
     */
    public function getRRule(): Frequency
    {
        $frequency = $this->getFrequency();
        switch ($frequency) {
            case static::NO_REPEAT:
                return new OneOff($this->getValue('start'));
            case static::CRON_EXPR:
                return new Cron($this->getValue('cron-expression'));
            case RRule::MINUTELY:
            case RRule::HOURLY:
            case RRule::DAILY:
            case RRule::WEEKLY:
            case RRule::MONTHLY:
            case RRule::QUARTERLY:
            case RRule::YEARLY:
                return RRule::fromFrequency($frequency);
            default: // static::CUSTOM_EXPR
                $interval = $this->getValue('interval', 1);
                $customFrequency = $this->getValue('custom_frequency', RRule::DAILY);
                switch ($customFrequency) {
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
                        if ($customFrequency === RRule::YEARLY) {
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
                        if ($customFrequency === RRule::YEARLY) {
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
                    default:
                        throw new LogicException(sprintf('Custom frequency %s is not supported!', $customFrequency));
                }
        }
    }

    /**
     * Load the given frequency instance into a list of key=>value pairs
     *
     * @param Frequency $frequency
     *
     * @return array
     */
    public function loadRRule(Frequency $frequency): array
    {
        $values = [
            'frequency'        => $this->getFrequency(),
            'custom_frequency' => $this->getCustomFrequency(),
            'start'            => $this->start,
            'use-end-time'     => $this->end instanceof DateTime,
            'end'              => $this->end
        ];

        if ($frequency instanceof Cron) {
            $values['cron-expression'] = implode(' ', $frequency->getParts());
        } elseif ($frequency instanceof RRule) {
            $values['interval'] = $frequency->getInterval();
            switch ($frequency->getFrequency()) {
                case RRule::WEEKLY:
                    $values['weekly-fields'] = $this->weeklyField->loadWeekDays($frequency->getByDay());

                    break;
                case RRule::MONTHLY:
                    $values['monthly-fields'] = $this->monthlyFields->loadRRule($frequency);

                    break;
                case RRule::YEARLY:
                    $values['annually-fields'] = $this->annuallyFields->loadRRule($frequency);
            }
        }

        return $values;
    }

    protected function assemble()
    {
        $start = $this->getPopulatedValue('start', $this->start);
        if (! $start instanceof DateTime) {
            $start = new DateTime($start);
        }
        $this->setStart($start);

        $this->addElement('localDateTime', 'start', [
            'class'       => 'autosubmit',
            'required'    => true,
            'label'       => $this->translate('Start'),
            'value'       => $start,
            'description' => $this->translate('Start time of this schedule')
        ]);

        $this->addElement('checkbox', 'use-end-time', [
            'required' => false,
            'class'    => 'autosubmit',
            'value'    => $this->getPopulatedValue('use-end-time', 'n'),
            'label'    => $this->translate('Use End Time')
        ]);

        if ($this->getPopulatedValue('use-end-time', 'n') === 'y') {
            $end = $this->getPopulatedValue('end', $this->end ?: new DateTime());
            if (! $end instanceof DateTime) {
                $end = new DateTime($end);
            }

            $this->addElement('localDateTime', 'end', [
                'class'       => 'autosubmit',
                'required'    => true,
                'value'       => $end,
                'label'       => $this->translate('End'),
                'description' => $this->translate('End time of this schedule')
            ]);
        }

        $this->addElement('select', 'frequency', [
            'required'    => false,
            'class'       => 'autosubmit',
            'label'       => $this->translate('Frequency'),
            'description' => $this->translate('Specifies how often this job run should be recurring'),
            'options'     => [
                static::NO_REPEAT            => $this->translate('None'),
                $this->translate('Regular')  => $this->regulars,
                $this->translate('Advanced') => $this->advanced
            ],
        ]);

        if ($this->getFrequency() === static::CUSTOM_EXPR) {
            $this->addElement('select', 'custom_frequency', [
                'required'    => false,
                'class'       => 'autosubmit',
                'value'       => $this->getValue('custom_frequency'),
                'options'     => $this->customFrequencies,
                'label'       => $this->translate('Custom Frequency'),
                'description' => $this->translate('Specifies how often this job run should be recurring')
            ]);

            switch ($this->getValue('custom_frequency', RRule::DAILY)) {
                case RRule::DAILY:
                    $this->assembleCommonElements();

                    break;
                case RRule::WEEKLY:
                    $this->assembleCommonElements();
                    $this->addElement($this->weeklyField);

                    break;
                case RRule::MONTHLY:
                    $this->assembleCommonElements();
                    $this->addElement($this->monthlyFields);

                    break;
                case RRule::YEARLY:
                    $this->addElement($this->annuallyFields);
            }
        } elseif ($this->hasCronExpression()) {
            $this->addElement('text', 'cron-expression', [
                'label'       => $this->translate('Cron Expression'),
                'description' => $this->translate('Job cron Schedule'),
                'validators' => [
                    new CallbackValidator(function ($value) {
                        if ($value && ! Cron::isValid($value)) {
                            $this
                                ->getElement('cron-expression')
                                ->addMessage($this->translate('Invalid CRON expression'));

                            return false;
                        }

                        return true;
                    })
                ]
            ]);
        }

        if ($this->getFrequency() !== static::NO_REPEAT && ! $this->hasCronExpression()) {
            $this->addElement(
                new Recurrence('schedule-recurrences', [
                    'id'        => $this->protectId('schedule-recurrences'),
                    'label'     => $this->translate('Next occurrences'),
                    'valid'     => function (): bool {
                        return $this->isValid();
                    },
                    'frequency' => function (): Frequency {
                        if ($this->getFrequency() === static::CUSTOM_EXPR) {
                            $rule = $this->getRRule();
                        } else {
                            $rule = RRule::fromFrequency($this->getFrequency());
                        }

                        $start = $this->getPopulatedValue('start', new DateTime());
                        if (! $start instanceof DateTime) {
                            $start = new DateTime($start);
                        }

                        $rule->startAt($start);

                        if ($this->getPopulatedValue('use-end-time') === 'y') {
                            $end = $this->getPopulatedValue('end', new DateTime());
                            if (! $end instanceof DateTime) {
                                $end = new DateTime($end);
                            }

                            $rule->endAt($end);
                        }

                        return $rule;
                    }
                ])
            );
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

    /**
     * Get prepared multipart updates
     *
     * @param RequestInterface $request
     *
     * @return array
     */
    public function prepareMultipartUpdate(RequestInterface $request): array
    {
        $autoSubmittedBy = $request->getHeader('X-Icinga-AutoSubmittedBy');
        $pattern = '/^schedule-element\[(weekly-fields|monthly-fields|annually-fields)]'
            . '\[(ordinal|interval|month|day(\d+)?|[A-Z]{2})]$/';

        $partUpdates = [];
        if (
            $autoSubmittedBy
            && ! $this->hasCronExpression()
            && (
                preg_match('/^schedule-element\[(start|end)]$/', $autoSubmittedBy[0], $matches)
                || preg_match($pattern, $autoSubmittedBy[0])
            )
        ) {
            $partUpdates[] = $this->getElement('schedule-recurrences');
            if (
                $this->getFrequency() === static::CUSTOM_EXPR
                && $this->getCustomFrequency() === RRule::MONTHLY
                && isset($matches[1])
                && $matches[1] === 'start'
            ) {
                // To update the available fields/days based on the provided start time
                $partUpdates[] = $this->monthlyFields;
            }
        }

        return $partUpdates;
    }
}
