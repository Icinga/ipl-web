<?php

namespace ipl\Tests\Web;

use DateTime;
use ipl\I18n\NoopTranslator;
use ipl\I18n\StaticTranslator;
use ipl\Scheduler\Cron;
use ipl\Scheduler\OneOff;
use ipl\Scheduler\RRule;
use ipl\Web\FormElement\ScheduleElement;

class ScheduleElementTest extends TestCase
{
    protected function setUp(): void
    {
        StaticTranslator::$instance = new NoopTranslator();
    }

    protected function assembleElement(array $options): ScheduleElement
    {
        $element = new ScheduleElement('test', $options);
        // ScheduleElement#getValue won't return an instance of Frequency if it's not validated
        $element->ensureAssembled()->validate();

        // Remove the recurrences preview. It randomizes the HTML and isn't subject to test
        if ($element->hasElement('schedule-recurrences')) {
            $element->remove($element->getElement('schedule-recurrences'));
        }

        $element->render(); // Forces also assembly of any content

        return $element;
    }

    public function testOneOffFrequency()
    {
        $datetime = new DateTime('2023-02-07T15:17:07');
        $element = $this->assembleElement(['value' => new OneOff($datetime)]);

        $this->assertEquals($datetime, $element->getValue('start'));
        $this->assertNull($element->getValue('end'));
        $this->assertSame('none', $element->getValue('frequency'));

        $this->assertEquals(new OneOff($datetime), $element->getValue());
    }

    public function testMinutelyFrequency()
    {
        $start = new DateTime('2023-02-07T15:17:07');
        $frequency = RRule::fromFrequency(RRule::MINUTELY)->startAt($start);
        $element = $this->assembleElement(['value' => $frequency]);

        $this->assertEquals($start, $element->getValue('start'));
        $this->assertNull($element->getValue('end'));
        $this->assertSame(RRule::MINUTELY, $element->getValue('frequency'));

        $this->assertEquals($frequency, $element->getValue());
    }

    public function testMinutelyFrequencyWithEnd()
    {
        $start = new DateTime('2023-02-07T15:17:07');
        $end = new DateTime('2023-02-10T18:00:00');
        $frequency = RRule::fromFrequency(RRule::MINUTELY)
            ->startAt($start)
            ->endAt($end);

        $element = $this->assembleElement(['value' => $frequency]);

        $this->assertEquals($start, $element->getValue('start'));
        $this->assertEquals($end, $element->getValue('end'));
        $this->assertSame(RRule::MINUTELY, $element->getValue('frequency'));

        $this->assertEquals($frequency, $element->getValue());
    }

    public function testHourlyFrequency()
    {
        $start = new DateTime('2023-02-07T15:17:08');
        $frequency = RRule::fromFrequency(RRule::HOURLY)->startAt($start);
        $element = $this->assembleElement(['value' => $frequency]);

        $this->assertEquals($start, $element->getValue('start'));
        $this->assertNull($element->getValue('end'));
        $this->assertSame(RRule::HOURLY, $element->getValue('frequency'));

        $this->assertEquals($frequency, $element->getValue());
    }

    public function testHourlyFrequencyWithEnd()
    {
        $start = new DateTime('2023-02-07T15:17:07');
        $end = new DateTime('2023-02-10T18:00:00');
        $frequency = RRule::fromFrequency(RRule::HOURLY)
            ->startAt($start)
            ->endAt($end);

        $element = $this->assembleElement(['value' => $frequency]);

        $this->assertEquals($start, $element->getValue('start'));
        $this->assertEquals($end, $element->getValue('end'));
        $this->assertSame(RRule::HOURLY, $element->getValue('frequency'));

        $this->assertEquals($frequency, $element->getValue());
    }

    public function testDailyFrequency()
    {
        $start = new DateTime('2023-02-07T15:17:07');
        $frequency = RRule::fromFrequency(RRule::DAILY)->startAt($start);
        $element = $this->assembleElement(['value' => $frequency]);

        $this->assertEquals($start, $element->getValue('start'));
        $this->assertNull($element->getValue('end'));
        $this->assertSame(RRule::DAILY, $element->getValue('frequency'));

        $this->assertEquals($frequency, $element->getValue());
    }

    public function testDailyFrequencyWithEnd()
    {
        $start = new DateTime('2023-02-07T15:17:07');
        $end = new DateTime('2023-02-10T18:00:00');
        $frequency = RRule::fromFrequency(RRule::DAILY)
            ->startAt($start)
            ->endAt($end);
        $element = $this->assembleElement(['value' => $frequency]);

        $this->assertEquals($start, $element->getValue('start'));
        $this->assertEquals($end, $element->getValue('end'));
        $this->assertSame(RRule::DAILY, $element->getValue('frequency'));

        $this->assertEquals($frequency, $element->getValue());
    }

