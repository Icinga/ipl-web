<?php

namespace ipl\Tests\Web\Widget;

use DateTime;
use DateTimeZone;
use IntlDateFormatter;
use ipl\I18n\NoopTranslator;
use ipl\I18n\StaticTranslator;
use ipl\Tests\Web\TestCase;
use ipl\Web\Widget\Time;
use ipl\Web\Widget\TimeAgo;
use ipl\Web\Widget\TimeUntil;

class TimeTest extends TestCase
{
    protected function setUp(): void
    {
        StaticTranslator::$instance = new NoopTranslator();
    }

    /** Returns a concrete Time instance for testing the protected castToDateTime() method */
    private function createExposedTime(): object
    {
        return new class (new DateTime()) extends Time {
            public function publicCastToDateTime(int|float|DateTime|null $value = null): DateTime
            {
                return $this->castToDateTime($value);
            }
        };
    }

    public function testConstructorAcceptsNullForCurrentTime(): void
    {
        $now = '2026-03-17 14:17:07';
        $html = sprintf('<time title="%1$s" datetime="%1$s">%1$s</time>', $now);

        $rendered = (new Time(new DateTime($now)))->render();

        $this->assertSame($html, $rendered);
    }

    public function testRenderHasCorrectAttributes(): void
    {
        $eventTime = '2026-03-17 14:17:07';
        $time = new DateTime($eventTime);
        $rendered = (new Time($time))->render();

        $this->assertStringContainsString(sprintf('datetime="%s"', $eventTime), $rendered);
        $this->assertStringContainsString(sprintf('title="%s"', $eventTime), $rendered);
        $this->assertStringNotContainsString('data-relative-time', $rendered);
    }

    public function testBaseTimeRendersFullDateTimeString(): void
    {
        $time = new DateTime('2026-03-17 14:17:07');
        $widget = new Time($time);
        $rendered = $widget->render();

        $this->assertStringContainsString('>2026-03-17 14:17:07<', $rendered);
    }

    public function testRenderUsesFormatter(): void
    {
        $time = new DateTime('2026-03-17 14:17:07');
        $widget = new Time($time);
        $formatter = IntlDateFormatter::create(locale: 'en', pattern: 'Y_M_d H:m');

        $rendered = $widget->setFormatter($formatter)->render();

        $this->assertStringContainsString('>2026_3_17 14:17<', $rendered);
    }

    public function testDiffThreeDaysAndMore(): void
    {
        $time = new DateTime('2026-03-17 14:17:07');
        $timeMinimum = new DateTime('2026-03-14 14:17:07');
        $intervalFormatString = '%r %yy %mM %dd %hh %im %ss';

        [$format, $type, $interval] = (new Time($timeMinimum))->diff($time);

        $this->assertSame('Mar 14', $format);
        $this->assertSame(Time::DATE, $type);
        $this->assertSame(' 0y 0M 3d 0h 0m 0s', $interval->format($intervalFormatString));

        [$format, $type, $interval] = (new Time($time))->diff($timeMinimum);

        $this->assertSame('Mar 17', $format);
        $this->assertSame(Time::DATE, $type);
        $this->assertSame('- 0y 0M 3d 0h 0m 0s', $interval->format($intervalFormatString));

        $timeHighValue = new DateTime('2036-03-14 14:17:07');

        [$format, $type, $interval] = (new Time($timeMinimum))->diff($timeHighValue);

        $this->assertSame('2026-03', $format);
        $this->assertSame(Time::DATE, $type);
        $this->assertSame(' 10y 0M 0d 0h 0m 0s', $interval->format($intervalFormatString));

        [$format, $type, $interval] = (new Time($timeHighValue))->diff($timeMinimum);

        $this->assertSame('2036-03', $format);
        $this->assertSame(Time::DATE, $type);
        $this->assertSame('- 10y 0M 0d 0h 0m 0s', $interval->format($intervalFormatString));
    }

