<?php

namespace ipl\Web\Control;

use ipl\Html\Attributes;
use ipl\Html\Form;
use ipl\Html\FormElement\InputElement;
use ipl\Html\FormElement\SubmitElement;
use ipl\Html\HtmlElement;
use ipl\Web\Url;
use ipl\Web\Widget\Icon;

use function ipl\I18n\t;

/**
 * Form for simple value based search
 */
class SimpleSearchField extends Form
{
    protected $defaultAttributes = [
        'class'     => 'search-field',
        'name'      => 'search-field',
        'role'      => 'search'
    ];

    /** @var string The term separator */
    public const TERM_SEPARATOR = ',';

    /** @var string The default search parameter */
    public const DEFAULT_SEARCH_PARAM = 'q';

    /** @var string The search parameter */
    protected $searchParameter;

    /** @var Url The suggestion url */
    protected $suggestionUrl;

    /** @var string Submit label */
    protected $submitLabel;

    /**
     * Set the search parameter
     *
     * @param string $name
     *
     * @return  $this
     */
    public function setSearchParameter(string $name): self
    {
        $this->searchParameter = $name;

        return $this;
    }

    /**
     * Get the search parameter
     *
     * @return string
     */
    public function getSearchParameter(): string
    {
        return $this->searchParameter ?: self::DEFAULT_SEARCH_PARAM;
    }

    /**
     * Set the suggestion url
     *
     * @param   Url $url
     *
     * @return  $this
     */
    public function setSuggestionUrl(Url $url): self
    {
        $this->suggestionUrl = $url;

        return $this;
    }

    /**
     * Get the suggestion url
     *
     * @return Url
     */
    public function getSuggestionUrl(): Url
    {
        return $this->suggestionUrl;
    }

    /**
     * Set submit label
     *
     * @param string $label
     *
     * @return  $this
     */
    public function setSubmitLabel(string $label): self
    {
        $this->submitLabel = $label;

        return $this;
    }

    /**
     * Get submit label
     *
     * @return string
     */
    public function getSubmitLabel(): string
    {
        return $this->submitLabel ?? t('Submit');
    }

    public function assemble()
    {
        $filterInput = new InputElement(null, [
            'type'                      => 'text',
            'placeholder'               => t('Type to search'),
            'class'                     => 'search-field',
            'id'                        => 'search-filed',
            'autocomplete'              => 'off',
            'required'                  => true,
            'data-no-auto-submit'       => true,
            'data-no-js-placeholder'    => true,
            'data-enrichment-type'      => 'terms',
            'data-term-separator'       => self::TERM_SEPARATOR,
            'data-term-mode'            => 'read-only',
            'data-term-direction'       => 'vertical',
            'data-data-input'           => '#data-input',
            'data-term-input'           => '#term-input',
            'data-term-container'       => '#term-container',
            'data-term-suggestions'     => '#term-suggestions',
            'data-suggest-url'          => $this->getSuggestionUrl()
        ]);

        $dataInput = new InputElement('data', ['type' => 'hidden', 'id' => 'data-input']);

        $termInput = new InputElement($this->getSearchParameter(), ['type' => 'hidden', 'id' => 'term-input']);
        $this->registerElement($termInput);

        $termContainer = new HtmlElement(
            'div',
            Attributes::create(['id' => 'term-container', 'class' => 'term-container'])
        );

        $termSuggestions = new HtmlElement(
            'div',
            Attributes::create(['id' => 'term-suggestions', 'class' => 'search-suggestions'])
        );

        $submitButton = new SubmitElement('submit', ['label' => $this->getSubmitLabel()]);

        $this->registerElement($submitButton);

        $this->add([
            HtmlElement::create(
                'div',
                null,
                [
                    new Icon('search', ['class' => 'search-icon']),
                    $filterInput,
                    $termSuggestions,
                    $dataInput,
                    $termInput,
                    $submitButton
                ]
            ),
            $termContainer
        ]);
    }
}
