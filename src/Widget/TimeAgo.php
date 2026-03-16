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
        [, , $interval] = $this->diff($this->dateTime);

        $attributes = [
            'datetime'           => $this->timeString,
            'data-relative-time' => 'ago'
        ];

        if ($interval->days === 0 && $interval->h === 0) {
            $attributes['data-ago-label'] = sprintf(
                t('%s ago', 'An event that happened the given time interval ago'),
                '0m 0s'
            );
        }

        $this->addAttributes(Attributes::create($attributes));
        $this->addHtml(Text::create($this->format()));
    }

    protected function format(): string
    {
        $onMessage = t('on %s', 'An event happened on the given date or date and time');
        $map = [
            self::RELATIVE => t('%s ago', 'An event that happened the given time interval ago'),
            self::TIME     => t('at %s', 'An event happened at the given time'),
            self::DATE     => null,
        ];

        [$time, $type] = $this->diff($this->dateTime);

        return sprintf($map[$type] ?? $onMessage, $time);
    }
}
