<?php

namespace ipl\Web\Widget;

use DateInterval;
use ipl\Html\Attributes;

class TimeAgo extends Time
{
    protected $defaultAttributes = ['class' => 'time-ago'];

    protected function assembleSpecific(): void
    {
        $this->addAttributes(
            Attributes::create(
                [
                    'datetime'           => $this->dateTime,
                    'data-relative-time' => 'ago'
                ]
            )
        );

        $this->add($this->getFormatted());
    }

    protected static function format(string $time, int $type, DateInterval $interval = null): string
    {
        $values = [];
        switch ($type) {
            case static::DATE:
            case static::DATETIME:
                $values = ['on %s', 'An event happened on the given date or date and time'];
                break;
            case static::RELATIVE:
                $values = ['%s ago', 'An event that happened the given time interval ago'];
                break;
            case static::TIME:
                $values = ['at %s', 'An event happened at the given time'];
        }

        return sprintf(t(...$values), $time);
    }
}
