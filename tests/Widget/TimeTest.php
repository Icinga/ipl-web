<?php

// Define t() and N_() in ipl\Web\Widget namespace so unqualified calls from widget code resolve here
namespace ipl\Web\Widget {
    if (! function_exists('ipl\Web\Widget\t')) {
        function t(string $message, ?string $context = null): string
        {
            return $message;
        }
    }

    if (! function_exists('ipl\Web\Widget\N_')) {
        function N_(string $message): string
        {
            return $message;
        }
    }
}

namespace ipl\Tests\Web\Widget {
    use DateTime;
    use ipl\Tests\Web\TestCase;
    use ipl\Web\Widget\Time;
    use ipl\Web\Widget\TimeAgo;
    use ipl\Web\Widget\TimeUntil;

    class TimeTest extends TestCase
    {
        /** Returns a concrete Time instance for testing the protected castToDateTime() method */
        private function createExposedTime(): object
        {
            return new class(new DateTime()) extends Time {
                public function publicCastToDateTime(int|float|DateTime|null $value = null): DateTime
                {
                    return $this->castToDateTime($value);
                }
            };
        }

        public function testDiffReturnsDateTypeForMoreThanTwoDaysAgo(): void
        {
            $widget = new TimeAgo(new DateTime());
            [, $type] = $widget->diff(new DateTime('-5 days'));

            $this->assertSame(Time::DATE, $type);
        }

        public function testDiffReturnsRelativeTypeForOneToTwoDaysAgo(): void
        {
            $widget = new TimeAgo(new DateTime());
            [, $type] = $widget->diff(new DateTime('-36 hours'));

            $this->assertSame(Time::RELATIVE, $type);
        }

        public function testDiffReturnsRelativeTypeForSubHourDifference(): void
        {
            $widget = new TimeAgo(new DateTime());
            [, $type] = $widget->diff(new DateTime('-30 minutes'));

            $this->assertSame(Time::RELATIVE, $type);
        }

        public function testDiffFormatsRelativeTimeWithDaysPattern(): void
        {
            $widget = new TimeAgo(new DateTime());
            [$formatted] = $widget->diff(new DateTime('-36 hours'));

            $this->assertMatchesRegularExpression('/^\d+d \d+h$/', $formatted);
        }

        public function testDiffFormatsRelativeTimeWithMinutesPattern(): void
        {
            $widget = new TimeAgo(new DateTime());
            [$formatted] = $widget->diff(new DateTime('-30 minutes'));

            $this->assertMatchesRegularExpression('/^\d+m \d+s$/', $formatted);
        }

        public function testDiffFormatsDateWithMonthAndDayForSameYear(): void
        {
            $widget = new TimeAgo(new DateTime());
            $fiveDaysAgo = new DateTime('-5 days');

            if (date('Y') !== $fiveDaysAgo->format('Y')) {
                $this->markTestSkipped('Skipped: test date crosses a year boundary');
            }

            [$formatted] = $widget->diff($fiveDaysAgo);

            // e.g., "Feb 22"
            $this->assertMatchesRegularExpression('/^[A-Z][a-z]+ \d+$/', $formatted);
        }

        public function testDiffFormatsDateWithYearMonthForPreviousYear(): void
        {
            $widget = new TimeAgo(new DateTime());
            $lastYear = new DateTime('-400 days');

            if (date('Y') === $lastYear->format('Y')) {
                $this->markTestSkipped('Skipped: test date is still in the current year');
            }

            [$formatted] = $widget->diff($lastYear);

            // e.g., "2024-01"
            $this->assertMatchesRegularExpression('/^\d{4}-\d{2}$/', $formatted);
        }

        public function testDiffReturnsTimeTypeForHoursAgoSameDay(): void
        {
            $now = new DateTime();
            if ((int) $now->format('H') < 3) {
                $this->markTestSkipped('Unreliable before 03:00 local time');
            }

            $widget = new TimeAgo(new DateTime());
            [, $type] = $widget->diff(new DateTime('-2 hours'));

            $this->assertSame(Time::TIME, $type);
        }

