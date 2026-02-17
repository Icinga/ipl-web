<?php

namespace ipl\Web\Widget;

use DateTime;
use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\Text;

class Time extends BaseHtmlElement
{
    /** @var int Format relative */
    public const RELATIVE = 0;

    /** @var int Format time */
    public const TIME = 1;

    /** @var int Format date */
    public const DATE = 2;

    /** @var int Format date and time */
    public const DATETIME = 4;

    /** @var DateTime time of this widget */
    protected DateTime $dateTime;

    /** @var string DateTime string in ISO 8601 format */
    protected string $timeString;

    /** @var string Tag of element. */
    protected $tag = 'time';

    public function __construct(DateTime $time)
    {
        $this->dateTime = $time;
        $this->timeString = $time->format('Y-m-d H:i:s');
        $this->addAttributes(Attributes::create(['title' => $this->timeString]));
    }

    /**
     * Compute difference between two dates
     *
     * Returns an array with the interval, the formatted string and the type of difference
     * Type can be one of the constants RELATIVE, TIME, DATE, DATETIME
     * Passing null as a parameter will use the current time
     *
     * @param DateTime $time
     *
     * @return array [string<formattedTime>, int<type>, DateInterval]
     */
    public function diff(DateTime $time): array
    {
        $now = new DateTime();

        $interval = $now->diff($time);

        if ($interval->days > 2) {
            $type = static::DATE;
            $formatted = $time->format(date('Y') === date('Y', $time->getTimestamp()) ? 'M j' : 'Y-m');
        } elseif ($interval->days > 0) {
            $type = static::RELATIVE;
            $formatted = $interval->format('%dd %hh');
        } elseif ($interval->h > 0) {
            if (date('d') === date('d', $time->getTimestamp())) {
                $type = static::TIME;
                $formatted = $time->format('H:i');
            } else {
                $type = static::DATE;
                $formatted = $time->format('M j H:i');
            }
        } else {
            $type = static::RELATIVE;
            $formatted = $interval->format('%im %ss');
        }

        return [$formatted, $type, $interval];
    }

    /**
     * Get formatted time
     *
     * @return string
     */
    protected function format(): string
    {
        return $this->timeString;
    }

    /**
     * Return a relative time widget
     *
     * @param DateTime $time
     *
     * @return static
     */
    public static function relative(DateTime $time): static
    {
        return $time->getTimestamp() < time() ? new TimeAgo($time) : new TimeUntil($time);
    }

    /**
     * Assemble logic of subclasses
     * Override this method to customize the output
     *
     * @return void
     */
    protected function assemble(): void
    {
        $this->addHtml(Text::create($this->format()));
    }
}
