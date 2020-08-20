<?php

namespace ipl\Web\Control\FilterEditor;

use Countable;
use ipl\Html\BaseHtmlElement;
use ipl\Html\FormElement\InputElement;
use ipl\Html\HtmlElement;
use ipl\Stdlib\Contract\Paginatable;
use IteratorIterator;
use LimitIterator;
use OuterIterator;
use Traversable;

class Suggestions extends BaseHtmlElement
{
    const DEFAULT_LIMIT = 50;

    protected $tag = 'ul';

    /** @var Traversable */
    protected $data;

    /** @var string */
    protected $type;

    public function setData($data)
    {
        $this->data = $data;

        return $this;
    }

    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    protected function assemble()
    {
        if ($this->data instanceof Paginatable) {
            $this->data->limit(self::DEFAULT_LIMIT);
            $data = $this->data;
        } else {
            $data = new LimitIterator(new IteratorIterator($this->data), 0, self::DEFAULT_LIMIT);
        }

        foreach ($data as $term => $meta) {
            if (is_int($term)) {
                $term = $meta;
            }

            $attributes = [
                'type'          => 'button',
                'tabindex'      => -1,
                'data-search'   => $term
            ];
            if ($this->type !== null) {
                $attributes['data-type'] = $this->type;
            }

            if (is_array($meta)) {
                foreach ($meta as $key => $value) {
                    if ($key === 'label') {
                        $attributes['value'] = $value;
                    }

                    $attributes['data-' . $key] = $value;
                }
            } else {
                $attributes['value'] = $meta;
                $attributes['data-label'] = $meta;
            }

            $this->add(new HtmlElement('li', null, new InputElement(null, $attributes)));
        }

        if ($this->hasMore($data, self::DEFAULT_LIMIT)) {
            $this->getAttributes()->add('class', 'has-more');
        }
    }

    protected function hasMore($data, $than)
    {
        if (is_array($data)) {
            return count($data) > $than;
        } elseif ($data instanceof Countable) {
            return $data->count() > $than;
        } elseif ($data instanceof OuterIterator) {
            return $this->hasMore($data->getInnerIterator(), $than);
        }

        return false;
    }

    public function renderUnwrapped()
    {
        $this->ensureAssembled();

        if ($this->isEmpty()) {
            return '';
        }

        return parent::renderUnwrapped();
    }
}
