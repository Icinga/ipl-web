<?php

namespace ipl\Web\Compat\FormDecorator;

use ipl\Html\Contract\FormElement;
use ipl\Html\HtmlString;
use ipl\Html\FormDecoration\LabelDecorator as IplHtmlLabelDecorator;
use ipl\Html\ValidHtml;

class LabelDecorator extends IplHtmlLabelDecorator
{
    protected function getElementLabel(FormElement $formElement): ?ValidHtml
    {
        return parent::getElementLabel($formElement) ?? HtmlString::create('&nbsp;');
    }
}
