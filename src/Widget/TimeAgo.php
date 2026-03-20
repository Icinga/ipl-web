<?php

namespace ipl\Web\Widget;

use DateTime;
use Exception;
use ipl\Html\Attributes;

class TimeAgo extends Time
{
    protected $defaultAttributes = ['class' => 'time-ago', 'data-relative-time' => 'ago'];

    /**
     * @param int|float|DateTime|null $time Time as timestamp, DateTime object, or null for current time
     * @param int|float|DateTime|null $compareTime Time to compare with, null for current time
     *
     * @throws Exception
     */
    public function __construct(int|float|DateTime|null $time = null, int|float|DateTime|null $compareTime = null)
    {
        if ($compareTime !== null) {
            $this->compareTime = $this->castToDateTime($compareTime);
        }

        if (! $time instanceof DateTime) {
            $time = $this->castToDateTime($time);
        }

        parent::__construct($time);
    }

    protected function format(): string
    {
        [$time, $type, $interval] = $this->diff($this->compareTime);

        if ($interval->days === 0 && $interval->h === 0) {
            $this->addAttributes(
                Attributes::create(
                    [
                        'data-ago-label' => sprintf(
                            t('%s ago', 'An event that happened the given time interval ago'),
                            '0m 0s'
                        )
                    ]
                )
            );
        }

        return sprintf(
            match ($type) {
                self::RELATIVE => t('%s ago', 'An event that happened the given time interval ago'),
                self::TIME     => t('at %s', 'An event happened at the given time'),
                self::DATE     => t('on %s', 'An event happened on the given date or date and time'),
            },
            $time
        );
    }
}
