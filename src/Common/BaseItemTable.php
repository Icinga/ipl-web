<?php

namespace ipl\Web\Common;

use InvalidArgumentException;
use ipl\Html\BaseHtmlElement;
use ipl\Stdlib\BaseFilter;
use ipl\Web\Widget\EmptyState;

/**
 * Base class for item tables
 */
abstract class BaseItemTable extends BaseHtmlElement
{
    use BaseFilter;

    /** @var string Defines the layout used by this item */
    public const TABLE_LAYOUT = 'table-layout';

    protected $baseAttributes = [
        'class'            => 'item-table',
        'data-base-target' => '_next'
    ];

    /** @var iterable */
    protected $data;

    protected $tag = 'ul';

    /**
     * Create a new item table
     *
     * @param iterable $data Data source of the table
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

    /**
     * Initialize the item table
     *
     * If you want to adjust the item table after construction, override this method.
     */
    protected function init()
    {
    }

    /**
     * Get the table layout to use
     *
     * @return string
     */
    protected function getLayout(): string
    {
        return static::TABLE_LAYOUT;
    }

    abstract protected function getItemClass(): string;

    protected function assemble()
    {
        $this->addAttributes(['class' => $this->getLayout()]);

        $itemClass = $this->getItemClass();
        foreach ($this->data as $data) {
            /** @var BaseTableRowItem $item */
            $item = new $itemClass($data, $this);

            $this->addHtml($item);
        }

        if ($this->isEmpty()) {
            $this->setTag('div');
            $this->addHtml(new EmptyState(t('No items found.')));
        }
    }
}
