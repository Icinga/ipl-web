<?php

namespace ipl\Tests\Web\Widget;

use DateTime;
use IntlDateFormatter;
use ipl\I18n\NoopTranslator;
use ipl\I18n\StaticTranslator;
use ipl\Tests\Web\TestCase;
use ipl\Web\Widget\TimeAgo;

class TimeAgoTest extends TestCase
{
    protected function setUp(): void
    {
        StaticTranslator::$instance = new NoopTranslator();
    }

    public function testConstructorAcceptsNullForCurrentTime(): void
    {
        $nowDateTime = new DateTime();
        $html = sprintf(
            '<time %s title="%2$s" datetime="%2$s" data-ago-label="0m 0s ago">0m 0s ago</time>',
            'class="time-ago" data-relative-time="ago"',
            $nowDateTime->format('Y-m-d H:i:s'),
        );
        $renderedWithoutParams = (new TimeAgo(null, null))->render();

        $this->assertSame($html, $renderedWithoutParams);

        $renderedWithParams = (new TimeAgo($nowDateTime, $nowDateTime))->render();

        $this->assertSame($html, $renderedWithParams);
    }

    public function testConstructorAcceptsTimestamp(): void
    {
        $html = sprintf(
            '<time %s title="%2$s" datetime="%2$s" data-ago-label="0m 0s ago">30m 0s ago</time>',
            'class="time-ago" data-relative-time="ago"',
            '2026-03-17 14:17:07',
        );
        $timestampEvent = mktime(14, 17, 7, 3, 17, 2026);
        $timestampNow = mktime(14, 47, 7, 3, 17, 2026);
        $rendered = (new TimeAgo($timestampEvent, $timestampNow))->render();

        $this->assertSame($html, $rendered);

        $timestampEventFloat = (float) $timestampEvent;
        $timestampNowFloat = (float) $timestampNow;
        $rendered2 = (new TimeAgo($timestampEventFloat, $timestampNowFloat))->render();

        $this->assertSame($html, $rendered2);
    }

    public function testRenderHasAttributes(): void
    {
        $eventTime = '2026-03-17 14:17:07';
        $now = '2026-03-17 14:47:07';
        $time = new DateTime($eventTime);
        $compareTime = new DateTime($now);
        $rendered = (new TimeAgo($time, $compareTime))->render();

        $this->assertStringContainsString('class="time-ago"', $rendered);
        $this->assertStringContainsString('data-relative-time="ago"', $rendered);
        $this->assertStringContainsString(sprintf('datetime="%s"', $eventTime), $rendered);
        $this->assertStringContainsString(sprintf('title="%s"', $eventTime), $rendered);
    }

    public function testRenderHasAgoLabel(): void
    {
        $eventTime = '2026-03-17 14:17:07';
        $now = '2026-03-17 14:47:07';
        $time = new DateTime($eventTime);
        $compareTime = new DateTime($now);
        $rendered = (new TimeAgo($time, $compareTime))->render();

        $this->assertStringContainsString('data-ago-label="0m 0s ago"', $rendered);

        $compareTime = new DateTime('2026-03-17 15:18:07');
        $rendered = (new TimeAgo($time, $compareTime))->render();

        $this->assertStringNotContainsString('data-ago-label=', $rendered);
    }

    public function testRenderUsesTimeHtmlTag(): void
    {
        $time = new DateTime('2026-03-17 14:17:07');
        $compareTime = new DateTime('2026-03-22 14:17:07');
        $rendered = (new TimeAgo($time, $compareTime))->render();


        $this->assertStringStartsWith('<time', $rendered);
        $this->assertStringEndsWith('</time>', $rendered);
    }

    public function testFormatWithSubHourTime(): void
    {
        $time = new DateTime('2026-03-17 14:17:07');
        $compareTime = new DateTime('2026-03-17 14:47:07');
        $rendered = (new TimeAgo($time, $compareTime))->render();

        $this->assertStringContainsString('>30m 0s ago<', $rendered);
    }

    public function testFormatWithHoursAgoSameDay(): void
    {
        $time = new DateTime('2026-03-17 14:17:07');
        $compareTime = new DateTime('2026-03-17 16:17:07');
        $rendered = (new TimeAgo($time, $compareTime))->render();

        $this->assertStringContainsString('>at 14:17<', $rendered);
    }

    public function testFormatWithDaysAgo(): void
    {
        $time = new DateTime('2026-03-17 14:17:07');
        $compareTime = new DateTime('2026-03-22 14:17:07');
        $rendered = (new TimeAgo($time, $compareTime))->render();

        $this->assertStringContainsString('>on Mar 17<', $rendered);
    }

    public function testFormatWithDaysAndHoursAgo(): void
    {
        $time = new DateTime('2026-03-17 14:17:07');
        $compareTime = new DateTime('2026-03-19 02:17:07');
        $rendered = (new TimeAgo($time, $compareTime))->render();

        $this->assertStringContainsString('>1d 12h ago<', $rendered);
    }

    public function testFormatCrossMidnightLessThanDayAgo(): void
    {
        $time = new DateTime('2026-03-17 14:17:07');
        $compareTime = new DateTime('2026-03-18 02:17:07');
        $rendered = (new TimeAgo($time, $compareTime))->render();

        $this->assertStringContainsString('>on Mar 17 14:17<', $rendered);
    }

    public function testFormatWithPreviousYear(): void
    {
        $time = new DateTime('2026-12-31 23:47:07');
        $compareTime3Days = new DateTime('2027-03-17 14:17:07');
        $rendered3Days = (new TimeAgo($time, $compareTime3Days))->render();

        $this->assertStringContainsString('>on 2026-12<', $rendered3Days);

        $compareTime1To3Days = new DateTime('2027-01-02 09:47:07');
        $rendered1To3Days = (new TimeAgo($time, $compareTime1To3Days))->render();

        $this->assertStringContainsString('>1d 10h ago<', $rendered1To3Days);

        $compareTimeHours = new DateTime('2027-01-01 09:47:07');
        $renderedHours = (new TimeAgo($time, $compareTimeHours))->render();

        $this->assertStringContainsString('>on Dec 31 23:47<', $renderedHours);

        $compareTimeMinutes = new DateTime('2027-01-01 00:17:07');
        $renderedMinutes = (new TimeAgo($time, $compareTimeMinutes))->render();

        $this->assertStringContainsString('>30m 0s ago<', $renderedMinutes);
    }

    public function testFormatWithFutureSubHourShowsAgoSuffix(): void
    {
        $time = new DateTime('2026-03-17 14:17:07');
        $compareTime = new DateTime('2026-03-17 13:47:07');
        $rendered = (new TimeAgo($time, $compareTime))->render();

        $this->assertStringContainsString('>30m 0s ago<', $rendered);
    }

    public function testRenderIgnoresFormatter(): void
    {
        $dateTime = new DateTime('2026-03-17 14:17:07');
        $compareTime = new DateTime('2026-03-17 15:17:07');
        $widget = new TimeAgo($dateTime, $compareTime);
        $formatter = IntlDateFormatter::create(locale: 'en', pattern: 'Y_M_d H:m');

        $rendered = $widget->setFormatter($formatter)->render();

        $this->assertStringNotContainsString('>2026_3_17 14:17<', $rendered);
    }
}
