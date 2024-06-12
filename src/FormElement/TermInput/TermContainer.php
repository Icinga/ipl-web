<?php

namespace ipl\Web\FormElement\TermInput;

use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;
use ipl\Web\FormElement\TermInput;
use ipl\Web\Widget\Icon;

class TermContainer extends BaseHtmlElement
{
    protected $tag = 'div';

    protected $defaultAttributes = ['class' => 'terms'];

    /** @var TermInput */
    protected $input;

    /**
     * Create a new TermContainer
     *
     * @param TermInput $input
     */
    public function __construct(TermInput $input)
    {
        $this->input = $input;
    }

    protected function assemble()
    {
        foreach ($this->input->getTerms() as $i => $term) {
            $value = $term->getLabel() ?: $term->getSearchValue();

            $label = new HtmlElement(
                'label',
                Attributes::create([
                    'class' => $term->getClass(),
                    'data-search' => $term->getSearchValue(),
                    'data-label' => $value,
                    'data-index' => $i
                ]),
                new HtmlElement(
                    'input',
                    Attributes::create([
                        'type' => 'text',
                        'value' => $value,
                        'pattern' => $term->getPattern(),
                        'data-invalid-msg' => $term->getMessage(),
                        'readonly' => $this->input->getReadOnly()
                    ])
                )
            );
            if ($this->input->getReadOnly()) {
                $label->addHtml(
                    new Icon('trash'),
                    new HtmlElement('span', Attributes::create(['class' => 'invalid-reason']))
                );
            }

            $this->addHtml($label);
        }
    }
}
