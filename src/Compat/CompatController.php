<?php

namespace ipl\Web\Compat;

use InvalidArgumentException;
use Icinga\Web\Controller;
use ipl\Html\ValidHtml;
use ipl\Web\Widget\Content;
use ipl\Web\Widget\Controls;
use ipl\Web\Widget\Tabs;

class CompatController extends Controller
{
    /** @var Controls */
    protected $controls;

    /** @var Content */
    protected $content;

    /** @var Tabs */
    protected $tabs;

    protected function prepareInit()
    {
        parent::prepareInit();

        unset($this->view->tabs);

        $this->controls = new Controls();
        $this->content = new Content();
        $this->tabs = new Tabs();

        $this->controls->setTabs($this->tabs);

        ViewRenderer::inject();

        $this->view->controls = $this->controls;
        $this->view->content = $this->content;
    }

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
     * Add a control
     *
     * @param ValidHtml $control
     *
     * @return $this
     */
    protected function addControl(ValidHtml $control)
    {
        $this->controls->add($control);

        return $this;
    }

    /**
     * Add content
     *
     * @param ValidHtml $content
     *
     * @return $this
     */
    protected function addContent(ValidHtml $content)
    {
        $this->content->add($content);

        return $this;
    }

    /**
     * Add an active tab with the given title and set it as the window's title too
     *
     * @param string $title
     * @param mixed  ...$args
     *
     * @return $this
     *
     * @throws InvalidArgumentException
     */
    protected function setTitle($title, ...$args)
    {
        $title = vsprintf($title, $args);

        $this->view->title = $title;

        $this->getTabs()->add(uniqid(), [
            'active'    => true,
            'label'     => $title,
            'url'       => $this->getRequest()->getUrl()
        ]);

        return $this;
    }
}
