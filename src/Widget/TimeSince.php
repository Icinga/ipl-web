<?php

namespace ipl\Web\Widget;

use ipl\Html\Attributes;
use ipl\Html\Text;

class TimeSince extends Time
{
    protected $defaultAttributes = ['class' => 'time-since'];

    protected function assemble(): void
    {
        $this->addAttributes(
            Attributes::create(
                [
                    'datetime'           => $this->timeString,
                    'data-relative-time' => 'since'
                ]
            )
        );

        $this->addHtml(Text::create($this->format()));
    }

    protected function format(): string
    {
        static $sinceMessage = ['since %s', 'A status is lasting since the given time, date or date and time'];
        static $map = [
            self::RELATIVE => ['for %s', 'A status is lasting for the given time interval'],
            self::TIME     => null,
            self::DATE     => null,
            self::DATETIME => null,
        ];

        [$time, $type] = $this->diff($this->dateTime);
        $format = $map[$type] ?? $sinceMessage;

        return sprintf(t(N_($format[0]), N_($format[1])), $time);
    }
}
