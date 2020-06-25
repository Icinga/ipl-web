<?php

namespace ipl\Web\Control;

use Icinga\Data\Filter\Filter;
use ipl\Html\Form;
use ipl\Html\FormElement\HiddenElement;
use ipl\Html\FormElement\InputElement;
use ipl\Html\FormElement\SubmitElement;
use ipl\Html\HtmlElement;
use ipl\Web\Url;

class FilterEditor extends Form
{
    protected $defaultAttributes = ['class' => 'completion'];

    /** @var Filter */
    protected $filter;

    /** @var string */
    protected $searchParameter;

    /** @var array */
    protected $searchColumns;

    /** @var Url */
    protected $suggestionUrl;

    /** @var string */
    protected $submitLabel;

    /** @var callable */
    protected $protector;

    /**
     * Set the filter to use
     *
     * @param   Filter $filter
     * @return  $this
     */
    public function setFilter(Filter $filter)
    {
        $this->filter = $filter;

        return $this;
    }

    /**
     * Get the filter in use
     *
     * @return Filter
     */
    public function getFilter()
    {
        return $this->filter;
    }

    /**
     * Set the search parameter to use
     *
     * @param   string $name
     * @return  $this
     */
    public function setSearchParameter($name)
    {
        $this->searchParameter = $name;

        return $this;
    }

    /**
     * Get the search parameter in use
     *
     * @return string
     */
    public function getSearchParameter()
    {
        return $this->searchParameter ?: 'q';
    }

    /**
     * Set the search columns to use
     *
     * @param   array $columns
     * @return  $this
     */
    public function setSearchColumns(array $columns)
    {
        $this->searchColumns = $columns;

        return $this;
    }

    /**
     * Get the search columns in use
     *
     * @return array
     */
    public function getSearchColumns()
    {
        return $this->searchColumns ?: [];
    }

    /**
     * Set the suggestion url
     *
     * @param   Url $url
     * @return  $this
     */
    public function setSuggestionUrl(Url $url)
    {
        $this->suggestionUrl = $url;

        return $this;
    }

    /**
     * Get the suggestion url
     *
     * @return Url
     */
    public function getSuggestionUrl()
    {
        return $this->suggestionUrl;
    }

    /**
     * Set the submit label
     *
     * @param   string $label
     * @return  $this
     */
    public function setSubmitLabel($label)
    {
        $this->submitLabel = $label;

        return $this;
    }

    /**
     * Get the submit label
     *
     * @return string
     */
    public function getSubmitLabel()
    {
        return $this->submitLabel;
    }

    /**
     * Set callback to protect ids with
     *
     * @param   callable $protector
     *
     * @return  $this
     */
    public function setIdProtector($protector)
    {
        $this->protector = $protector;

        return $this;
    }

    private function protectId($id)
    {
        if (is_callable($this->protector)) {
            return call_user_func($this->protector, $id);
        }

        return $id;
    }

    protected function assemble()
    {
        $termContainerId = $this->protectId('terms');
        $termInputId = $this->protectId('term-input');
        $searchInputId = $this->protectId('search-input');
        $suggestionsId = $this->protectId('suggestions');

        $termContainer = new HtmlElement('div', [
            'id'    => $termContainerId,
            'class' => 'terms'
        ]);
        if (($filter = $this->getFilter()) !== null) {
            // TODO: Either pre-render the filter as terms or provide a way for JS to restore the terms
        }

        $this->add($termContainer);

        $this->addElement(new HiddenElement($this->getSearchParameter(), [
            'id'        => $termInputId,
            'disabled'  => true
        ]));

        $searchInput = new InputElement($this->getSearchParameter(), [
            'type'                  => 'text',
            'required'              => true,
            'placeholder'           => 'type something..',
            'class'                 => 'autofocus search-input',
            'id'                    => $searchInputId,
            'autocomplete'          => 'off',
            'data-term-completion'  => 'full',
            'data-term-input'       => '#' . $termInputId,
            'data-term-container'   => '#' . $termContainerId,
            'data-term-suggestions' => '#' . $suggestionsId,
            'data-suggest-url'      => $this->getSuggestionUrl()
        ]);
        $this->registerElement($searchInput)
            ->add(new HtmlElement('label', null, $searchInput));

        $this->add(new SubmitElement('submit', ['label' => $this->getSubmitLabel()]));

        $this->add(new HtmlElement('div', ['id' => $suggestionsId, 'class' => 'suggestions']));
    }

    public function assembleFilter()
    {
        $q = $this->getValue('q');
        $filter = Filter::fromQueryString($q);
        if ($filter->isExpression() && $filter->getExpression() === true) {
            $filter = Filter::matchAny();
            foreach ($this->getSearchColumns() as $column) {
                $filter->addFilter(Filter::where($column, "*$q*"));
            }
        }

        return $filter;
    }
}