    public function testDiffOneToThreeDays(): void
    {
        $time = new DateTime('2026-03-17 14:17:07');
        $timeMinimum = new DateTime('2026-03-16 14:17:07');
        $intervalFormatString = '%r %yy %mM %dd %hh %im %ss';

        [$format, $type, $interval] = (new Time($timeMinimum))->diff($time);

        $this->assertSame('1d 0h', $format);
        $this->assertSame(Time::RELATIVE, $type);
        $this->assertSame(' 0y 0M 1d 0h 0m 0s', $interval->format($intervalFormatString));

        [$format, $type, $interval] = (new Time($time))->diff($timeMinimum);

        $this->assertSame('1d 0h', $format);
        $this->assertSame(Time::RELATIVE, $type);
        $this->assertSame('- 0y 0M 1d 0h 0m 0s', $interval->format($intervalFormatString));

        $timeMaximum = new DateTime('2026-03-19 14:17:06');

        [$format, $type, $interval] = (new Time($timeMinimum))->diff($timeMaximum);

        $this->assertSame('2d 23h', $format);
        $this->assertSame(Time::RELATIVE, $type);
        $this->assertSame(' 0y 0M 2d 23h 59m 59s', $interval->format($intervalFormatString));

        [$format, $type, $interval] = (new Time($timeMaximum))->diff($timeMinimum);

        $this->assertSame('2d 23h', $format);
        $this->assertSame(Time::RELATIVE, $type);
        $this->assertSame('- 0y 0M 2d 23h 59m 59s', $interval->format($intervalFormatString));
    }

    public function testDiffSubHourDifference(): void
    {
        $time = new DateTime('2026-03-17 14:47:07');
        $timeMinimum = new DateTime('2026-03-17 14:47:06');
        $intervalFormatString = '%r %yy %mM %dd %hh %im %ss';

        [$format, $type, $interval] = (new Time($time))->diff($time);

        $this->assertSame('0m 0s', $format);
        $this->assertSame(Time::RELATIVE, $type);
        $this->assertSame(' 0y 0M 0d 0h 0m 0s', $interval->format($intervalFormatString));

        [$format, $type, $interval] = (new Time($timeMinimum))->diff($time);

        $this->assertSame('0m 1s', $format);
        $this->assertSame(Time::RELATIVE, $type);
        $this->assertSame(' 0y 0M 0d 0h 0m 1s', $interval->format($intervalFormatString));

        [$format, $type, $interval] = (new Time($time))->diff($timeMinimum);

        $this->assertSame('0m 1s', $format);
        $this->assertSame(Time::RELATIVE, $type);
        $this->assertSame('- 0y 0M 0d 0h 0m 1s', $interval->format($intervalFormatString));

        $timeMaximum = new DateTime('2026-03-17 15:47:05');

        [$format, $type, $interval] = (new Time($timeMinimum))->diff($timeMaximum);

        $this->assertSame('59m 59s', $format);
        $this->assertSame(Time::RELATIVE, $type);
        $this->assertSame(' 0y 0M 0d 0h 59m 59s', $interval->format($intervalFormatString));

        [$format, $type, $interval] = (new Time($timeMaximum))->diff($timeMinimum);

        $this->assertSame('59m 59s', $format);
        $this->assertSame(Time::RELATIVE, $type);
        $this->assertSame('- 0y 0M 0d 0h 59m 59s', $interval->format($intervalFormatString));
    }

    public function testDiffHoursSameDay(): void
    {
        $time = new DateTime('2026-03-17 01:00:00');
        $timeMinimum = new DateTime('2026-03-17 00:00:00');
        $intervalFormatString = '%r %yy %mM %dd %hh %im %ss';

        [$format, $type, $interval] = (new Time($timeMinimum))->diff($time);

        $this->assertSame('00:00', $format);
        $this->assertSame(Time::TIME, $type);
        $this->assertSame(' 0y 0M 0d 1h 0m 0s', $interval->format($intervalFormatString));

        [$format, $type, $interval] = (new Time($time))->diff($timeMinimum);

        $this->assertSame('01:00', $format);
        $this->assertSame(Time::TIME, $type);
        $this->assertSame('- 0y 0M 0d 1h 0m 0s', $interval->format($intervalFormatString));

        $timeMaximum = new DateTime('2026-03-17 23:59:59');

        [$format, $type, $interval] = (new Time($timeMinimum))->diff($timeMaximum);

        $this->assertSame('00:00', $format);
        $this->assertSame(Time::TIME, $type);
        $this->assertSame(' 0y 0M 0d 23h 59m 59s', $interval->format($intervalFormatString));

        [$format, $type, $interval] = (new Time($timeMaximum))->diff($timeMinimum);

        $this->assertSame('23:59', $format);
        $this->assertSame(Time::TIME, $type);
        $this->assertSame('- 0y 0M 0d 23h 59m 59s', $interval->format($intervalFormatString));
    }

