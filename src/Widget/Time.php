<?php

namespace ipl\Web\Widget;

use DateInterval;
use DateTime;
use DateTimeZone;
use IntlDateFormatter;
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

    /** @var DateTime Time of this widget */
    protected DateTime $dateTime;

    /** @var string Default formatted datetime string */
    protected string $timeString;

    /** @var IntlDateFormatter|null Formatter to create the time string */
    protected ?IntlDateFormatter $formatter = null;

    /** @var string Tag of element. */
    protected $tag = 'time';

    /**
     * Create a time widget for the given time
     *
     * @param DateTime $time
     */
    public function __construct(DateTime $time)
    {
        $this->dateTime = $time;
        $this->timeString = $time->format('Y-m-d H:i:s');
        $this->addAttributes(Attributes::create(['title' => $this->timeString]));
    }

    /**
     * Set the formatter to use for formatting the time
     *
     * @param IntlDateFormatter $formatter
     *
     * @return $this
     */
    public function setFormatter(IntlDateFormatter $formatter): static
    {
        $this->formatter = $formatter;

        return $this;
    }

    /**
     * Compute the difference between the given time and now
     *
     * Returns an array with the interval, the formatted string, and the type of difference
     * Type can be one of the constants RELATIVE, TIME, DATE
     *
     * @param DateTime $time
     *
     * @return array{0: string, 1: self::TIME|self::DATE|self::RELATIVE, 2: DateInterval}
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
     * This method is only used by {@see assemble()}
     *
     * @return string
     */
    protected function format(): string
    {
        return $this->formatter ? $this->formatter->format($this->dateTime) : $this->timeString;
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

    /**
     * Convert a value to a DateTime object
     *
     * @param int|float|DateTime|null $value
     *
     * @return DateTime
     *
     * @throws \Exception
     *
     * @internal This method is for backwards compatibility
     */
    protected function castToDateTime(int|float|DateTime|null $value = null): DateTime
    {
        if ($value === null) {
            return new DateTime();
        }

        if ($value instanceof DateTime) {
            return $value;
        }

        $value = (int) ($value > 9999999999 ? ($value / 1000) : $value);

        return (new DateTime('@' . $value))->setTimezone(new DateTimeZone(date_default_timezone_get()));
    }
}
