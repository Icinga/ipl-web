<?php

namespace ipl\Web\Widget;

use ipl\Html\Attributes;
use ipl\Html\Text;

class TimeUntil extends Time
{
    protected $defaultAttributes = ['class' => 'time-until'];

    protected function assemble(): void
    {
        $this->addAttributes(
            Attributes::create(
                [
                    'datetime'           => $this->timeString,
                    'data-relative-time' => 'until',
                ]
            )
        );

        $this->addHtml(Text::create($this->format()));
    }

    protected function format(): string
    {
        static $onMessage = ['on %s', 'An event will happen on the given date or date and time'];
        static $map = [
            self::RELATIVE => ['in %s', 'An event will happen after the given time interval has elapsed'],
            self::TIME     => ['at %s', 'An event will happen at the given time'],
            self::DATE     => null,
            self::DATETIME => null,
        ];

        [$time, $type, $interval] = $this->diff($this->dateTime);
        $format = $map[$type] ?? $onMessage;

        if ($interval->invert === 1 && $type === static::RELATIVE) {
            $time = '-' . $time;
        }

        return sprintf(t(N_($format[0]), N_($format[1])), $time);
    }
}
