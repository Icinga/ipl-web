<?php

namespace ipl\Web\Compat;

use InvalidArgumentException;
use Icinga\Web\Controller;
use ipl\Html\HtmlDocument;
use ipl\Html\ValidHtml;
use ipl\Web\Widget\Content;
use ipl\Web\Widget\Controls;
use ipl\Web\Widget\Tabs;

class CompatController extends Controller
{
    /** @var HtmlDocument */
    protected $document;

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

        $this->document = new HtmlDocument();
        $this->document->setSeparator("\n");
        $this->controls = new Controls();
        $this->content = new Content();
        $this->tabs = new Tabs();

        $this->controls->setTabs($this->tabs);

        ViewRenderer::inject();

        $this->view->document = $this->document;
    }

    /**
     * Get the document
     *
     * @return HtmlDocument
     */
    public function getDocument()
    {
        return $this->document;
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
    protected function setTitle($title)
    {
        $args = func_get_args();
        array_shift($args);

        if (! empty($args)) {
            $title = vsprintf($title, $args);
        }

        $this->view->title = $title;

        $this->getTabs()->add(uniqid(), [
            'active'    => true,
            'label'     => $title,
            'url'       => $this->getRequest()->getUrl()
        ]);

        return $this;
    }

    public function postDispatch()
    {
        if (! $this->content->isEmpty()) {
            $this->document->prepend($this->content);
        }

        if (! $this->controls->isEmpty()) {
            $this->document->prepend($this->controls);
        }

        parent::postDispatch();
    }
}