    public function testWeeklyFrequency()
    {
        $start = new DateTime('2023-02-07T15:17:07');
        $frequency = RRule::fromFrequency(RRule::WEEKLY)->startAt($start);
        $element = $this->assembleElement(['value' => $frequency]);

        $this->assertEquals($start, $element->getValue('start'));
        $this->assertNull($element->getValue('end'));
        $this->assertSame(RRule::WEEKLY, $element->getValue('frequency'));

        $this->assertEquals($frequency, $element->getValue());
    }

    public function testWeeklyFrequencyWithEnd()
    {
        $start = new DateTime('2023-02-07T15:17:07');
        $end = new DateTime('2023-02-10T18:00:00');
        $frequency = RRule::fromFrequency(RRule::WEEKLY)
            ->startAt($start)
            ->endAt($end);

        $element = $this->assembleElement(['value' => $frequency]);

        $this->assertEquals($start, $element->getValue('start'));
        $this->assertEquals($end, $element->getValue('end'));
        $this->assertSame(RRule::WEEKLY, $element->getValue('frequency'));

        $this->assertEquals($frequency, $element->getValue());
    }

    public function testMonthlyFrequency()
    {
        $start = new DateTime('2023-02-07T15:17:07');
        $frequency = RRule::fromFrequency(RRule::MONTHLY)->startAt($start);
        $element = $this->assembleElement(['value' => $frequency]);

        $this->assertEquals($start, $element->getValue('start'));
        $this->assertNull($element->getValue('end'));
        $this->assertSame(RRule::MONTHLY, $element->getValue('frequency'));

        $this->assertEquals($frequency, $element->getValue());
    }

    public function testMonthlyFrequencyWithEnd()
    {
        $start = new DateTime('2023-02-07T15:17:07');
        $end = new DateTime('2023-02-10T18:00:00');
        $frequency = RRule::fromFrequency(RRule::MONTHLY)
            ->startAt($start)
            ->endAt($end);

        $element = $this->assembleElement(['value' => $frequency]);

        $this->assertEquals($start, $element->getValue('start'));
        $this->assertEquals($end, $element->getValue('end'));
        $this->assertSame(RRule::MONTHLY, $element->getValue('frequency'));

        $this->assertEquals($frequency, $element->getValue());
    }

    public function testQuarterlyFrequency()
    {
        $start = new DateTime('2023-02-07T15:17:07');
        $frequency = RRule::fromFrequency(RRule::QUARTERLY)->startAt($start);
        $element = $this->assembleElement(['value' => $frequency]);

        $this->assertEquals($start, $element->getValue('start'));
        $this->assertNull($element->getValue('end'));
        $this->assertSame(RRule::QUARTERLY, $element->getValue('frequency'));

        $this->assertEquals($frequency, $element->getValue());
    }

    public function testQuarterlyFrequencyWithEnd()
    {
        $start = new DateTime('2023-02-07T15:17:07');
        $end = new DateTime('2023-02-10T18:00:00');
        $frequency = RRule::fromFrequency(RRule::QUARTERLY)
            ->startAt($start)
            ->endAt($end);

        $element = $this->assembleElement(['value' => $frequency]);

        $this->assertEquals($start, $element->getValue('start'));
        $this->assertEquals($end, $element->getValue('end'));
        $this->assertSame(RRule::QUARTERLY, $element->getValue('frequency'));

        $this->assertEquals($frequency, $element->getValue());
    }

    public function testAnnuallyFrequency()
    {
        $start = new DateTime('2023-02-07T15:17:07');
        $frequency = RRule::fromFrequency(RRule::YEARLY)->startAt($start);
        $element = $this->assembleElement(['value' => $frequency]);

        $this->assertEquals($start, $element->getValue('start'));
        $this->assertNull($element->getValue('end'));
        $this->assertSame(RRule::YEARLY, $element->getValue('frequency'));

        $this->assertEquals($frequency, $element->getValue());
    }

    public function testAnnuallyFrequencyWithEnd()
    {
        $start = new DateTime('2023-02-07T15:17:07');
        $end = new DateTime('2023-02-10T18:00:00');
        $frequency = RRule::fromFrequency(RRule::YEARLY)
            ->startAt($start)
            ->endAt($end);

        $element = $this->assembleElement(['value' => $frequency]);

        $this->assertEquals($start, $element->getValue('start'));
        $this->assertEquals($end, $element->getValue('end'));
        $this->assertSame(RRule::YEARLY, $element->getValue('frequency'));

        $this->assertEquals($frequency, $element->getValue());
    }

