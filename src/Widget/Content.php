<?php

namespace ipl\Web\Widget;

use ipl\Html\BaseHtmlElement;

/**
 * Container for content
 */
class Content extends BaseHtmlElement
{
    protected $tag = 'div';

    protected $contentSeparator = "\n";

    protected $defaultAttributes = ['class' => 'content'];
}
