<?php

namespace ipl\Web\Widget;

use DateTime;
use ipl\Html\Attributes;
use ipl\Html\Text;

class TimeUntil extends Time
{
    protected $defaultAttributes = ['class' => 'time-until'];

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
                    'data-relative-time' => 'until',
                ]
            )
        );

        $this->addHtml(Text::create($this->format()));
    }

    protected function format(): string
    {
        $onMessage = [N_('on %s'), N_('An event will happen on the given date or date and time')];
        $map = [
            self::RELATIVE => [N_('in %s'), N_('An event will happen after the given time interval has elapsed')],
            self::TIME     => [N_('at %s'), N_('An event will happen at the given time')],
            self::DATE     => null,
            self::DATETIME => null,
        ];

        [$time, $type, $interval] = $this->diff($this->dateTime);
        $format = $map[$type] ?? $onMessage;

        if ($interval->invert === 1 && $type === static::RELATIVE) {
            $time = '-' . $time;
        }

        return sprintf(t($format[0], $format[1]), $time);
    }
}
