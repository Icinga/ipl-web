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
use ipl\Web\Control\SearchBar\Terms;
use ipl\Web\Url;
use ipl\Web\Widget\Icon;

class SearchBar extends Form
{
    protected $defaultAttributes = ['class' => 'search-bar', 'role' => 'search'];

    /** @var Filter */
    protected $filter;

    /** @var string */
    protected $searchParameter;

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

        $filterInput = new InputElement($this->getSearchParameter(), [
            'type'                  => 'text',
            'placeholder'           => 'type something..',
            'class'                 => 'filter-input',
            'id'                    => $searchInputId,
            'autocomplete'          => 'off',
            'data-enrichment-type'  => 'filter',
            'data-term-input'       => '#' . $termInputId,
            'data-term-container'   => '#' . $termContainerId,
            'data-term-suggestions' => '#' . $suggestionsId,
            'data-missing-log-op'   => t('Please add a logical operator on the left.'),
            'data-incomplete-group' => t('Please close or remove this group.'),
            'data-choose-template'  => t('Please type one of: %s', '..<comma separated list>'),
            'data-choose-column'    => t('Please enter a valid column.'),
            'validators'            => [
                new CallbackValidator(function ($q, CallbackValidator $validator) {
                    try {
                        $filter = Filter::fromQueryString($q);
                    } catch (FilterParseException $e) {
                        $charAt = $e->getCharPos() - 1;
                        $char = $e->getChar();

                        $this->getElement($this->getSearchParameter())
                            ->setValue(substr($q, $charAt))
                            ->addAttributes([
                                'title'     => sprintf(t('Unexpected %s at start of input'), $char),
                                'pattern'   => sprintf('^(?!%s).*', $char === ')' ? '\)' : $char)
                            ]);

                        $probablyValidQueryString = substr($q, 0, $charAt);
                        $this->setFilter(Filter::fromQueryString($probablyValidQueryString));
                        return false;
                    }

                    $this->setFilter($filter);
                    return true;
                })
            ]
        ]);
        if (($suggestionUrl = $this->getSuggestionUrl()) !== null) {
            $filterInput->setAttribute('data-suggest-url', $suggestionUrl);
        }

        $this->registerElement($filterInput);

        $submitButton = new SubmitElement('submit', ['label' => $this->getSubmitLabel()]);
        $this->registerElement($submitButton);

        $this->add([
            new HtmlElement(
                'button',
                ['type' => 'button', 'class' => 'search-options'],
                new Icon('search')
            ),
            new HtmlElement('div', ['class' => 'filter-input-area'], [
                $termContainer,
                new HtmlElement('label', ['data-label' => ''], $filterInput),
            ]),
            $termInput,
            $submitButton,
            new HtmlElement('div', [
                'id'                => $suggestionsId,
                'class'             => 'suggestions',
                'data-base-target'  => $suggestionsId
            ])
        ]);
    }
}
