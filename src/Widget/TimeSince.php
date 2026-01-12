<?php

namespace ipl\Web\Widget;

use DateInterval;

class TimeSince extends Time
{
    protected $defaultAttributes = ['class' => 'time-since'];

    protected function assembleSpecific(): void
    {
        $this->addAttributes(['datetime' => $this->dateTime, 'data-relative-time' => 'since']);

        $this->add($this->getFormatted());
    }

    protected static function format(string $time, int $type, DateInterval $interval = null): string
    {
        $values = [];
        switch ($type) {
            case static::RELATIVE:
                $values = ['for %s', 'A status is lasting for the given time interval'];
                break;
            case static::DATE:
            case static::DATETIME:
            case static::TIME:
                $values = ['since %s', 'A status is lasting since the given time, date or date and time'];
        }

        return sprintf(t(...$values), $time);
    }
}
