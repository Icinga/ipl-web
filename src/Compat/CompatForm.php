<?php

namespace ipl\Web\Compat;

use ipl\Html\Form;

class CompatForm extends Form
{
    protected $defaultAttributes = ['class' => 'icinga-form icinga-controls'];

    public function hasDefaultElementDecorator()
    {
        if ($this->defaultElementDecorator === null) {
            $this->defaultElementDecorator = new CompatDecorator();
        }

        return parent::hasDefaultElementDecorator();
    }
}