    public function testDiffPastYear(): void
    {
        $time = new DateTime('2026-12-31 23:47:07');
        $compareTime3DaysAndMore = new DateTime('2027-09-13 14:17:07');
        $intervalFormatString = '%r %yy %mM %dd %hh %im %ss';
        $widget = new Time($time);

        [$format, $type, $interval] = $widget->diff($compareTime3DaysAndMore);

        $this->assertSame('2026-12', $format);
        $this->assertSame(Time::DATE, $type);
        $this->assertSame(' 0y 8M 12d 14h 30m 0s', $interval->format($intervalFormatString));

        $compareTime1To3Days = new DateTime('2027-01-02 11:47:07');

        [$format, $type, $interval] = $widget->diff($compareTime1To3Days);

        $this->assertSame('1d 12h', $format);
        $this->assertSame(Time::RELATIVE, $type);
        $this->assertSame(' 0y 0M 1d 12h 0m 0s', $interval->format($intervalFormatString));

        $compareTimeLess1Day = new DateTime('2027-01-01 11:47:07');

        [$format, $type, $interval] = $widget->diff($compareTimeLess1Day);

        $this->assertSame('Dec 31 23:47', $format);
        $this->assertSame(Time::DATE, $type);
        $this->assertSame(' 0y 0M 0d 12h 0m 0s', $interval->format($intervalFormatString));

        $compareTimeLess1Hour = new DateTime('2027-01-01 00:17:07');

        [$format, $type, $interval] = $widget->diff($compareTimeLess1Hour);

        $this->assertSame('30m 0s', $format);
        $this->assertSame(Time::RELATIVE, $type);
        $this->assertSame(' 0y 0M 0d 0h 30m 0s', $interval->format($intervalFormatString));
    }

    public function testDiffNextYear(): void
    {
        $time = new DateTime('2027-01-01 00:17:07');
        $compareTime3DaysAndMore = new DateTime('2026-04-18 09:47:07');
        $intervalFormatString = '%r %yy %mM %dd %hh %im %ss';
        $widget = new Time($time);

        [$format, $type, $interval] = $widget->diff($compareTime3DaysAndMore);

        $this->assertSame('2027-01', $format);
        $this->assertSame(Time::DATE, $type);
        $this->assertSame('- 0y 8M 12d 14h 30m 0s', $interval->format($intervalFormatString));

        $compareTime1To3Days = new DateTime('2026-12-30 12:17:07');

        [$format, $type, $interval] = $widget->diff($compareTime1To3Days);

        $this->assertSame('1d 12h', $format);
        $this->assertSame(Time::RELATIVE, $type);
        $this->assertSame('- 0y 0M 1d 12h 0m 0s', $interval->format($intervalFormatString));

        $compareTimeLess1Day = new DateTime('2026-12-31 12:17:07');

        [$format, $type, $interval] = $widget->diff($compareTimeLess1Day);

        $this->assertSame('Jan 1 00:17', $format);
        $this->assertSame(Time::DATE, $type);
        $this->assertSame('- 0y 0M 0d 12h 0m 0s', $interval->format($intervalFormatString));

        $compareTimeLess1Hour = new DateTime('2026-12-31 23:47:07');

        [$format, $type, $interval] = $widget->diff($compareTimeLess1Hour);

        $this->assertSame('30m 0s', $format);
        $this->assertSame(Time::RELATIVE, $type);
        $this->assertSame('- 0y 0M 0d 0h 30m 0s', $interval->format($intervalFormatString));
    }

    public function testDiffCrossMidnightLessThanDayAgo(): void
    {
        $time = new DateTime('2026-03-17 23:47:07');
        $compareTimeInHours = new DateTime('2026-03-18 09:47:07');
        $intervalFormatString = '%r %yy %mM %dd %hh %im %ss';
        $widget = new Time($time);

        [$format, $type, $interval] = $widget->diff($compareTimeInHours);

        $this->assertSame('Mar 17 23:47', $format);
        $this->assertSame(Time::DATE, $type);
        $this->assertSame(' 0y 0M 0d 10h 0m 0s', $interval->format($intervalFormatString));

        $compareTimeInMinutes = new DateTime('2026-03-18 00:17:07');

        [$format, $type, $interval] = $widget->diff($compareTimeInMinutes);

        $this->assertSame('30m 0s', $format);
        $this->assertSame(Time::RELATIVE, $type);
        $this->assertSame(' 0y 0M 0d 0h 30m 0s', $interval->format($intervalFormatString));
    }

    public function testDiffCrossMidnightLessThanDayInFuture(): void
    {
        $time = new DateTime('2026-03-18 00:17:07');
        $compareTimeHoursAgo = new DateTime('2026-03-17 14:17:07');
        $intervalFormatString = '%r %yy %mM %dd %hh %im %ss';
        $widget = new Time($time);

        [$format, $type, $interval] = $widget->diff($compareTimeHoursAgo);

        $this->assertSame('Mar 18 00:17', $format);
        $this->assertSame(Time::DATE, $type);
        $this->assertSame('- 0y 0M 0d 10h 0m 0s', $interval->format($intervalFormatString));

        $compareTimeMinutesAgo = new DateTime('2026-03-17 23:47:07');

        [$format, $type, $interval] = $widget->diff($compareTimeMinutesAgo);

        $this->assertSame('30m 0s', $format);
        $this->assertSame(Time::RELATIVE, $type);
        $this->assertSame('- 0y 0M 0d 0h 30m 0s', $interval->format($intervalFormatString));
    }

