<?php

namespace ipl\Web\Widget;

use DateInterval;
use ipl\Html\Attributes;

class TimeUntil extends Time
{
    protected $defaultAttributes = ['class' => 'time-until'];

    protected function assembleSpecific(): void
    {
        $this->addAttributes(
            Attributes::create(
                [
                    'datetime'           => $this->dateTime,
                    'data-ago-label'     => TimeAgo::getFormattedFromGiven(),
                    'data-relative-time' => 'until'
                ]
            )
        );

        $this->add($this->getFormatted());
    }

    protected static function format(string $time, int $type, DateInterval $interval): string
    {
        if ($interval->invert === 1 && $type === static::RELATIVE) {
            $time = '-' . $time;
        }

        $values = [];
        switch ($type) {
            case static::DATE:
            case static::DATETIME:
                $values = ['on %s', 'An event will happen on the given date or date and time'];
                break;
            case static::RELATIVE:
                $values = ['in %s', 'An event will happen after the given time interval has elapsed'];
                break;
            case static::TIME:
                $values = ['at %s', 'An event will happen at the given time'];
        }

        return sprintf(t(...$values), $time);
    }
}
