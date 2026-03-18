<?php

namespace ipl\Web\Widget;

use DateTime;
use Exception;
use ipl\Html\Attributes;

class TimeSince extends Time
{
    protected $defaultAttributes = ['class' => 'time-since'];

    /**
     * @param int|float|DateTime|null $time Time as timestamp, DateTime object, or null for current time
     *
     * @throws Exception
     */
    public function __construct(int|float|DateTime|null $time = null)
    {
        if (! $time instanceof DateTime) {
            $time = $this->castToDateTime($time);
        }

        parent::__construct($time);
    }

    protected function format(): string
    {
        [$time, $type] = $this->diff($this->dateTime);

        $this->addAttributes(Attributes::create(['data-relative-time' => 'since']));

        return sprintf(
            match ($type) {
                self::RELATIVE         => t('for %s', 'A status is lasting for the given time interval'),
                self::TIME, self::DATE => t(
                    'since %s',
                    'A status is lasting since the given time, date or date and time'
                ),
            },
            $time
        );
    }
}
