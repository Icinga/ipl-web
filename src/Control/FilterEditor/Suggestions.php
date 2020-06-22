<?php

namespace ipl\Web\Control\FilterEditor;

use ipl\Html\BaseHtmlElement;
use ipl\Html\FormElement\InputElement;
use ipl\Html\HtmlElement;

class Suggestions extends BaseHtmlElement
{
    protected $tag = 'ul';

    /** @var iterable */
    protected $data;

    public function setData($data)
    {
        $this->data = $data;

        return $this;
    }

    protected function assemble()
    {
        foreach ($this->data as $term => $meta) {
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