    public function testRelativeReturnsTimeAgoForPastDateTime(): void
    {
        $resultNow = Time::relative(new DateTime('-1 hour'));

        $this->assertInstanceOf(TimeAgo::class, $resultNow);

        $time = new DateTime('2026-03-17 14:17:07');
        $compareTime = new DateTime('2026-03-17 15:17:07');

        $result = Time::relative($time, $compareTime);
        $agoRendered = (new TimeAgo($time, $compareTime))->render();

        $this->assertInstanceOf(TimeAgo::class, $result);
        $this->assertSame($agoRendered, $result->render());
    }

    public function testRelativeReturnsTimeUntilForFutureDateTime(): void
    {
        $resultNow = Time::relative(new DateTime('+1 hour'));

        $this->assertInstanceOf(TimeUntil::class, $resultNow);

        $time = new DateTime('2026-03-17 14:17:07');
        $compareTime = new DateTime('2026-03-17 13:17:07');

        $result = Time::relative($time, $compareTime);
        $untilRendered = (new TimeUntil($time, $compareTime))->render();

        $this->assertInstanceOf(TimeUntil::class, $result);
        $this->assertSame($untilRendered, $result->render());
    }

    public function testRelativeReturnsTimeUntilForCurrentDateTime(): void
    {
        $resultNow = Time::relative(new DateTime());

        $this->assertInstanceOf(TimeAgo::class, $resultNow);

        $time = $compareTime = new DateTime('2026-03-17 14:17:07');

        $result = Time::relative($time, $compareTime);
        $untilRendered = (new TimeAgo($time, $compareTime))->render();

        $this->assertInstanceOf(TimeAgo::class, $result);
        $this->assertSame($untilRendered, $result->render());
    }

    public function testCastToDateTimeReturnsCurrentTimeForNull(): void
    {
        $widget = $this->createExposedTime();
        $result = $widget->publicCastToDateTime(null);

        $this->assertInstanceOf(DateTime::class, $result);
        $this->assertEqualsWithDelta(time(), $result->getTimestamp(), 2);
    }

    public function testCastToDateTimeReturnsSameObjectForDateTime(): void
    {
        $widget = $this->createExposedTime();
        $dateTime = new DateTime('2026-03-17 14:17:07');
        $result = $widget->publicCastToDateTime($dateTime);

        $this->assertSame($dateTime, $result);
    }

    public function testCastToDateTimeConvertsUnixTimestamp(): void
    {
        $widget = $this->createExposedTime();
        $timestamp = mktime(14, 17, 7, 3, 17, 2026);
        $result = $widget->publicCastToDateTime($timestamp);

        $this->assertInstanceOf(DateTime::class, $result);
        $this->assertSame($timestamp, $result->getTimestamp());

        $time = (new DateTime('@' . $timestamp))->setTimezone(new DateTimeZone(date_default_timezone_get()));

        $this->assertEquals($time, $result);
    }

    public function testCastToDateTimeConvertsMillisecondTimestamp(): void
    {
        $widget = $this->createExposedTime();
        $timestamp = mktime(14, 17, 7, 3, 17, 2026);
        $millisecondTimestamp = ($timestamp * 1000);
        $result = $widget->publicCastToDateTime($millisecondTimestamp);

        $this->assertInstanceOf(DateTime::class, $result);
        $this->assertSame($timestamp, $result->getTimestamp());


        $result2 = $widget->publicCastToDateTime($timestamp * 10000);

        $this->assertNotEquals($timestamp, $result2->getTimestamp());

        $result3 = $widget->publicCastToDateTime($timestamp * 100);

        $this->assertNotEquals($timestamp, $result3->getTimestamp());

        $result4 = $widget->publicCastToDateTime($timestamp * 10);

        $this->assertNotEquals($timestamp, $result4->getTimestamp());
    }

    public function testCastToDateTimeConvertsSmallFloat(): void
    {
        $widget = $this->createExposedTime();
        $timestamp = 1705312800.5;
        $result = $widget->publicCastToDateTime($timestamp);

        $this->assertInstanceOf(DateTime::class, $result);
        $this->assertSame(1705312800, $result->getTimestamp());
    }
}
