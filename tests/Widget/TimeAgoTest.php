<?php

namespace ipl\Tests\Web\Widget;

use DateTime;
use IntlDateFormatter;
use ipl\I18n\NoopTranslator;
use ipl\I18n\StaticTranslator;
use ipl\Html\Test\TestCase;
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
            '<time class="time-ago" data-relative-time="ago" title="%1$s" datetime="%1$s">0m 0s ago</time>',
            $nowDateTime->format('Y-m-d H:i:s'),
        );

        $this->assertHtml($html, new TimeAgo(null, null));
        $this->assertHtml($html, new TimeAgo($nowDateTime, $nowDateTime));
    }

    public function testConstructorAcceptsTimestamp(): void
    {
        $html = <<<'HTML'
<time class="time-ago" data-relative-time="ago"
      title="2026-03-17 14:17:07" datetime="2026-03-17 14:17:07">30m 0s ago</time>
HTML;

        $timestampEvent = mktime(14, 17, 7, 3, 17, 2026);
        $timestampNow = new DateTime('2026-03-17 14:47:7');

        $this->assertHtml($html, new TimeAgo($timestampEvent, $timestampNow));
        $this->assertHtml($html, new TimeAgo((float) $timestampEvent, $timestampNow));
    }

    public function testFormatWithSubHourTime(): void
    {
        $html = <<<'HTML'
<time class="time-ago" data-relative-time="ago"
      title="2026-03-17 14:17:07" datetime="2026-03-17 14:17:07">30m 0s ago</time>
HTML;

        $this->assertHtml(
            $html,
            new TimeAgo(new DateTime('2026-03-17 14:17:07'), new DateTime('2026-03-17 14:47:07'))
        );
    }

    public function testFormatWithHoursAgoSameDay(): void
    {
        $html = <<<'HTML'
<time class="time-ago" data-relative-time="ago"
      title="2026-03-17 14:17:07" datetime="2026-03-17 14:17:07">at 14:17</time>
HTML;

        $this->assertHtml(
            $html,
            new TimeAgo(new DateTime('2026-03-17 14:17:07'), new DateTime('2026-03-17 16:17:07'))
        );
    }

    public function testFormatWithDaysAgo(): void
    {
        $html = <<<'HTML'
<time class="time-ago" data-relative-time="ago"
      title="2026-03-17 14:17:07" datetime="2026-03-17 14:17:07">on Mar 17</time>
HTML;

        $this->assertHtml(
            $html,
            new TimeAgo(new DateTime('2026-03-17 14:17:07'), new DateTime('2026-03-22 14:17:07'))
        );
    }

    public function testFormatWithDaysAndHoursAgo(): void
    {
        $html = <<<'HTML'
<time class="time-ago" data-relative-time="ago"
      title="2026-03-17 14:17:07" datetime="2026-03-17 14:17:07">1d 12h ago</time>
HTML;

        $this->assertHtml(
            $html,
            new TimeAgo(new DateTime('2026-03-17 14:17:07'), new DateTime('2026-03-19 02:17:07'))
        );
    }

    public function testFormatCrossMidnightLessThanDayAgo(): void
    {
        $html = <<<'HTML'
<time class="time-ago" data-relative-time="ago"
      title="2026-03-17 14:17:07" datetime="2026-03-17 14:17:07">on Mar 17 14:17</time>
HTML;

        $this->assertHtml(
            $html,
            new TimeAgo(new DateTime('2026-03-17 14:17:07'), new DateTime('2026-03-18 02:17:07'))
        );
    }

    public function testFormatWithPreviousYear(): void
    {
        $eventTime = new DateTime('2026-12-31 23:47:07');

        $this->assertHtml(
            '<time class="time-ago" data-relative-time="ago"'
            . ' title="2026-12-31 23:47:07" datetime="2026-12-31 23:47:07">on 2026-12</time>',
            new TimeAgo($eventTime, new DateTime('2027-03-17 14:17:07'))
        );

        $this->assertHtml(
            '<time class="time-ago" data-relative-time="ago"'
            . ' title="2026-12-31 23:47:07" datetime="2026-12-31 23:47:07">1d 10h ago</time>',
            new TimeAgo($eventTime, new DateTime('2027-01-02 09:47:07'))
        );

        $this->assertHtml(
            '<time class="time-ago" data-relative-time="ago"'
            . ' title="2026-12-31 23:47:07" datetime="2026-12-31 23:47:07">on Dec 31 23:47</time>',
            new TimeAgo($eventTime, new DateTime('2027-01-01 09:47:07'))
        );

        $this->assertHtml(
            '<time class="time-ago" data-relative-time="ago"'
            . ' title="2026-12-31 23:47:07" datetime="2026-12-31 23:47:07">30m 0s ago</time>',
            new TimeAgo($eventTime, new DateTime('2027-01-01 00:17:07'))
        );
    }

    public function testFormatWithFutureSubHourShowsAgoSuffix(): void
    {
        $html = <<<'HTML'
<time class="time-ago" data-relative-time="ago"
      title="2026-03-17 14:17:07" datetime="2026-03-17 14:17:07">30m 0s ago</time>
HTML;

        $this->assertHtml(
            $html,
            new TimeAgo(new DateTime('2026-03-17 14:17:07'), new DateTime('2026-03-17 13:47:07'))
        );
    }

    public function testRenderIgnoresFormatter(): void
    {
        $html = <<<'HTML'
<time class="time-ago" data-relative-time="ago"
      title="2026-03-17 14:17:07" datetime="2026-03-17 14:17:07">at 14:17</time>
HTML;

        $widget = new TimeAgo(new DateTime('2026-03-17 14:17:07'), new DateTime('2026-03-17 15:17:07'));
        $formatter = IntlDateFormatter::create(locale: 'en', pattern: 'Y_M_d H:m');

        $this->assertHtml($html, $widget->setFormatter($formatter));
    }
}