    public function testCronFrequency()
    {
        $start = new DateTime('2023-02-07T15:17:07');
        $cron = (new Cron('5 4 * * *'))->startAt($start);
        $element = $this->assembleElement(['value' => $cron]);

        $this->assertEquals($start, $element->getValue('start'));
        $this->assertNull($element->getValue('end'));
        $this->assertSame('cron_expr', $element->getValue('frequency'));
        $this->assertSame($cron->getExpression(), $element->getValue('cron_expression'));

        $this->assertEquals($cron, $element->getValue());
    }

    public function testCronFrequencyWithEnd()
    {
        $start = new DateTime('2023-02-07T15:17:07');
        $end = new DateTime('2023-02-10T18:00:00');
        $cron = (new Cron('0 22 * * 1-5'))
            ->startAt($start)
            ->endAt($end);

        $element = $this->assembleElement(['value' => $cron]);

        $this->assertEquals($start, $element->getValue('start'));
        $this->assertEquals($end, $element->getValue('end'));
        $this->assertSame('cron_expr', $element->getValue('frequency'));
        $this->assertSame($cron->getExpression(), $element->getValue('cron_expression'));

        $this->assertEquals($cron, $element->getValue());
    }

    public function testCustomDailyFrequency()
    {
        $start = new DateTime('2023-02-07T15:17:07');
        $frequency = (new RRule('FREQ=DAILY;INTERVAL=1'))->startAt($start);
        $element = $this->assembleElement(['value' => $frequency]);

        $this->assertEquals($start, $element->getValue('start'));
        $this->assertNull($element->getValue('end'));
        $this->assertSame('custom', $element->getValue('frequency'));
        $this->assertSame(RRule::DAILY, $element->getValue('custom-frequency'));
        $this->assertSame(1, $element->getValue('interval'));

        $this->assertEquals($frequency, $element->getValue());
    }

    public function testCustomDailyFrequencyWithEnd()
    {
        $start = new DateTime('2023-02-07T15:17:07');
        $end = new DateTime('2023-02-10T18:00:00');
        $frequency = (new RRule('FREQ=DAILY;INTERVAL=1'))
            ->startAt($start)
            ->endAt($end);

        $element = $this->assembleElement(['value' => $frequency]);

        $this->assertEquals($start, $element->getValue('start'));
        $this->assertEquals($end, $element->getValue('end'));
        $this->assertSame('custom', $element->getValue('frequency'));
        $this->assertSame(RRule::DAILY, $element->getValue('custom-frequency'));
        $this->assertSame(1, $element->getValue('interval'));

        $this->assertEquals($frequency, $element->getValue());
    }

    public function testCustomWeeklyFrequency()
    {
        $start = new DateTime('2023-02-08T15:17:07');
        $frequency = (new RRule('FREQ=WEEKLY;INTERVAL=4;BYDAY=WE'))->startAt($start);
        $element = $this->assembleElement(['value' => $frequency]);

        $this->assertEquals($start, $element->getValue('start'));
        $this->assertNull($element->getValue('end'));
        $this->assertSame('custom', $element->getValue('frequency'));
        $this->assertSame(RRule::WEEKLY, $element->getValue('custom-frequency'));
        $this->assertSame(4, $element->getValue('interval'));
        $this->assertSame([
            'MO' => 'n',
            'TU' => 'n',
            'WE' => 'y',
            'TH' => 'n',
            'FR' => 'n',
            'SA' => 'n',
            'SU' => 'n'
        ], $element->getValue('weekly-fields'));

        $this->assertEquals($frequency, $element->getValue());
    }

    public function testCustomWeeklyFrequencyWithEnd()
    {
        $start = new DateTime('2023-02-08T15:17:07');
        $end = new DateTime('2023-02-10T18:00:00');
        $frequency = (new RRule('FREQ=WEEKLY;INTERVAL=4;BYDAY=WE'))
            ->startAt($start)
            ->endAt($end);

        $element = $this->assembleElement(['value' => $frequency]);

        $this->assertEquals($start, $element->getValue('start'));
        $this->assertEquals($end, $element->getValue('end'));
        $this->assertSame('custom', $element->getValue('frequency'));
        $this->assertSame(RRule::WEEKLY, $element->getValue('custom-frequency'));
        $this->assertSame(4, $element->getValue('interval'));
        $this->assertSame([
            'MO' => 'n',
            'TU' => 'n',
            'WE' => 'y',
            'TH' => 'n',
            'FR' => 'n',
            'SA' => 'n',
            'SU' => 'n'
        ], $element->getValue('weekly-fields'));

        $this->assertEquals($frequency, $element->getValue());
    }

