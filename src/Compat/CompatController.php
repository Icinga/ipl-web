<?php

namespace ipl\Web\Compat;

use InvalidArgumentException;
use Icinga\Web\Controller;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlDocument;
use ipl\Html\ValidHtml;
use ipl\Web\Layout\Content;
use ipl\Web\Layout\Controls;
use ipl\Web\Layout\Footer;
use ipl\Web\Widget\Tabs;

class CompatController extends Controller
{
    /** @var Content */
    protected $content;

    /** @var Controls */
    protected $controls;

    /** @var HtmlDocument */
    protected $document;

    /** @var Footer */
    protected $footer;

    /** @var Tabs */
    protected $tabs;

    /** @var array */
    protected $parts;

    protected function prepareInit()
    {
        parent::prepareInit();

        unset($this->view->tabs);

        $this->params->shift('isIframe');
        $this->params->shift('showFullscreen');
        $this->params->shift('showCompact');
        $this->params->shift('renderLayout');
        $this->params->shift('_disableLayout');
        $this->params->shift('_dev');
        if ($this->params->get('view') === 'compact') {
            $this->params->remove('view');
        }

        $this->document = new HtmlDocument();
        $this->document->setSeparator("\n");
        $this->controls = new Controls();
        $this->controls->setAttribute('id', $this->getRequest()->protectId('controls'));
        $this->content = new Content();
        $this->content->setAttribute('id', $this->getRequest()->protectId('content'));
        $this->footer = new Footer();
        $this->footer->setAttribute('id', $this->getRequest()->protectId('footer'));
        $this->tabs = new Tabs();
        $this->parts = [];

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
     * Add footer
     *
     * @param ValidHtml $footer
     *
     * @return $this
     */
    protected function addFooter(ValidHtml $footer)
    {
        $this->footer->add($footer);

        return $this;
    }

    /**
     * Add a part to be served as multipart-content
     *
     * If an id is passed the element is used as-is as the part's content.
     * Otherwise (no id given) the element's content is used instead.
     *
     * @param BaseHtmlElement $element
     * @param string          $id       If not given, this is taken from $element
     *
     * @throws InvalidArgumentException If no id is given and the element also does not have one
     *
     * @return $this
     */
    protected function addPart(BaseHtmlElement $element, $id = null)
    {
        $part = new Multipart();

        if ($id === null) {
            $id = $element->getAttributes()->get('id')->getValue();
            if (! $id) {
                throw new InvalidArgumentException('Element has no id');
            }

            $part->addFrom($element);
        } else {
            $part->add($element);
        }

        $this->parts[] = $part->setFor($id);

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

    public function setAutorefreshInterval($interval)
    {
        $interval = (int) $interval;
        if ($interval < 0) {
            throw new InvalidArgumentException('Negative autorefresh intervals are not supported');
        }

        $this->autorefreshInterval = $interval;
        $this->_helper->layout()->autorefreshInterval = $interval;

        return $this;
    }

    public function postDispatch()
    {
        if (empty($this->parts)) {
            if (! $this->content->isEmpty()) {
                $this->document->prepend($this->content);
            }

            if (! $this->view->compact && ! $this->controls->isEmpty()) {
                $this->document->prepend($this->controls);
            }

            if (! $this->footer->isEmpty()) {
                $this->document->add($this->footer);
            }
        } else {
            $partSeparator = base64_encode(random_bytes(16));
            $this->getResponse()->setHeader('X-Icinga-Multipart-Content', $partSeparator);

            $this->document->setSeparator("\n$partSeparator\n");
            $this->document->add($this->parts);
        }

        parent::postDispatch();
    }
}
