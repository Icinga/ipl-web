<?php

namespace ipl\Web\FormElement\ScheduleElement;

use DateTime;
use ipl\Html\Attributes;
use ipl\Html\FormElement\BaseFormElement;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\I18n\Translation;
use ipl\Scheduler\Contract\Frequency;
use ipl\Scheduler\RRule;

class Recurrence extends BaseFormElement
{
    use Translation;

    protected $tag = 'div';

    protected $defaultAttributes = ['class' => 'schedule-recurrences'];

    /** @var callable A callable that generates a frequency instance */
    protected $frequencyCallback;

    /** @var callable A validation callback for the schedule element */
    protected $isValidCallback;

    /**
     * Set a validation callback that will be called when assembling this element
     *
     * @param callable $callback
     *
     * @return $this
     */
    public function setValid(callable $callback): self
    {
        $this->isValidCallback = $callback;

        return $this;
    }

    /**
     * Set a callback that generates an {@see Frequency} instance
     *
     * @param callable $callback
     *
     * @return $this
     */
    public function setFrequency(callable $callback): self
    {
        $this->frequencyCallback = $callback;

        return $this;
    }

    protected function assemble()
    {
        $isValid = ($this->isValidCallback)();
        if (! $isValid) {
            return;
        }

        /** @var RRule $frequency */
        $frequency = ($this->frequencyCallback)();
        $recurrences = $frequency->getNextRecurrences(new DateTime(), 3);
        if (! $recurrences->valid()) {
            // Such a situation can be caused by setting an invalid end time
            $this->addHtml(Text::create($this->translate('Recurrences cannot be generated')));

            return;
        }

        foreach ($recurrences as $recurrence) {
            $this->addHtml(HtmlElement::create('p', null, $recurrence->format($this->translate('D, Y/m/d, H:i:s'))));
        }
    }

    protected function registerAttributeCallbacks(Attributes $attributes)
    {
        parent::registerAttributeCallbacks($attributes);

        $attributes
            ->registerAttributeCallback('frequency', null, [$this, 'setFrequency'])
            ->registerAttributeCallback('valid', null, [$this, 'setValid']);
    }
}
