<?php

namespace ipl\Tests\Web;

use InvalidArgumentException;
use ipl\Html\Contract\FormElements;
use ipl\Html\HtmlDocument;
use ipl\Web\FormElement\TermInput;

class TermInputTest extends TestCase
{
    public function testTermEventsAreValid(): void
    {
        $input = new TermInput('test');
        $events = [
            TermInput::ON_ADD,
            TermInput::ON_PASTE,
            TermInput::ON_SAVE,
            TermInput::ON_REMOVE,
            TermInput::ON_ENRICH,
        ];

        foreach ($events as $event) {
            $this->assertTrue($input->isValidEvent($event), "$event is not a valid event");
        }
    }

    public function testFieldsetEventsAreValid(): void
    {
        $input = new TermInput('test');

        $this->assertTrue($input->isValidEvent(FormElements::ON_ELEMENT_REGISTERED));
        $this->assertTrue($input->isValidEvent(HtmlDocument::ON_ASSEMBLED));
    }

    public function testRegisteringAnUnknownEventThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new TermInput('test'))->on('on_unknown', function (): void {
        });
    }
}