    public function testCustomMonthlyFrequency()
    {
        $start = new DateTime('2023-02-08T15:17:07');
        $frequency = (new RRule('FREQ=MONTHLY;INTERVAL=1;BYMONTHDAY=8,17,18,27'))->startAt($start);
        $element = $this->assembleElement(['value' => $frequency]);

        $this->assertEquals($start, $element->getValue('start'));
        $this->assertNull($element->getValue('end'));
        $this->assertSame('custom', $element->getValue('frequency'));
        $this->assertSame(RRule::MONTHLY, $element->getValue('custom-frequency'));
        $this->assertSame(1, $element->getValue('interval'));
        $this->assertSame([
            'runsOn'  => 'each',
            'day1'    => 'n',
            'day2'    => 'n',
            'day3'    => 'n',
            'day4'    => 'n',
            'day5'    => 'n',
            'day6'    => 'n',
            'day7'    => 'n',
            'day8'    => 'y',
            'day9'    => 'n',
            'day10'   => 'n',
            'day11'   => 'n',
            'day12'   => 'n',
            'day13'   => 'n',
            'day14'   => 'n',
            'day15'   => 'n',
            'day16'   => 'n',
            'day17'   => 'y',
            'day18'   => 'y',
            'day19'   => 'n',
            'day20'   => 'n',
            'day21'   => 'n',
            'day22'   => 'n',
            'day23'   => 'n',
            'day24'   => 'n',
            'day25'   => 'n',
            'day26'   => 'n',
            'day27'   => 'y',
            'day28'   => 'n',
            'ordinal' => 'first', // Not really of interest, as disabled, but it's returned anyway
            'day'     => 'day' // Not really of interest, as disabled, but it's returned anyway
        ], $element->getValue('monthly-fields'));

        $this->assertEquals($frequency, $element->getValue());
    }

    public function testCustomMonthlyFrequencyWithEnd()
    {
        $start = new DateTime('2023-02-08T15:17:07');
        $end = new DateTime('2023-02-10T18:00:00');
        $frequency = (new RRule('FREQ=MONTHLY;INTERVAL=1;BYMONTHDAY=8,17,18,27'))
            ->startAt($start)
            ->endAt($end);

        $element = $this->assembleElement(['value' => $frequency]);

        $this->assertEquals($start, $element->getValue('start'));
        $this->assertEquals($end, $element->getValue('end'));
        $this->assertSame('custom', $element->getValue('frequency'));
        $this->assertSame(RRule::MONTHLY, $element->getValue('custom-frequency'));
        $this->assertSame(1, $element->getValue('interval'));
        $this->assertSame([
            'runsOn'  => 'each',
            'day1'    => 'n',
            'day2'    => 'n',
            'day3'    => 'n',
            'day4'    => 'n',
            'day5'    => 'n',
            'day6'    => 'n',
            'day7'    => 'n',
            'day8'    => 'y',
            'day9'    => 'n',
            'day10'   => 'n',
            'day11'   => 'n',
            'day12'   => 'n',
            'day13'   => 'n',
            'day14'   => 'n',
            'day15'   => 'n',
            'day16'   => 'n',
            'day17'   => 'y',
            'day18'   => 'y',
            'day19'   => 'n',
            'day20'   => 'n',
            'day21'   => 'n',
            'day22'   => 'n',
            'day23'   => 'n',
            'day24'   => 'n',
            'day25'   => 'n',
            'day26'   => 'n',
            'day27'   => 'y',
            'day28'   => 'n',
            'ordinal' => 'first', // Not really of interest, as disabled, but it's returned anyway
            'day'     => 'day' // Not really of interest, as disabled, but it's returned anyway
        ], $element->getValue('monthly-fields'));

        $this->assertEquals($frequency, $element->getValue());
    }

