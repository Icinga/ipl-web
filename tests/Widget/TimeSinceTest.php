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
    use ipl\Web\Widget\TimeSince;

    class TimeSinceTest extends TestCase
    {
        public function testConstructorAcceptsNullForCurrentTime(): void
        {
            $widget = new TimeSince(null);

            $this->assertStringContainsString('data-relative-time="since"', $widget->render());
        }

        public function testConstructorAcceptsIntTimestamp(): void
        {
            $timestamp = mktime(12, 0, 0, 1, 15, 2024);
            $widget = new TimeSince($timestamp);

            $this->assertStringContainsString('data-relative-time="since"', $widget->render());
        }

        public function testConstructorAcceptsFloatTimestamp(): void
        {
            $timestamp = (float) mktime(12, 0, 0, 1, 15, 2024);
            $widget = new TimeSince($timestamp);

            $this->assertStringContainsString('data-relative-time="since"', $widget->render());
        }

        public function testConstructorAcceptsDateTime(): void
        {
            $widget = new TimeSince(new DateTime('-30 minutes'));

            $this->assertStringContainsString('data-relative-time="since"', $widget->render());
        }

        public function testRenderHasTimeSinceClass(): void
        {
            $widget = new TimeSince(new DateTime('-30 minutes'));

            $this->assertStringContainsString('class="time-since"', $widget->render());
        }

        public function testRenderHasDatetimeAttribute(): void
        {
            $dateTime = new DateTime('2024-01-15 12:00:00');
            $widget = new TimeSince($dateTime);
            $rendered = $widget->render();

            $this->assertStringContainsString('datetime="2024-01-15 12:00:00"', $rendered);
        }

        public function testRenderHasTitleAttribute(): void
        {
            $dateTime = new DateTime('2024-01-15 12:00:00');
            $widget = new TimeSince($dateTime);
            $rendered = $widget->render();

            $this->assertStringContainsString('title="2024-01-15 12:00:00"', $rendered);
        }

        public function testFormatWithSubHourTimeShowsForPrefix(): void
        {
            $widget = new TimeSince(new DateTime('-30 minutes'));
            $rendered = $widget->render();

            // RELATIVE type: "for %s" → "for 30m Xs"
            $this->assertMatchesRegularExpression('/for \d+m \d+s/', $rendered);
        }

        public function testFormatWithHoursAgoSameDayShowsSincePrefix(): void
        {
            $now = new DateTime();
            if ((int) $now->format('H') < 3) {
                $this->markTestSkipped('Unreliable before 03:00 local time');
            }

            $widget = new TimeSince(new DateTime('-2 hours'));
            $rendered = $widget->render();

            // TIME type: falls back to "since %s" → "since 14:30"
            $this->assertMatchesRegularExpression('/since \d{1,2}:\d{2}/', $rendered);
        }

        public function testFormatWithDaysAgoShowsSincePrefix(): void
        {
            $widget = new TimeSince(new DateTime('-5 days'));
            $rendered = $widget->render();

            // DATE type: falls back to "since %s" → "since Feb 22"
            $this->assertMatchesRegularExpression('/since [A-Z][a-z]+ \d+/', $rendered);
        }

        public function testFormatWithDaysAndHoursAgoShowsForRelative(): void
        {
            $widget = new TimeSince(new DateTime('-36 hours'));
            $rendered = $widget->render();

            // RELATIVE type with days: "for %s" → "for 1d 12h"
            $this->assertMatchesRegularExpression('/for \d+d \d+h/', $rendered);
        }

        public function testTimestampInputProducesCorrectDatetimeAttribute(): void
        {
            $timestamp = mktime(12, 0, 0, 1, 15, 2024);
            $widget = new TimeSince($timestamp);
            $rendered = $widget->render();

            $this->assertStringContainsString('datetime="2024-01-15 12:00:00"', $rendered);
        }

        public function testFormatCrossMidnightShowsSincePrefixWithTime(): void
        {
            $hour = (int) date('H');
            if ($hour < 1 || $hour >= 23) {
                $this->markTestSkipped('Unreliable outside 01:00–23:00 local time');
            }

            // Yesterday 23:30 — hours diff but different calendar day
            $widget = new TimeSince(new DateTime('yesterday 23:30'));
            $rendered = $widget->render();

            // DATE type with cross-midnight: "since %s" → "since Mar 1 23:30"
            $this->assertMatchesRegularExpression('/since [A-Z][a-z]+ \d+ \d{1,2}:\d{2}/', $rendered);
        }

        public function testRenderUsesTimeHtmlTag(): void
        {
            $widget = new TimeSince(new DateTime('-30 minutes'));
            $rendered = $widget->render();

            $this->assertStringStartsWith('<time', $rendered);
            $this->assertStringEndsWith('</time>', $rendered);
        }
    }
}
