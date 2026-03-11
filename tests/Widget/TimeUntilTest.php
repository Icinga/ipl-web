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
    use ipl\Web\Widget\TimeUntil;

    class TimeUntilTest extends TestCase
    {
        public function testConstructorAcceptsNullForCurrentTime(): void
        {
            $widget = new TimeUntil(null);

            $this->assertStringContainsString('data-relative-time="until"', $widget->render());
        }

        public function testConstructorAcceptsIntTimestamp(): void
        {
            $timestamp = mktime(12, 0, 0, 1, 15, 2030);
            $widget = new TimeUntil($timestamp);

            $this->assertStringContainsString('data-relative-time="until"', $widget->render());
        }

        public function testConstructorAcceptsFloatTimestamp(): void
        {
            $timestamp = (float) mktime(12, 0, 0, 1, 15, 2030);
            $widget = new TimeUntil($timestamp);

            $this->assertStringContainsString('data-relative-time="until"', $widget->render());
        }

        public function testConstructorAcceptsDateTime(): void
        {
            $widget = new TimeUntil(new DateTime('+30 minutes'));

            $this->assertStringContainsString('data-relative-time="until"', $widget->render());
        }

        public function testRenderHasTimeUntilClass(): void
        {
            $widget = new TimeUntil(new DateTime('+30 minutes'));

            $this->assertStringContainsString('class="time-until"', $widget->render());
        }

        public function testRenderHasDatetimeAttribute(): void
        {
            $dateTime = new DateTime('2030-01-15 12:00:00');
            $widget = new TimeUntil($dateTime);
            $rendered = $widget->render();

            $this->assertStringContainsString('datetime="2030-01-15 12:00:00"', $rendered);
        }

        public function testRenderHasTitleAttribute(): void
        {
            $dateTime = new DateTime('2030-01-15 12:00:00');
            $widget = new TimeUntil($dateTime);
            $rendered = $widget->render();

            $this->assertStringContainsString('title="2030-01-15 12:00:00"', $rendered);
        }

        public function testFormatWithFutureSubHourTimeShowsInPrefix(): void
        {
            $widget = new TimeUntil(new DateTime('+30 minutes'));
            $rendered = $widget->render();

            // RELATIVE type, invert=0 (future): "in %s" → "in 30m Xs"
            $this->assertMatchesRegularExpression('/in \d+m \d+s/', $rendered);
        }

        public function testFormatWithFutureHoursSameDayShowsAtPrefix(): void
        {
            $now = new DateTime();
            if ((int) $now->format('H') >= 21) {
                $this->markTestSkipped('Unreliable after 21:00 local time');
            }

            $widget = new TimeUntil(new DateTime('+2 hours'));
            $rendered = $widget->render();

            // TIME type: "at %s" → "at 16:30"
            $this->assertMatchesRegularExpression('/at \d{1,2}:\d{2}/', $rendered);
        }

        public function testFormatWithFarFutureDateShowsOnPrefix(): void
        {
            $fiveDaysLater = new DateTime('+5 days');
            if (date('Y') !== $fiveDaysLater->format('Y')) {
                $this->markTestSkipped('Skipped: test date crosses a year boundary');
            }

            $widget = new TimeUntil($fiveDaysLater);
            $rendered = $widget->render();

            // DATE type: "on %s" → "on Mar 4"
            $this->assertMatchesRegularExpression('/on [A-Z][a-z]+ \d+/', $rendered);
        }

        public function testFormatWithFutureDaysAndHoursShowsInPrefix(): void
        {
            $widget = new TimeUntil(new DateTime('+36 hours'));
            $rendered = $widget->render();

            // RELATIVE type with days: "in %s" → "in 1d 12h"
            $this->assertMatchesRegularExpression('/in \d+d \d+h/', $rendered);
        }

        public function testFormatWithPastRelativeTimeHasDashPrefix(): void
        {
            // When TimeUntil is used with a past sub-hour time, invert=1 → "-Xm Ys" prefix
            $widget = new TimeUntil(new DateTime('-30 minutes'));
            $rendered = $widget->render();

            // RELATIVE type, invert=1: "in -30m Xs"
            $this->assertMatchesRegularExpression('/in -\d+m \d+s/', $rendered);
        }

        public function testTimestampInputProducesCorrectDatetimeAttribute(): void
        {
            $timestamp = mktime(12, 0, 0, 1, 15, 2030);
            $widget = new TimeUntil($timestamp);
            $rendered = $widget->render();

            $this->assertStringContainsString('datetime="2030-01-15 12:00:00"', $rendered);
        }

        public function testFormatCrossMidnightShowsOnPrefixWithTime(): void
        {
            $hour = (int) date('H');
            if ($hour < 1 || $hour >= 23) {
                $this->markTestSkipped('Unreliable outside 01:00–23:00 local time');
            }

            // Tomorrow 00:30 — hours diff but different calendar day
            $widget = new TimeUntil(new DateTime('tomorrow 00:30'));
            $rendered = $widget->render();

            // DATE type with cross-midnight: "on %s" → "on Mar 3 00:30"
            $this->assertMatchesRegularExpression('/on [A-Z][a-z]+ \d+ \d{1,2}:\d{2}/', $rendered);
        }

        public function testRenderUsesTimeHtmlTag(): void
        {
            $widget = new TimeUntil(new DateTime('+30 minutes'));
            $rendered = $widget->render();

            $this->assertStringStartsWith('<time', $rendered);
            $this->assertStringEndsWith('</time>', $rendered);
        }

        public function testFormatWithFarFutureDifferentYearShowsOnPrefixWithYearMonth(): void
        {
            $farFuture = new DateTime('+400 days');
            if (date('Y') === $farFuture->format('Y')) {
                $this->markTestSkipped('Skipped: test date is still in the current year');
            }

            $widget = new TimeUntil($farFuture);
            $rendered = $widget->render();

            // DATE type with different year: "on %s" → "on 2028-04"
            $this->assertMatchesRegularExpression('/on \d{4}-\d{2}/', $rendered);
        }

        public function testFormatWithPastDaysAndHoursShowsDashPrefix(): void
        {
            $widget = new TimeUntil(new DateTime('-36 hours'));
            $rendered = $widget->render();

            // RELATIVE type, invert=1: "in %s" with "-" prefix → "in -1d 12h"
            $this->assertMatchesRegularExpression('/in -\d+d \d+h/', $rendered);
        }

        public function testSubHourTimeHasAgoLabelAttribute(): void
        {
            $widget = new TimeUntil(new DateTime('+30 minutes'));
            $rendered = $widget->render();

            $this->assertStringContainsString('data-ago-label=', $rendered);
        }

        public function testMultiHourTimeDoesNotHaveAgoLabelAttribute(): void
        {
            $widget = new TimeUntil(new DateTime('+2 hours'));
            $rendered = $widget->render();

            $this->assertStringNotContainsString('data-ago-label=', $rendered);
        }

        public function testMultiDayTimeDoesNotHaveAgoLabelAttribute(): void
        {
            $widget = new TimeUntil(new DateTime('+5 days'));
            $rendered = $widget->render();

            $this->assertStringNotContainsString('data-ago-label=', $rendered);
        }
    }
}