    public function testCustomOnTheEachMonthFrequency()
    {
        $start = new DateTime('2023-02-08T15:17:07');
        $end = new DateTime('2023-02-10T18:00:00');
        $frequency = (new RRule('FREQ=MONTHLY;INTERVAL=1;BYDAY=2WE'))
            ->startAt($start)
            ->endAt($end);

        $element = $this->assembleElement(['value' => $frequency]);

        $this->assertEquals($start, $element->getValue('start'));
        $this->assertEquals($end, $element->getValue('end'));
        $this->assertSame('custom', $element->getValue('frequency'));
        $this->assertSame(RRule::MONTHLY, $element->getValue('custom-frequency'));
        $this->assertSame(1, $element->getValue('interval'));
        $this->assertSame([
            'runsOn'  => 'onthe',
            'day1'    => 'n',
            'day2'    => 'n',
            'day3'    => 'n',
            'day4'    => 'n',
            'day5'    => 'n',
            'day6'    => 'n',
            'day7'    => 'n',
            'day8'    => 'n',
            'day9'    => 'n',
            'day10'   => 'n',
            'day11'   => 'n',
            'day12'   => 'n',
            'day13'   => 'n',
            'day14'   => 'n',
            'day15'   => 'n',
            'day16'   => 'n',
            'day17'   => 'n',
            'day18'   => 'n',
            'day19'   => 'n',
            'day20'   => 'n',
            'day21'   => 'n',
            'day22'   => 'n',
            'day23'   => 'n',
            'day24'   => 'n',
            'day25'   => 'n',
            'day26'   => 'n',
            'day27'   => 'n',
            'day28'   => 'n',
            'ordinal' => 'second',
            'day'     => 'WE'
        ], $element->getValue('monthly-fields'));

        $this->assertEquals($frequency, $element->getValue());
    }

    public function testCustomAnnuallyFrequency()
    {
        $start = new DateTime('2023-02-07T15:17:07');
        $frequency = (new RRule('FREQ=YEARLY;INTERVAL=1;BYMONTH=2'))->startAt($start);
        $element = $this->assembleElement(['value' => $frequency]);

        $this->assertEquals($start, $element->getValue('start'));
        $this->assertNull($element->getValue('end'));
        $this->assertSame('custom', $element->getValue('frequency'));
        $this->assertSame(RRule::YEARLY, $element->getValue('custom-frequency'));
        $this->assertSame([
            'month'     => 'FEB',
            'runsOnThe' => 'n',
            'ordinal'   => 'first', // Not really of interest, as disabled, but it's returned anyway
            'day'       => 'day' // Not really of interest, as disabled, but it's returned anyway
        ], $element->getValue('annually-fields'));

        $this->assertEquals($frequency, $element->getValue());
    }

    public function testCustomAnnuallyFrequencyWithEnd()
    {
        $start = new DateTime('2023-02-07T15:17:07');
        $end = new DateTime('2023-02-10T18:00:00');
        $frequency = (new RRule('FREQ=YEARLY;INTERVAL=1;BYMONTH=2'))
            ->startAt($start)
            ->endAt($end);

        $element = $this->assembleElement(['value' => $frequency]);

        $this->assertEquals($start, $element->getValue('start'));
        $this->assertEquals($end, $element->getValue('end'));
        $this->assertSame('custom', $element->getValue('frequency'));
        $this->assertSame(RRule::YEARLY, $element->getValue('custom-frequency'));
        $this->assertSame([
            'month'     => 'FEB',
            'runsOnThe' => 'n',
            'ordinal'   => 'first', // Not really of interest, as disabled, but it's returned anyway
            'day'       => 'day' // Not really of interest, as disabled, but it's returned anyway
        ], $element->getValue('annually-fields'));

        $this->assertEquals($frequency, $element->getValue());
    }

    public function testCustomOnTheEachYearFrequencyWithEnd()
    {
        $start = new DateTime('2023-02-07T15:17:07');
        $end = new DateTime('2023-02-10T18:00:00');
        $frequency = (new RRule('FREQ=YEARLY;INTERVAL=1;BYDAY=SA,SU;BYMONTH=2;BYSETPOS=3'))
            ->startAt($start)
            ->endAt($end);

        $element = $this->assembleElement(['value' => $frequency]);

        $this->assertEquals($start, $element->getValue('start'));
        $this->assertEquals($end, $element->getValue('end'));
        $this->assertSame('custom', $element->getValue('frequency'));
        $this->assertSame(RRule::YEARLY, $element->getValue('custom-frequency'));
        $this->assertSame([
            'month'     => 'FEB',
            'runsOnThe' => 'y',
            'ordinal'   => 'third',
            'day'       => 'weekend'
        ], $element->getValue('annually-fields'));

        $this->assertEquals($frequency, $element->getValue());
    }

    public function testRecurrenceStartIsSyncedCorrectly()
    {
        $start = new DateTime('2023-04-27T11:00:00');
        $frequency = (new RRule('FREQ=WEEKLY;INTERVAL=2;BYDAY=SA,SU'))->startAt($start);

        $value = $this->assembleElement(['value' => $frequency])->getValue();

        // The initial start date is April 27, but the frequency only triggers on Saturday/Sunday,
        // so the first recurrence is on April 29 and the start date should've been synced.
        $this->assertEquals(new DateTime('2023-04-29T11:00:00'), $value->getStart());
    }
}
