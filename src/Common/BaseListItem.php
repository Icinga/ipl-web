<?php

namespace ipl\Web\Common;

use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;

/**
 * Base class for list items
 */
abstract class BaseListItem extends BaseHtmlElement
{
    protected $baseAttributes = ['class' => 'list-item'];

    /** @var object The associated list item */
    protected $item;

    /** @var BaseItemList The list where the item is part of */
    protected $list;

    protected $tag = 'li';

    /**
     * Create a new list item
     *
     * @param object       $item
     * @param BaseItemList $list
     */
    public function __construct($item, BaseItemList $list)
    {
        $this->item = $item;
        $this->list = $list;

        $this->addAttributes($this->baseAttributes);

        $this->init();
    }

    abstract protected function assembleHeader(BaseHtmlElement $header);

    abstract protected function assembleMain(BaseHtmlElement $main);

    protected function assembleFooter(BaseHtmlElement $footer)
    {
    }

    protected function assembleCaption(BaseHtmlElement $caption)
    {
    }

    protected function assembleTitle(BaseHtmlElement $title)
    {
    }

    protected function assembleVisual(BaseHtmlElement $visual)
    {
    }

    protected function createCaption(): BaseHtmlElement
    {
        $caption = new HtmlElement('section', Attributes::create(['class' => 'caption']));

        $this->assembleCaption($caption);

        return $caption;
    }

    protected function createHeader(): BaseHtmlElement
    {
        $header = new HtmlElement('header');

        $this->assembleHeader($header);

        return $header;
    }

    protected function createMain(): BaseHtmlElement
    {
        $main = new HtmlElement('div', Attributes::create(['class' => 'main']));

        $this->assembleMain($main);

        return $main;
    }

    protected function createFooter(): BaseHtmlElement
    {
        $footer = new HtmlElement('footer');

        $this->assembleFooter($footer);

        return $footer;
    }

    protected function createTimestamp()
    {
    }

    protected function createTitle(): BaseHtmlElement
    {
        $title = new HtmlElement('div', Attributes::create(['class' => 'title']));

        $this->assembleTitle($title);

        return $title;
    }

    /**
     * @return ?BaseHtmlElement
     */
    protected function createVisual()
    {
        $visual = new HtmlElement('div', Attributes::create(['class' => 'visual']));

        $this->assembleVisual($visual);

        return $visual;
    }

    /**
     * Initialize the list item
     *
     * If you want to adjust the list item after construction, override this method.
     */
    protected function init()
    {
    }

    protected function assemble()
    {
        $this->add([
            $this->createVisual(),
            $this->createMain()
        ]);
    }
}
