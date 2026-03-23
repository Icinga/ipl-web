<?php

namespace ipl\Tests\Web\Widget;

use DateTime;
use IntlDateFormatter;
use ipl\I18n\NoopTranslator;
use ipl\I18n\StaticTranslator;
use ipl\Tests\Web\TestCase;
use ipl\Web\Widget\TimeSince;

class TimeSinceTest extends TestCase
{
    protected function setUp(): void
    {
        StaticTranslator::$instance = new NoopTranslator();
    }

    public function testConstructorAcceptsNullForCurrentTime(): void
    {
        $nowDateTime = new DateTime();
        $html = sprintf(
            '<time %s title="%2$s" datetime="%2$s">for 0m 0s</time>',
            'class="time-since" data-relative-time="since"',
            $nowDateTime->format('Y-m-d H:i:s'),
        );
        $renderedWithoutParams = (new TimeSince(null, null))->render();

        $this->assertSame($html, $renderedWithoutParams);

        $renderedWithParams = (new TimeSince($nowDateTime, $nowDateTime))->render();

        $this->assertSame($html, $renderedWithParams);
    }

    public function testConstructorAcceptsTimestamp(): void
    {
        $html = sprintf(
            '<time %s title="%2$s" datetime="%2$s">for 30m 0s</time>',
            'class="time-since" data-relative-time="since"',
            '2026-03-17 14:17:07',
        );
        $timestampEvent = mktime(14, 17, 7, 3, 17, 2026);
        $timestampNow = mktime(14, 47, 7, 3, 17, 2026);
        $rendered = (new TimeSince($timestampEvent, $timestampNow))->render();

        $this->assertSame($html, $rendered);

        $timestampEventFloat = (float) $timestampEvent;
        $timestampNowFloat = (float) $timestampNow;
        $rendered2 = (new TimeSince($timestampEventFloat, $timestampNowFloat))->render();

        $this->assertSame($html, $rendered2);
    }

    public function testRenderHasAttributes(): void
    {
        $eventTime = '2026-03-17 14:17:07';
        $now = '2026-03-17 14:47:07';
        $time = new DateTime($eventTime);
        $compareTime = new DateTime($now);
        $rendered = (new TimeSince($time, $compareTime))->render();

        $this->assertStringContainsString('class="time-since"', $rendered);
        $this->assertStringContainsString('data-relative-time="since"', $rendered);
        $this->assertStringContainsString(sprintf('datetime="%s"', $eventTime), $rendered);
        $this->assertStringContainsString(sprintf('title="%s"', $eventTime), $rendered);
    }

    public function testRenderHasAgoLabel(): void
    {
        $eventTime = '2026-03-17 14:17:07';
        $now = '2026-03-17 14:47:07';
        $time = new DateTime($eventTime);
        $compareTime = new DateTime($now);
        $rendered = (new TimeSince($time, $compareTime))->render();

        $this->assertStringNotContainsString('data-ago-label=', $rendered);

        $compareTime = new DateTime('2026-03-17 15:18:07');
        $rendered = (new TimeSince($time, $compareTime))->render();

        $this->assertStringNotContainsString('data-ago-label=', $rendered);
    }

    public function testRenderUsesTimeHtmlTag(): void
    {
        $time = new DateTime('2026-03-17 14:17:07');
        $compareTime = new DateTime('2026-03-22 14:17:07');
        $rendered = (new TimeSince($time, $compareTime))->render();


        $this->assertStringStartsWith('<time', $rendered);
        $this->assertStringEndsWith('</time>', $rendered);
    }

    public function testFormatWithSubHourTime(): void
    {
        $time = new DateTime('2026-03-17 14:17:07');
        $compareTime = new DateTime('2026-03-17 14:47:07');
        $rendered = (new TimeSince($time, $compareTime))->render();

        $this->assertStringContainsString('>for 30m 0s<', $rendered);
    }

    public function testFormatWithHoursAgoSameDay(): void
    {
        $time = new DateTime('2026-03-17 14:17:07');
        $compareTime = new DateTime('2026-03-17 16:17:07');
        $rendered = (new TimeSince($time, $compareTime))->render();

        $this->assertStringContainsString('>since 14:17<', $rendered);
    }

    public function testFormatWithDaysAgo(): void
    {
        $time = new DateTime('2026-03-17 14:17:07');
        $compareTime = new DateTime('2026-03-22 14:17:07');
        $rendered = (new TimeSince($time, $compareTime))->render();

        $this->assertStringContainsString('>since Mar 17<', $rendered);
    }

    public function testFormatWithDaysAndHoursAgo(): void
    {
        $time = new DateTime('2026-03-17 14:17:07');
        $compareTime = new DateTime('2026-03-19 02:17:07');
        $rendered = (new TimeSince($time, $compareTime))->render();

        $this->assertStringContainsString('>for 1d 12h<', $rendered);
    }

    public function testFormatCrossMidnightLessThanDayAgo(): void
    {
        $time = new DateTime('2026-03-17 14:17:07');
        $compareTime = new DateTime('2026-03-18 02:17:07');
        $rendered = (new TimeSince($time, $compareTime))->render();

        $this->assertStringContainsString('>since Mar 17 14:17<', $rendered);
    }

    public function testFormatWithPreviousYear(): void
    {
        $time = new DateTime('2026-12-31 23:47:07');
        $compareTime3Days = new DateTime('2027-03-17 14:17:07');
        $rendered3Days = (new TimeSince($time, $compareTime3Days))->render();

        $this->assertStringContainsString('>since 2026-12<', $rendered3Days);

        $compareTime1To3Days = new DateTime('2027-01-02 09:47:07');
        $rendered1To3Days = (new TimeSince($time, $compareTime1To3Days))->render();

        $this->assertStringContainsString('>for 1d 10h<', $rendered1To3Days);

        $compareTimeHours = new DateTime('2027-01-01 09:47:07');
        $renderedHours = (new TimeSince($time, $compareTimeHours))->render();

        $this->assertStringContainsString('>since Dec 31 23:47<', $renderedHours);

        $compareTimeMinutes = new DateTime('2027-01-01 00:17:07');
        $renderedMinutes = (new TimeSince($time, $compareTimeMinutes))->render();

        $this->assertStringContainsString('>for 30m 0s<', $renderedMinutes);
    }

    public function testFormatWithFutureSubHourShowsAgoSuffix(): void
    {
        $time = new DateTime('2026-03-17 14:17:07');
        $compareTime = new DateTime('2026-03-17 13:47:07');
        $rendered = (new TimeSince($time, $compareTime))->render();

        $this->assertStringContainsString('>for 30m 0s<', $rendered);
    }


    public function testRenderIgnoresFormatter(): void
    {
        $dateTime = new DateTime('2026-03-17 14:17:07');
        $compareTime = new DateTime('2026-03-17 15:17:07');
        $widget = new TimeSince($dateTime, $compareTime);
        $formatter = IntlDateFormatter::create(locale: 'en', pattern: 'Y_M_d H:m');

        $rendered = $widget->setFormatter($formatter)->render();

        $this->assertStringNotContainsString('>2026_3_17 14:17<', $rendered);
    }
}
