<?php

namespace ipl\Web\Common;

use InvalidArgumentException;
use ipl\Html\BaseHtmlElement;
use ipl\Stdlib\BaseFilter;
use ipl\Web\Widget\EmptyState;

/**
 * Base class for item lists
 */
abstract class BaseItemList extends BaseHtmlElement
{
    use BaseFilter;

    protected $baseAttributes = [
        'class'                         => ['item-list', 'default-layout'],
        'data-base-target'              => '_next',
        'data-pdfexport-page-breaks-at' => '.list-item'
    ];

    /** @var iterable */
    protected $data;

    protected $tag = 'ul';

    /**
     * Create a new item  list
     *
     * @param iterable $data Data source of the list
     */
    public function __construct($data)
    {
        if (! is_iterable($data)) {
            throw new InvalidArgumentException('Data must be an array or an instance of Traversable');
        }

        $this->data = $data;

        $this->addAttributes($this->baseAttributes);

        $this->init();
    }

    abstract protected function getItemClass(): string;

    /**
     * Initialize the item list
     *
     * If you want to adjust the item list after construction, override this method.
     */
    protected function init()
    {
    }

    protected function assemble()
    {
        $itemClass = $this->getItemClass();
        foreach ($this->data as $data) {
            /** @var BaseListItem|BaseTableRowItem $item */
            $item = new $itemClass($data, $this);
            $this->addHtml($item);
        }

        if ($this->isEmpty()) {
            $this->setTag('div');
            $this->addHtml(new EmptyState(t('No items found.')));
        }
    }
}
