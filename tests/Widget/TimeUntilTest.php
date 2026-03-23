<?php

namespace ipl\Tests\Web\Widget;

use DateTime;
use IntlDateFormatter;
use ipl\I18n\NoopTranslator;
use ipl\I18n\StaticTranslator;
use ipl\Tests\Web\TestCase;
use ipl\Web\Widget\TimeUntil;

class TimeUntilTest extends TestCase
{
    protected function setUp(): void
    {
        StaticTranslator::$instance = new NoopTranslator();
    }

    public function testConstructorAcceptsNullForCurrentTime(): void
    {
        $nowDateTime = new DateTime();
        $html = sprintf(
            '<time %s title="%2$s" datetime="%2$s" data-ago-label="0m 0s ago">in 0m 0s</time>',
            'class="time-until" data-relative-time="until"',
            $nowDateTime->format('Y-m-d H:i:s'),
        );
        $renderedWithoutParams = (new TimeUntil(null, null))->render();

        $this->assertSame($html, $renderedWithoutParams);

        $renderedWithParams = (new TimeUntil($nowDateTime, $nowDateTime))->render();

        $this->assertSame($html, $renderedWithParams);
    }

    public function testConstructorAcceptsTimestamp(): void
    {
        $html = sprintf(
            '<time %s title="%2$s" datetime="%2$s" data-ago-label="0m 0s ago">in 30m 0s</time>',
            'class="time-until" data-relative-time="until"',
            '2026-03-17 14:17:07',
        );
        $timestampEvent = mktime(14, 17, 7, 3, 17, 2026);
        $timestampNow = mktime(13, 47, 7, 3, 17, 2026);
        $rendered = (new TimeUntil($timestampEvent, $timestampNow))->render();

        $this->assertSame($html, $rendered);

        $timestampEventFloat = (float) $timestampEvent;
        $timestampNowFloat = (float) $timestampNow;
        $rendered2 = (new TimeUntil($timestampEventFloat, $timestampNowFloat))->render();

        $this->assertSame($html, $rendered2);
    }

    public function testRenderHasAttributes(): void
    {
        $eventTime = '2026-03-17 14:17:07';
        $now = '2026-03-17 13:47:07';
        $time = new DateTime($eventTime);
        $compareTime = new DateTime($now);
        $rendered = (new TimeUntil($time, $compareTime))->render();

        $this->assertStringContainsString('class="time-until"', $rendered);
        $this->assertStringContainsString('data-relative-time="until"', $rendered);
        $this->assertStringContainsString(sprintf('datetime="%s"', $eventTime), $rendered);
        $this->assertStringContainsString(sprintf('title="%s"', $eventTime), $rendered);
    }

    public function testRenderHasAgoLabel(): void
    {
        $eventTime = '2026-03-17 14:17:07';
        $now = '2026-03-17 13:47:07';
        $time = new DateTime($eventTime);
        $compareTime = new DateTime($now);
        $rendered = (new TimeUntil($time, $compareTime))->render();

        $this->assertStringContainsString('data-ago-label="0m 0s ago"', $rendered);

        $compareTime = new DateTime('2026-03-17 15:18:07');
        $rendered = (new TimeUntil($time, $compareTime))->render();

        $this->assertStringNotContainsString('data-ago-label=', $rendered);
    }

    public function testRenderUsesTimeHtmlTag(): void
    {
        $time = new DateTime('2026-03-22 14:17:07');
        $compareTime = new DateTime('2026-03-17 14:17:07');
        $rendered = (new TimeUntil($time, $compareTime))->render();


        $this->assertStringStartsWith('<time', $rendered);
        $this->assertStringEndsWith('</time>', $rendered);
    }

    public function testFormatWithSubHourTime(): void
    {
        $time = new DateTime('2026-03-17 14:17:07');
        $compareTime = new DateTime('2026-03-17 13:47:07');
        $rendered = (new TimeUntil($time, $compareTime))->render();

        $this->assertStringContainsString('>in 30m 0s<', $rendered);
    }

    public function testFormatWithHoursAgoSameDay(): void
    {
        $time = new DateTime('2026-03-17 14:17:07');
        $compareTime = new DateTime('2026-03-17 12:17:07');
        $rendered = (new TimeUntil($time, $compareTime))->render();

        $this->assertStringContainsString('>at 14:17<', $rendered);
    }

    public function testFormatWithDaysAgo(): void
    {
        $time = new DateTime('2026-03-17 14:17:07');
        $compareTime = new DateTime('2026-03-12 14:17:07');
        $rendered = (new TimeUntil($time, $compareTime))->render();

        $this->assertStringContainsString('>on Mar 17<', $rendered);
    }

    public function testFormatWithDaysAndHoursAgo(): void
    {
        $time = new DateTime('2026-03-17 14:17:07');
        $compareTime = new DateTime('2026-03-16 02:17:07');
        $rendered = (new TimeUntil($time, $compareTime))->render();

        $this->assertStringContainsString('>in 1d 12h<', $rendered);
    }

    public function testFormatCrossMidnightLessThanDayAgo(): void
    {
        $time = new DateTime('2026-03-17 14:17:07');
        $compareTime = new DateTime('2026-03-16 22:17:07');
        $rendered = (new TimeUntil($time, $compareTime))->render();

        $this->assertStringContainsString('>on Mar 17 14:17<', $rendered);
    }

    public function testFormatWithPreviousYear(): void
    {
        $time = new DateTime('2027-01-01 00:17:07');
        $compareTime = new DateTime('2026-03-17 14:17:07');
        $rendered = (new TimeUntil($time, $compareTime))->render();

        $this->assertStringContainsString('>on 2027-01<', $rendered);

        $compareTime = new DateTime('2026-12-30 14:17:07');
        $rendered = (new TimeUntil($time, $compareTime))->render();

        $this->assertStringContainsString('>in 1d 10h<', $rendered);

        $compareTime = new DateTime('2026-12-31 14:17:07');
        $rendered = (new TimeUntil($time, $compareTime))->render();

        $this->assertStringContainsString('>on Jan 1 00:17<', $rendered);

        $compareTime = new DateTime('2026-12-31 23:47:07');
        $rendered = (new TimeUntil($time, $compareTime))->render();

        $this->assertStringContainsString('>in 30m 0s<', $rendered);
    }

    public function testFormatWithFutureSubHourShowsAgoSuffix(): void
    {
        $time = new DateTime('2026-03-17 14:17:07');
        $compareTime = new DateTime('2026-03-17 14:47:07');
        $rendered = (new TimeUntil($time, $compareTime))->render();

        $this->assertStringContainsString('>in -30m 0s<', $rendered);
    }

    public function testRenderIgnoresFormatter(): void
    {
        $dateTime = new DateTime('2026-03-17 15:17:07');
        $compareTime = new DateTime('2026-03-17 14:17:07');
        $widget = new TimeUntil($dateTime, $compareTime);
        $formatter = IntlDateFormatter::create(locale: 'en', pattern: 'Y_M_d H:m');

        $rendered = $widget->setFormatter($formatter)->render();

        $this->assertStringNotContainsString('>2026_3_17 14:17<', $rendered);
    }
}
