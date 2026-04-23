<?php

namespace ipl\Tests\Web\Widget;

use DateTime;
use IntlDateFormatter;
use ipl\I18n\NoopTranslator;
use ipl\I18n\StaticTranslator;
use ipl\Html\Test\TestCase;
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
            '<time class="time-until" data-relative-time="until"'
            . ' title="%1$s" datetime="%1$s" data-ago-label="0m 0s ago">in 0m 0s</time>',
            $nowDateTime->format('Y-m-d H:i:s'),
        );

        $this->assertHtml($html, new TimeUntil(null, null));
        $this->assertHtml($html, new TimeUntil($nowDateTime, $nowDateTime));
    }

    public function testConstructorAcceptsTimestamp(): void
    {
        $html = <<<'HTML'
<time class="time-until" data-relative-time="until"
      title="2026-03-17 14:17:07" datetime="2026-03-17 14:17:07"
      data-ago-label="0m 0s ago">in 30m 0s</time>
HTML;

        $timestampEvent = mktime(14, 17, 7, 3, 17, 2026);
        $timestampNow = new DateTime('2026-03-17 13:47:7');

        $this->assertHtml($html, new TimeUntil($timestampEvent, $timestampNow));
        $this->assertHtml($html, new TimeUntil((float) $timestampEvent, $timestampNow));
    }

    public function testFormatWithSubHourTime(): void
    {
        $html = <<<'HTML'
<time class="time-until" data-relative-time="until"
      title="2026-03-17 14:17:07" datetime="2026-03-17 14:17:07"
      data-ago-label="0m 0s ago">in 30m 0s</time>
HTML;

        $this->assertHtml(
            $html,
            new TimeUntil(new DateTime('2026-03-17 14:17:07'), new DateTime('2026-03-17 13:47:07'))
        );
    }

    public function testFormatWithHoursAgoSameDay(): void
    {
        $html = <<<'HTML'
<time class="time-until" data-relative-time="until"
      title="2026-03-17 14:17:07" datetime="2026-03-17 14:17:07">at 14:17</time>
HTML;

        $this->assertHtml(
            $html,
            new TimeUntil(new DateTime('2026-03-17 14:17:07'), new DateTime('2026-03-17 12:17:07'))
        );
    }

    public function testFormatWithDaysAgo(): void
    {
        $html = <<<'HTML'
<time class="time-until" data-relative-time="until"
      title="2026-03-17 14:17:07" datetime="2026-03-17 14:17:07">on Mar 17</time>
HTML;

        $this->assertHtml(
            $html,
            new TimeUntil(new DateTime('2026-03-17 14:17:07'), new DateTime('2026-03-12 14:17:07'))
        );
    }

    public function testFormatWithDaysAndHoursAgo(): void
    {
        $html = <<<'HTML'
<time class="time-until" data-relative-time="until"
      title="2026-03-17 14:17:07" datetime="2026-03-17 14:17:07">in 1d 12h</time>
HTML;

        $this->assertHtml(
            $html,
            new TimeUntil(new DateTime('2026-03-17 14:17:07'), new DateTime('2026-03-16 02:17:07'))
        );
    }

    public function testFormatCrossMidnightLessThanDayAgo(): void
    {
        $html = <<<'HTML'
<time class="time-until" data-relative-time="until"
      title="2026-03-17 14:17:07" datetime="2026-03-17 14:17:07">on Mar 17 14:17</time>
HTML;

        $this->assertHtml(
            $html,
            new TimeUntil(new DateTime('2026-03-17 14:17:07'), new DateTime('2026-03-16 22:17:07'))
        );
    }

    public function testFormatWithPreviousYear(): void
    {
        $eventTime = new DateTime('2027-01-01 00:17:07');

        $this->assertHtml(
            '<time class="time-until" data-relative-time="until"'
            . ' title="2027-01-01 00:17:07" datetime="2027-01-01 00:17:07">on 2027-01</time>',
            new TimeUntil($eventTime, new DateTime('2026-03-17 14:17:07'))
        );

        $this->assertHtml(
            '<time class="time-until" data-relative-time="until"'
            . ' title="2027-01-01 00:17:07" datetime="2027-01-01 00:17:07">in 1d 10h</time>',
            new TimeUntil($eventTime, new DateTime('2026-12-30 14:17:07'))
        );

        $this->assertHtml(
            '<time class="time-until" data-relative-time="until"'
            . ' title="2027-01-01 00:17:07" datetime="2027-01-01 00:17:07">on Jan 1 00:17</time>',
            new TimeUntil($eventTime, new DateTime('2026-12-31 14:17:07'))
        );

        $this->assertHtml(
            '<time class="time-until" data-ago-label="0m 0s ago" data-relative-time="until"'
            . ' title="2027-01-01 00:17:07" datetime="2027-01-01 00:17:07">in 30m 0s</time>',
            new TimeUntil($eventTime, new DateTime('2026-12-31 23:47:07'))
        );
    }

    public function testFormatWithFutureSubHourShowsAgoSuffix(): void
    {
        $html = <<<'HTML'
<time class="time-until" data-ago-label="0m 0s ago" data-relative-time="until"
      title="2026-03-17 14:17:07" datetime="2026-03-17 14:17:07">in -30m 0s</time>
HTML;

        $this->assertHtml(
            $html,
            new TimeUntil(new DateTime('2026-03-17 14:17:07'), new DateTime('2026-03-17 14:47:07'))
        );
    }

    public function testFormatWithPastDaysAndHoursNegatesTime(): void
    {
        $html = <<<'HTML'
<time class="time-until" data-relative-time="until"
      title="2026-03-17 14:17:07" datetime="2026-03-17 14:17:07">in -1d 12h</time>
HTML;

        $this->assertHtml(
            $html,
            new TimeUntil(new DateTime('2026-03-17 14:17:07'), new DateTime('2026-03-19 02:17:07'))
        );
    }

    public function testRenderIgnoresFormatter(): void
    {
        $html = <<<'HTML'
<time class="time-until" data-relative-time="until"
      title="2026-03-17 15:17:07" datetime="2026-03-17 15:17:07">at 15:17</time>
HTML;

        $widget = new TimeUntil(new DateTime('2026-03-17 15:17:07'), new DateTime('2026-03-17 14:17:07'));
        $formatter = IntlDateFormatter::create(locale: 'en', pattern: 'Y_M_d H:m');

        $this->assertHtml($html, $widget->setFormatter($formatter));
    }
}
