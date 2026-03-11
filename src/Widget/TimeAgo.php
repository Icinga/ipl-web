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
        $onMessage = [N_('on %s'), N_('An event happened on the given date or date and time')];
        $map = [
            self::RELATIVE => [N_('%s ago'), N_('An event that happened the given time interval ago')],
            self::TIME     => [N_('at %s'), N_('An event happened at the given time')],
            self::DATE     => null,
        ];

        [$time, $type] = $this->diff($this->dateTime);
        $format = $map[$type] ?? $onMessage;

        return sprintf(t($format[0], $format[1]), $time);
    }
}
