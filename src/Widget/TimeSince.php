<?php

namespace ipl\Web\Widget;

use DateTime;
use ipl\Html\Attributes;
use ipl\Html\Text;

class TimeSince extends Time
{
    protected $defaultAttributes = ['class' => 'time-since'];

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
                    'data-relative-time' => 'since'
                ]
            )
        );

        $this->addHtml(Text::create($this->format()));
    }

    protected function format(): string
    {
        $sinceMessage = [N_('since %s'), N_('A status is lasting since the given time, date or date and time')];
        $map = [
            self::RELATIVE => [N_('for %s'), N_('A status is lasting for the given time interval')],
            self::TIME     => null,
            self::DATE     => null,
            self::DATETIME => null,
        ];

        [$time, $type] = $this->diff($this->dateTime);
        $format = $map[$type] ?? $sinceMessage;

        return sprintf(t($format[0], $format[1]), $time);
    }
}
