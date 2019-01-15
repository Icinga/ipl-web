<?php

namespace ipl\Web\Widget;

use ipl\Html\BaseHtmlElement;

/**
 * Container for controls
 */
class Controls extends BaseHtmlElement
{
    protected $tag = 'div';

    protected $contentSeparator = "\n";

    protected $defaultAttributes = ['class' => 'controls'];

    /** @var Tabs */
    protected $tabs;

    /**
     * Get the tabs
     *
     * @return Tabs
     */
    public function getTabs()
    {
        return $this->tabs;
    }

    /**
     * Set the tabs
     *
     * @param Tabs $tabs
     *
     * @return $this
     */
    public function setTabs(Tabs $tabs)
    {
        $this->tabs = $tabs;

        return $this;
    }

    protected function assemble()
    {
        $this->prepend($this->getTabs());
    }
}
