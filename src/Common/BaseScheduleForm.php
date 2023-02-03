<?php

namespace ipl\Web\Common;

use DateTime;
use ipl\Scheduler\Contract\Frequency;
use ipl\Scheduler\RRule;
use ipl\Web\Compat\CompatForm;
use ipl\Web\FormElement\ScheduleElement;
use ipl\Web\FormElement\ScheduleElement\Recurrence;

abstract class BaseScheduleForm extends CompatForm
{
    /** @var string Plain cron expressions */
    public const CRON_EXPR = 'cron_expr';

    /** @var string Configure the individual expression parts manually */
    public const CUSTOM_EXPR = 'custom';

    /** @var string Used to run a one-off task */
    public const NO_REPEAT = 'none';

    /** @var ScheduleElement */
    protected $scheduleElement;

    /** @var array */
    protected $advanced = [];

    /** @var array */
    protected $regulars = [];

    /** @var array */
    protected $partUpdates = [];

    public function __construct()
    {
        $this->scheduleElement = new ScheduleElement('schedule-element');
        if ($this->hasDefaultElementDecorator()) {
            $this->scheduleElement->setDefaultElementDecorator($this->getDefaultElementDecorator());
        }

        $this->advanced = [
            static::CUSTOM_EXPR => $this->translate('Custom…'),
            static::CRON_EXPR   => $this->translate('Cron Expression…')
        ];

        $this->regulars = [
            RRule::MINUTELY  => $this->translate('Minutely'),
            RRule::HOURLY    => $this->translate('Hourly'),
            RRule::DAILY     => $this->translate('Daily'),
            RRule::WEEKLY    => $this->translate('Weekly'),
            RRule::MONTHLY   => $this->translate('Monthly'),
            RRule::QUARTERLY => $this->translate('Quarterly'),
            RRule::YEARLY    => $this->translate('Annually'),
        ];

        $this->init();
    }

    /**
     * Get the underlying schedule form element
     *
     * @return ScheduleElement
     */
    public function getScheduleElement(): ScheduleElement
    {
        return $this->scheduleElement;
    }

    /**
     * Get multipart updates
     *
     * @return array
     */
    public function getPartUpdates(): array
    {
        return $this->partUpdates;
    }

    /**
     * Initialize this form after it's construction
     */
    protected function init(): void
    {
    }

    /**
     * Assemble the common parts of this schedule form
     */
    protected function assembleCommonParts(): void
    {
        $start = $this->getPopulatedValue('start', new DateTime());
        if (! $start instanceof DateTime) {
            $start = new DateTime($start);
        }

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
            $end = $this->getPopulatedValue('end', new DateTime());
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
            'options'     => [
                static::NO_REPEAT => $this->translate('None'),
                'Regular'         => $this->regulars,
                'Advanced'        => $this->advanced
            ],
            'label'       => $this->translate('Frequency'),
            'description' => $this->translate('Specifies how often this job run should be recurring'),
        ]);

        $repeat = $this->getPopulatedValue('frequency', static::NO_REPEAT);
        if ($repeat === static::CUSTOM_EXPR) {
            $this->scheduleElement->setStart($start);

            $this->registerElement($this->scheduleElement);
            $this->addHtml($this->scheduleElement);
        }
    }

    /**
     * Assemble the schedule recurrence widget
     */
    protected function assembleScheduleRecurrence(): void
    {
        $repeat = $this->getPopulatedValue('frequency', static::NO_REPEAT);
        if ($repeat !== static::CRON_EXPR && $repeat !== static::NO_REPEAT) {
            $this->addElement(
                new Recurrence('schedule-recurrences', [
                    'id'        => $this->scheduleElement->protectId('schedule-recurrences'),
                    'label'     => $this->translate('Next occurrences'),
                    'valid'     => function (): bool {
                        return $this->scheduleElement->isValid();
                    },
                    'frequency' => function () use ($repeat): Frequency {
                        if ($repeat === static::CUSTOM_EXPR) {
                            $rule = $this->scheduleElement->getRRule();
                        } else {
                            $repeat = strtoupper($repeat);
                            if (substr($repeat, 0, 1) === '@') {
                                $repeat = substr($repeat, 1);
                            }

                            $rule = RRule::fromFrequency($repeat);
                        }

                        $start = $this->getPopulatedValue('start', new DateTime());
                        if (! $start instanceof DateTime) {
                            $start = new DateTime($start);
                        }

                        // Update the schedule element start time as well!!
                        $this->scheduleElement->setStart($start);
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

            $autoSubmittedBy = $this->getRequest()->getHeader('X-Icinga-AutoSubmittedBy');
            $pattern = '/^schedule-element\[(weekly-fields|monthly-fields|annually-fields)]'
                . '\[(ordinal|interval|month|day(\d+)?|[A-Z]{2})]$/';
            if (
                $autoSubmittedBy
                && (
                    $autoSubmittedBy[0] === 'start'
                    || $autoSubmittedBy[0] === 'end'
                    || preg_match($pattern, $autoSubmittedBy[0])
                )
            ) {
                $this->partUpdates[] = $this->getElement('schedule-recurrences');
                if ($repeat === RRule::MONTHLY && $autoSubmittedBy[0] === 'start') {
                    // To update the available fields/days based on the provided start time
                    $this->partUpdates[] = $this->scheduleElement->getMonthlyFields();
                }
            }
        }
    }
}
