<?php

namespace ipl\Tests\Web\Widget;

use DateTime;
use ipl\Tests\Web\TestCase;
use ipl\Web\Widget\TimeAgo;

class TimeAgoTest extends TestCase
{
    public function testConstructorAcceptsNullForCurrentTime(): void
    {
        $widget = new TimeAgo(null);

        $this->assertStringContainsString('data-relative-time="ago"', $widget->render());
    }

    public function testConstructorAcceptsIntTimestamp(): void
    {
        $timestamp = mktime(12, 0, 0, 1, 15, 2024);
        $widget = new TimeAgo($timestamp);

        $this->assertStringContainsString('data-relative-time="ago"', $widget->render());
    }

    public function testConstructorAcceptsFloatTimestamp(): void
    {
        $timestamp = (float) mktime(12, 0, 0, 1, 15, 2024);
        $widget = new TimeAgo($timestamp);

        $this->assertStringContainsString('data-relative-time="ago"', $widget->render());
    }

    public function testConstructorAcceptsDateTime(): void
    {
        $widget = new TimeAgo(new DateTime('-30 minutes'));

        $this->assertStringContainsString('data-relative-time="ago"', $widget->render());
    }

    public function testRenderHasTimeAgoClass(): void
    {
        $widget = new TimeAgo(new DateTime('-30 minutes'));

        $this->assertStringContainsString('class="time-ago"', $widget->render());
    }

    public function testRenderHasDatetimeAttribute(): void
    {
        $dateTime = new DateTime('2024-01-15 12:00:00');
        $widget = new TimeAgo($dateTime);
        $rendered = $widget->render();

        $this->assertStringContainsString('datetime="2024-01-15 12:00:00"', $rendered);
    }

    public function testRenderHasTitleAttribute(): void
    {
        $dateTime = new DateTime('2024-01-15 12:00:00');
        $widget = new TimeAgo($dateTime);
        $rendered = $widget->render();

        $this->assertStringContainsString('title="2024-01-15 12:00:00"', $rendered);
    }

    public function testFormatWithSubHourTimeShowsAgoSuffix(): void
    {
        $widget = new TimeAgo(new DateTime('-30 minutes'));
        $rendered = $widget->render();

        // RELATIVE type: "%s ago" → "30m Xs ago"
        $this->assertMatchesRegularExpression('/\d+m \d+s ago/', $rendered);
    }

    public function testFormatWithHoursAgoSameDayShowsAtPrefix(): void
    {
        $now = new DateTime();
        if ((int) $now->format('H') < 3) {
            $this->markTestSkipped('Unreliable before 03:00 local time');
        }

        $widget = new TimeAgo(new DateTime('-2 hours'));
        $rendered = $widget->render();

        // TIME type: "at %s" → "at 14:30"
        $this->assertMatchesRegularExpression('/at \d{1,2}:\d{2}/', $rendered);
    }

    public function testFormatWithDaysAgoShowsOnPrefix(): void
    {
        $fiveDaysAgo = new DateTime('-5 days');
        if (date('Y') !== $fiveDaysAgo->format('Y')) {
            $this->markTestSkipped('Skipped: test date crosses a year boundary');
        }

        $widget = new TimeAgo($fiveDaysAgo);
        $rendered = $widget->render();

        // DATE type: "on %s" → "on Feb 22"
        $this->assertMatchesRegularExpression('/on [A-Z][a-z]+ \d+/', $rendered);
    }

    public function testFormatWithDaysAndHoursAgoShowsRelative(): void
    {
        $widget = new TimeAgo(new DateTime('-36 hours'));
        $rendered = $widget->render();

        // RELATIVE type with days: "%s ago" → "1d 12h ago"
        $this->assertMatchesRegularExpression('/\d+d \d+h ago/', $rendered);
    }

    public function testTimestampInputProducesCorrectDatetimeAttribute(): void
    {
        $timestamp = mktime(12, 0, 0, 1, 15, 2024);
        $widget = new TimeAgo($timestamp);
        $rendered = $widget->render();

        $this->assertStringContainsString('datetime="2024-01-15 12:00:00"', $rendered);
    }

    public function testFormatCrossMidnightShowsOnPrefixWithTime(): void
    {
        $hour = (int) date('H');
        if ($hour < 1 || $hour >= 23) {
            $this->markTestSkipped('Unreliable outside 01:00–23:00 local time');
        }

        // Yesterday 23:30 — hours diff but different calendar day
        $widget = new TimeAgo(new DateTime('yesterday 23:30'));
        $rendered = $widget->render();

        // DATE type with cross-midnight: "on %s" → "on Mar 1 23:30"
        $this->assertMatchesRegularExpression('/on [A-Z][a-z]+ \d+ \d{1,2}:\d{2}/', $rendered);
    }

    public function testRenderUsesTimeHtmlTag(): void
    {
        $widget = new TimeAgo(new DateTime('-30 minutes'));
        $rendered = $widget->render();

        $this->assertStringStartsWith('<time', $rendered);
        $this->assertStringEndsWith('</time>', $rendered);
    }

    public function testFormatWithPreviousYearShowsOnPrefixWithYearMonth(): void
    {
        $lastYear = new DateTime('-400 days');
        if (date('Y') === $lastYear->format('Y')) {
            $this->markTestSkipped('Skipped: test date is still in the current year');
        }

        $widget = new TimeAgo($lastYear);
        $rendered = $widget->render();

        // DATE type with previous year: "on %s" → "on 2024-01"
        $this->assertMatchesRegularExpression('/on \d{4}-\d{2}/', $rendered);
    }

    public function testFormatWithFutureSubHourShowsAgoSuffix(): void
    {
        $widget = new TimeAgo(new DateTime('+30 minutes'));
        $rendered = $widget->render();

        // RELATIVE type even for future: "%s ago" → "29m Xs ago"
        $this->assertMatchesRegularExpression('/\d+m \d+s ago/', $rendered);
    }
}