        public function testDiffFormatsTimeWithHoursAndMinutes(): void
        {
            $now = new DateTime();
            if ((int) $now->format('H') < 3) {
                $this->markTestSkipped('Unreliable before 03:00 local time');
            }

            $widget = new TimeAgo(new DateTime());
            [$formatted] = $widget->diff(new DateTime('-2 hours'));

            // e.g., "14:30"
            $this->assertMatchesRegularExpression('/^\d{1,2}:\d{2}$/', $formatted);
        }

        public function testDiffReturnsDateTypeForFarFuture(): void
        {
            $widget = new TimeAgo(new DateTime());
            [, $type] = $widget->diff(new DateTime('+5 days'));

            $this->assertSame(Time::DATE, $type);
        }

        public function testRelativeReturnsTimeAgoForPastDateTime(): void
        {
            $result = Time::relative(new DateTime('-1 hour'));

            $this->assertInstanceOf(TimeAgo::class, $result);
        }

        public function testRelativeReturnsTimeUntilForFutureDateTime(): void
        {
            $result = Time::relative(new DateTime('+1 hour'));

            $this->assertInstanceOf(TimeUntil::class, $result);
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
            $dateTime = new DateTime('2024-01-15 12:00:00');
            $result = $widget->publicCastToDateTime($dateTime);

            $this->assertSame($dateTime, $result);
        }

        public function testCastToDateTimeConvertsUnixTimestamp(): void
        {
            $widget = $this->createExposedTime();
            $timestamp = mktime(12, 0, 0, 1, 15, 2024);
            $result = $widget->publicCastToDateTime($timestamp);

            $this->assertInstanceOf(DateTime::class, $result);
            $this->assertSame($timestamp, $result->getTimestamp());
        }

        public function testCastToDateTimeConvertsMillisecondTimestamp(): void
        {
            $widget = $this->createExposedTime();
            $timestamp = mktime(12, 0, 0, 1, 15, 2024);
            $millisecondTimestamp = (float) ($timestamp * 1000);
            $result = $widget->publicCastToDateTime($millisecondTimestamp);

            $this->assertInstanceOf(DateTime::class, $result);
            $this->assertSame($timestamp, $result->getTimestamp());
        }

        public function testCastToDateTimeConvertsSmallFloat(): void
        {
            $widget = $this->createExposedTime();
            $timestamp = 1705312800.5;
            $result = $widget->publicCastToDateTime($timestamp);

            $this->assertInstanceOf(DateTime::class, $result);
            $this->assertSame(1705312800, $result->getTimestamp());
        }

        public function testDiffReturnsDateTypeForCrossMidnightHours(): void
        {
            $hour = (int) date('H');
            if ($hour < 1 || $hour >= 23) {
                $this->markTestSkipped('Unreliable outside 01:00–23:00 local time');
            }

            $widget = new TimeAgo(new DateTime());
            // Yesterday 23:30 — hours diff but different calendar day
            $yesterday = new DateTime('yesterday 23:30');
            [, $type] = $widget->diff($yesterday);

            $this->assertSame(Time::DATE, $type);
        }

        public function testDiffFormatsCrossMidnightWithMonthDayAndTime(): void
        {
            $hour = (int) date('H');
            if ($hour < 1 || $hour >= 23) {
                $this->markTestSkipped('Unreliable outside 01:00–23:00 local time');
            }

            $widget = new TimeAgo(new DateTime());
            $yesterday = new DateTime('yesterday 23:30');
            [$formatted] = $widget->diff($yesterday);

            // e.g., "Mar 1 23:30"
            $this->assertMatchesRegularExpression('/^[A-Z][a-z]+ \d+ \d{1,2}:\d{2}$/', $formatted);
        }

        public function testBaseTimeRendersFullDateTimeString(): void
        {
            $dateTime = new DateTime('2024-01-15 12:00:00');
            $widget = new Time($dateTime);
            $rendered = $widget->render();

            $this->assertStringContainsString('>2024-01-15 12:00:00<', $rendered);
        }
    }
}
