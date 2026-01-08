<?php

namespace ipl\Web\Widget;

use DateInterval;
use DateInvalidTimeZoneException;
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Exception;
use ipl\Html\BaseHtmlElement;

class Time extends BaseHtmlElement
{
    /**
     * Format relative
     *
     * @var int
     */
    public const RELATIVE = 0;

    /**
     * Format time
     *
     * @var int
     */
    public const TIME = 1;

    /**
     * Format date
     *
     * @var int
     */
    public const DATE = 2;

    /**
     * Format date and time
     *
     * @var int
     */
    public const DATETIME = 4;

    /** @var DateTimeImmutable */
    protected DateTimeImmutable $time;

    /** @var string */
    protected string $dateTime;

    protected $tag = 'time';

    public function __construct(int|float|DateTimeInterface $time)
    {
        $this->time = static::toImmutable($time);
        $this->dateTime = date('Y-m-d H:i:s', $this->time->getTimestamp());
    }

    /**
     * Compute difference between two dates
     *
     * Returns an array with the interval, the formatted string and the type of difference
     * Type can be one of the constants RELATIVE, TIME, DATE, DATETIME
     * Passing null as a parameter will use the current time
     *
     * @param DateTimeInterface|int|null $time
     * @param DateTimeInterface|int|null $compareTime
     *
     * @return array [string<formattedTime>, int<type>, DateInterval]
     */
    public static function diff(
        DateTimeInterface|int|null $time = null,
        DateTimeInterface|int|null $compareTime = null
    ): array {
        $time = static::toImmutable($time);
        $compareTime = static::toImmutable($compareTime);

        $interval = $compareTime->diff($time);

        if ($interval->d > 2 || $interval->m > 0 || $interval->y > 0) {
            $type = static::DATE;
            $formatted = $time->format(date('Y') === date('Y', $time->getTimestamp()) ? 'M j' : 'Y-m');
        } elseif ($interval->d > 0) {
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
     * Compute difference to a given date
     *
     * @param DateTimeInterface|int|null $compare
     *
     * @return array [string<formattedTime>, int<type>, DateInterval]
     */
    protected function diffTo(DateTimeInterface|int|null $compare = null): array
    {
        $time = $this->time;

        return static::diff($time, $compare);
    }

    /**
     * Compute difference now
     *
     * @return array [string<formattedTime>, int<type>, DateInterval]
     */
    protected function diffNow(): array
    {
        return static::diff($this->time);
    }

    /**
     * Get formatted time from a given time
     *
     * @param DateTimeInterface|int|null $time
     *
     * @return string
     */
    public static function getFormattedFromGiven(DateTimeInterface|int|null $time = null): string
    {
        [$formatted, $type, $interval] = static::diff(static::toImmutable($time));

        return static::format($formatted, $type, $interval);
    }

    /**
     * Get formatted time
     *
     * @return string
     */
    public function getFormatted(): string
    {
        [$formatted, $type, $interval] = $this->diffNow();

        return static::format($formatted, $type, $interval);
    }

    /**
     * Format a time string
     * Override this method to customize the formatting
     *
     * @param string $time
     * @param int $type
     * @param DateInterval $interval
     *
     * @return string
     */
    protected static function format(string $time, int $type, DateInterval $interval): string
    {
        return $interval->invert === 1 ? TimeAgo::format($time, $type) : TimeUntil::format($time, $type, $interval);
    }

    /**
     * Convert a value to a DateTimeImmutable
     *
     * @param int|float|DateTimeInterface|null $value
     *
     * @return DateTimeImmutable
     */
    public static function toImmutable(int|float|DateTimeInterface|null $value): DateTimeImmutable
    {
        if ($value === null) {
            return new DateTimeImmutable();
        }

        if ($value instanceof DateTimeImmutable) {
            return $value;
        }

        if ($value instanceof DateTimeInterface) {
            return DateTimeImmutable::createFromInterface($value);
        }

        return new DateTimeImmutable('@' . (int) $value);
    }

    /**
     * Return a relative time widget
     *
     * @return $this
     */
    public function relative(): static
    {
        if ($this->time->getTimestamp() < time()) {
            return new TimeAgo($this->time);
        } else {
            return new TimeUntil($this->time);
        }
    }

    protected function assemble(): void
    {
        $this->addAttributes(['title' => $this->dateTime]);
        $this->addAttributes('data-relative-time');

        $this->assembleSpecific();
    }

    /**
     * Assemble specific logic of subclasses
     * Override this method to customize the output
     *
     * @return void
     */
    protected function assembleSpecific(): void
    {
        $this->addAttributes(['data-relative-time' => null]);
        $this->add($this->dateTime);
    }
}
