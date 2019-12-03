<?php

namespace ipl\Web\Layout;

use ipl\Html\BaseHtmlElement;

/**
 * Container for footer
 */
class Footer extends BaseHtmlElement
{
    protected $tag = 'div';

    protected $contentSeparator = "\n";

    protected $defaultAttributes = ['class' => 'footer'];
}
