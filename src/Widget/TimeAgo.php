<?php

namespace ipl\Web\Widget;

use DateTime;
use ipl\Html\Attributes;
use ipl\Html\Text;

class TimeAgo extends Time
{
    protected $defaultAttributes = ['class' => 'time-ago'];

    /**
     * @param int|float|DateTime|null $time Time as timestamp, DateTime object, or null for current time
     *
     * @throws \Exception
     */
    public function __construct(int|float|DateTime|null $time = null)
    {
        if (! $time instanceof DateTime) {
            $time = $this->castToDateTime($time);
        }

        parent::__construct($time);
    }

    protected function assemble(): void
    {
        $this->addAttributes(
            Attributes::create(
                [
                    'datetime'           => $this->timeString,
                    'data-relative-time' => 'ago'
                ]
            )
        );

        $this->addHtml(Text::create($this->format()));
    }

    protected function format(): string
    {
        static $onMessage = ['on %s', 'An event happened on the given date or date and time'];
        static $map = [
            self::RELATIVE => ['%s ago', 'An event that happened the given time interval ago'],
            self::TIME     => ['at %s', 'An event happened at the given time'],
            self::DATE     => null,
            self::DATETIME => null,
        ];

        [$time, $type] = $this->diff($this->dateTime);
        $format = $map[$type] ?? $onMessage;

        return sprintf(t(N_($format[0]), N_($format[1])), $time);
    }
}
