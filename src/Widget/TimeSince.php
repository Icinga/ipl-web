<?php

namespace ipl\Web\Widget;

use Icinga\Date\DateFormatter;
use ipl\Html\BaseHtmlElement;

class TimeSince extends BaseHtmlElement
{
    /** @var int */
    protected $since;

    protected $tag = 'time';

    protected $defaultAttributes = ['class' => 'time-since'];

    public function __construct($since)
    {
        //NOTE: Since this value is retrieved as a floating number from the DB, there is a data loss if we
        // simply convert it to an int. For timestamp values, decimal places are formatted with dot, so we can
        // remove any commas ahead without any problem. Apart from that icingadb is also storing the value
        // as an int so there can never be a timestamp value with decimal places. :)
        $this->since = intval(preg_replace('/[^\d,]/', '', $since));
    }

    protected function assemble()
    {
        $dateTime = DateFormatter::formatDateTime($this->since);

        $this->addAttributes([
            'datetime' => $dateTime,
            'title'    => $dateTime
        ]);

        $this->add(DateFormatter::timeSince($this->since));
    }
}
