<?php

namespace ipl\Tests\Web\Widget;

use DateTime;
use IntlDateFormatter;
use ipl\I18n\NoopTranslator;
use ipl\I18n\StaticTranslator;
use ipl\Html\Test\TestCase;
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
            '<time class="time-since" data-relative-time="since"'
            . ' title="%1$s" datetime="%1$s">for 0m 0s</time>',
            $nowDateTime->format('Y-m-d H:i:s'),
        );

        $this->assertHtml($html, new TimeSince(null, null));
        $this->assertHtml($html, new TimeSince($nowDateTime, $nowDateTime));
    }

    public function testConstructorAcceptsTimestamp(): void
    {
        $html = <<<'HTML'
        <time class="time-since" data-relative-time="since"
              title="2026-03-17 14:17:07" datetime="2026-03-17 14:17:07">for 30m 0s</time>
        HTML;
        $timestampEvent = mktime(14, 17, 7, 3, 17, 2026);
        $timestampNow = mktime(14, 47, 7, 3, 17, 2026);

        $this->assertHtml($html, new TimeSince($timestampEvent, $timestampNow));
        $this->assertHtml($html, new TimeSince((float) $timestampEvent, (float) $timestampNow));
    }

    public function testFormatWithSubHourTime(): void
    {
        $html = <<<'HTML'
        <time class="time-since" data-relative-time="since"
              title="2026-03-17 14:17:07" datetime="2026-03-17 14:17:07">for 30m 0s</time>
        HTML;

        $this->assertHtml(
            $html,
            new TimeSince(new DateTime('2026-03-17 14:17:07'), new DateTime('2026-03-17 14:47:07'))
        );
    }

    public function testFormatWithHoursAgoSameDay(): void
    {
        $html = <<<'HTML'
        <time class="time-since" data-relative-time="since"
              title="2026-03-17 14:17:07" datetime="2026-03-17 14:17:07">since 14:17</time>
        HTML;

        $this->assertHtml(
            $html,
            new TimeSince(new DateTime('2026-03-17 14:17:07'), new DateTime('2026-03-17 16:17:07'))
        );
    }

    public function testFormatWithDaysAgo(): void
    {
        $html = <<<'HTML'
        <time class="time-since" data-relative-time="since"
              title="2026-03-17 14:17:07" datetime="2026-03-17 14:17:07">since Mar 17</time>
        HTML;

        $this->assertHtml(
            $html,
            new TimeSince(new DateTime('2026-03-17 14:17:07'), new DateTime('2026-03-22 14:17:07'))
        );
    }

    public function testFormatWithDaysAndHoursAgo(): void
    {
        $html = <<<'HTML'
        <time class="time-since" data-relative-time="since"
              title="2026-03-17 14:17:07" datetime="2026-03-17 14:17:07">for 1d 12h</time>
        HTML;

        $this->assertHtml(
            $html,
            new TimeSince(new DateTime('2026-03-17 14:17:07'), new DateTime('2026-03-19 02:17:07'))
        );
    }

    public function testFormatCrossMidnightLessThanDayAgo(): void
    {
        $html = <<<'HTML'
        <time class="time-since" data-relative-time="since"
              title="2026-03-17 14:17:07" datetime="2026-03-17 14:17:07">since Mar 17 14:17</time>
        HTML;

        $this->assertHtml(
            $html,
            new TimeSince(new DateTime('2026-03-17 14:17:07'), new DateTime('2026-03-18 02:17:07'))
        );
    }

    public function testFormatWithPreviousYear(): void
    {
        $eventTime = new DateTime('2026-12-31 23:47:07');

        $this->assertHtml(
            '<time class="time-since" data-relative-time="since"'
            . ' title="2026-12-31 23:47:07" datetime="2026-12-31 23:47:07">since 2026-12</time>',
            new TimeSince($eventTime, new DateTime('2027-03-17 14:17:07'))
        );

        $this->assertHtml(
            '<time class="time-since" data-relative-time="since"'
            . ' title="2026-12-31 23:47:07" datetime="2026-12-31 23:47:07">for 1d 10h</time>',
            new TimeSince($eventTime, new DateTime('2027-01-02 09:47:07'))
        );

        $this->assertHtml(
            '<time class="time-since" data-relative-time="since"'
            . ' title="2026-12-31 23:47:07" datetime="2026-12-31 23:47:07">since Dec 31 23:47</time>',
            new TimeSince($eventTime, new DateTime('2027-01-01 09:47:07'))
        );

        $this->assertHtml(
            '<time class="time-since" data-relative-time="since"'
            . ' title="2026-12-31 23:47:07" datetime="2026-12-31 23:47:07">for 30m 0s</time>',
            new TimeSince($eventTime, new DateTime('2027-01-01 00:17:07'))
        );
    }

    public function testFormatWithFutureSubHourShowsAgoSuffix(): void
    {
        $html = <<<'HTML'
        <time class="time-since" data-relative-time="since"
              title="2026-03-17 14:17:07" datetime="2026-03-17 14:17:07">for 30m 0s</time>
        HTML;

        $this->assertHtml(
            $html,
            new TimeSince(new DateTime('2026-03-17 14:17:07'), new DateTime('2026-03-17 13:47:07'))
        );
    }

    public function testRenderIgnoresFormatter(): void
    {
        $html = <<<'HTML'
        <time class="time-since" data-relative-time="since"
              title="2026-03-17 14:17:07" datetime="2026-03-17 14:17:07">since 14:17</time>
        HTML;
        $widget = new TimeSince(new DateTime('2026-03-17 14:17:07'), new DateTime('2026-03-17 15:17:07'));
        $formatter = IntlDateFormatter::create(locale: 'en', pattern: 'Y_M_d H:m');

        $this->assertHtml($html, $widget->setFormatter($formatter));
    }
}