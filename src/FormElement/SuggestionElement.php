<?php

namespace ipl\Web\FormElement;

use ipl\Html\Attributes;
use ipl\Html\FormElement\TextElement;
use ipl\Html\HtmlDocument;
use ipl\Html\HtmlElement;
use ipl\Web\Url;
use ipl\Web\Widget\Icon;

class SuggestionElement extends TextElement
{
    protected $defaultAttributes = [
        'autocomplete'          => 'off',
        'class'                 => 'suggestion-element',
        'data-enrichment-type'  => 'completion'
    ];

    /** @var Url URL to fetch suggestions from */
    protected Url $suggestionsUrl;

    /**
     * Create a new SuggestionElement
     *
     * @param string $name Name of the form element
     * @param Url $suggestionsUrl URL to fetch suggestions from
     * @param ?(array|Attributes) $attributes Attributes of the form element
     */
    public function __construct(string $name, Url $suggestionsUrl, array|Attributes $attributes = null)
    {
        parent::__construct($name, $attributes);

        $this->setSuggestionsUrl($suggestionsUrl);
    }

    /**
     * Get the URL to fetch suggestions from
     *
     * @return Url
     */
    public function getSuggestionsUrl(): Url
    {
        return $this->suggestionsUrl;
    }

    /**
     * Set the URL to fetch suggestions from
     *
     * @param Url $suggestionsUrl
     *
     * @return $this
     */
    public function setSuggestionsUrl(Url $suggestionsUrl): static
    {
        $this->suggestionsUrl = $suggestionsUrl;

        return $this;
    }

    /**
     * @return string If not set, returns a default placeholder
     */
    public function getPlaceholder(): string
    {
        return $this->placeholder ?? $this->translate('Start typing to see suggestions…');
    }

    protected function assemble(): void
    {
        $suggestionsId = uniqid('search-suggestions-');

        $this->prependWrapper(
            (new HtmlDocument())
                ->addHtml(
                    new HtmlElement('div', new Attributes(['id' => $suggestionsId, 'class' => 'search-suggestions'])),
                    new HtmlElement('span', new Attributes(['class' => 'suggestion-element-icon']), new Icon('search'))
                )
        );

        $this->getAttributes()->add([
            'data-term-suggestions' => '#' . $suggestionsId,
            'data-suggest-url'      => $this->getSuggestionsUrl()
        ]);
    }
}
