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
            [$format, $description] = static::getRelativeTimeFormat();
            $attributes['data-ago-label'] = sprintf(t($format, $description), '0m 0s');
        }

        $this->addAttributes(Attributes::create($attributes));
        $this->addHtml(Text::create($this->format()));
    }

    protected function format(): string
    {
        $onMessage = [N_('on %s'), N_('An event happened on the given date or date and time')];
        $map = [
            self::RELATIVE => static::getRelativeTimeFormat(),
            self::TIME     => [N_('at %s'), N_('An event happened at the given time')],
            self::DATE     => null,
        ];

        [$time, $type] = $this->diff($this->dateTime);
        $format = $map[$type] ?? $onMessage;

        return sprintf(t($format[0], $format[1]), $time);
    }

    /**
     * Get the format for relative time intervals.
     *
     * Returns the translation string and description used for formatting
     * time intervals in the past (e.g., "5 minutes ago").
     *
     * @return array An array containing the format string and its description.
     */
    public static function getRelativeTimeFormat(): array
    {
        return [N_('%s ago'), N_('An event that happened the given time interval ago')];
    }
}
