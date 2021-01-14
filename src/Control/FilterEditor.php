<?php

namespace ipl\Web\Control;

use Icinga\Data\Filter\Filter;
use Icinga\Data\Filter\FilterParseException;
use ipl\Html\Form;
use ipl\Html\FormElement\HiddenElement;
use ipl\Html\FormElement\InputElement;
use ipl\Html\FormElement\SubmitElement;
use ipl\Html\HtmlElement;
use ipl\Validator\CallbackValidator;
use ipl\Web\Control\FilterEditor\Terms;
use ipl\Web\Url;

class FilterEditor extends Form
{
    protected $defaultAttributes = ['class' => 'completion', 'role' => 'search'];

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

        $termContainer = (new Terms())->setAttribute('id', $termContainerId);
        $termInput = new HiddenElement($this->getSearchParameter(), [
            'id'        => $termInputId,
            'disabled'  => true
        ]);

        if (! $this->getRequest()->getHeaderLine('X-Icinga-Autorefresh')) {
            $termContainer->setFilter(function () {
                return $this->getFilter();
            });
            $termInput->getAttributes()->registerAttributeCallback('value', function () {
                return $this->getFilter()->toQueryString();
            });
        }

        $searchInput = new InputElement($this->getSearchParameter(), [
            'type'                  => 'text',
            'placeholder'           => 'type something..',
            'class'                 => 'search-input',
            'id'                    => $searchInputId,
            'autocomplete'          => 'off',
            'data-term-completion'  => 'full',
            'data-term-input'       => '#' . $termInputId,
            'data-term-container'   => '#' . $termContainerId,
            'data-term-suggestions' => '#' . $suggestionsId,
            'data-suggest-url'      => $this->getSuggestionUrl(),
            'data-choose-template'  => t('Please type one of: %s', '..<comma separated list>'),
            'validators'            => [
                new CallbackValidator(function ($q, CallbackValidator $validator) {
                    try {
                        $filter = Filter::fromQueryString($q);
                    } catch (FilterParseException $e) {
                        $charAt = $e->getCharPos() - 1;

                        $this->getElement($this->getSearchParameter())
                            ->setValue(substr($q, $charAt))
                            ->addAttributes([
                                'title'     => sprintf(t('Unexpected %s at start of input'), $e->getChar()),
                                'pattern'   => sprintf('^(?!\%s).*', $e->getChar())
                            ]);

                        $probablyValidQueryString = substr($q, 0, $charAt);
                        $this->setFilter(Filter::fromQueryString($probablyValidQueryString));
                        return false;
                    }

                    if ($filter->isExpression() && $filter->getExpression() === true) {
                        $filter = Filter::matchAny();
                        foreach ($this->getSearchColumns() as $column) {
                            $filter->addFilter(Filter::where($column, "*$q*"));
                        }
                    }

                    $this->setFilter($filter);
                    return true;
                })
            ]
        ]);

        $this->registerElement($searchInput);

        $this->add([
            $termContainer,
            $termInput,
            new HtmlElement('label', ['data-label' => ''], $searchInput),
            new SubmitElement('submit', ['label' => $this->getSubmitLabel()]),
            new HtmlElement('div', [
                'id'                => $suggestionsId,
                'class'             => 'suggestions',
                'data-base-target'  => $suggestionsId
            ])
        ]);
    }
}
