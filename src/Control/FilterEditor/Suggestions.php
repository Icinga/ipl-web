<?php

namespace ipl\Web\Control\FilterEditor;

use Countable;
use ipl\Html\BaseHtmlElement;
use ipl\Html\FormElement\InputElement;
use ipl\Html\HtmlElement;
use ipl\Stdlib\Contract\Paginatable;
use ipl\Web\Url;
use ipl\Web\Widget\Icon;
use ipl\Web\Widget\Link;
use IteratorIterator;
use LimitIterator;
use OuterIterator;
use Traversable;

class Suggestions extends BaseHtmlElement
{
    protected $tag = 'ul';

    /** @var Url */
    protected $url;

    /** @var Traversable */
    protected $data;

    /** @var int */
    protected $limit = 25;

    /** @var int */
    protected $pageNo = 1;

    public function setUrl(Url $url)
    {
        $this->url = $url;

        return $this;
    }

    public function setData($data)
    {
        $this->data = $data;

        return $this;
    }

    public function setPageSize($pageNo, $limit)
    {
        $this->pageNo = $pageNo;
        $this->limit = $limit;
    }

    protected function assemble()
    {
        $limit = $this->limit;
        $offset = $this->limit * ($this->pageNo - 1);

        if ($this->data instanceof Paginatable) {
            $this->data->limit($limit);
            $this->data->offset($offset);
            $data = $this->data;
        } else {
            $data = new LimitIterator(new IteratorIterator($this->data), $offset, $limit);
        }

        foreach ($data as $term => $meta) {
            if (is_int($term)) {
                $term = $meta;
            }

            $attributes = [
                'type'      => 'button',
                'tabindex'  => -1,
                'data-term' => $term
            ];
            if (is_array($meta)) {
                foreach ($meta as $key => $value) {
                    if ($key === 'label') {
                        $attributes['value'] = $value;
                    } else {
                        $attributes['data-' . $key] = $value;
                    }
                }
            } else {
                $attributes['value'] = $meta;
            }

            $this->add(new HtmlElement('li', null, new InputElement(null, $attributes)));
        }

        if (! $this->isEmpty() && $this->url !== null) {
            $pagination = new HtmlElement('li', ['class' => 'pagination']);
            if ($this->pageNo > 1) {
                $pagination->add(new Link(new Icon('angle-double-left'), $this->url->with([
                    'limit' => $limit,
                    'page'  => $this->pageNo - 1
                ])));
            }

            if ($this->hasMore($data, $offset + $limit)) {
                $pagination->add(new Link(new Icon('angle-double-right'), $this->url->with([
                    'limit' => $limit,
                    'page'  => $this->pageNo + 1
                ])));
            }

            if (! $pagination->isEmpty()) {
                $this->add($pagination);
            }
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

        // Show next page link in any case for unknown traversables
        return true;
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
