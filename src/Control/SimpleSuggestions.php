<?php

namespace ipl\Web\Control;

use ArrayIterator;
use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\FormattedString;
use ipl\Html\FormElement\ButtonElement;
use ipl\Html\FormElement\InputElement;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use IteratorIterator;
use LimitIterator;
use Psr\Http\Message\ServerRequestInterface;

abstract class SimpleSuggestions extends BaseHtmlElement
{
    /** @var int Suggestions limit */
    public const DEFAULT_LIMIT = 10;

    /** @var string Class name for suggestion title */
    public const SUGGESTION_TITLE_CLASS = 'suggestion-title';

    protected $tag = 'ul';

    /** @var string The given input for search */
    protected $searchTerm;

    /** @var mixed Fetched data for given input */
    protected $data;

    /** @var string Default first suggestion in the suggestion list */
    protected $default;

    /**
     * Set the search term
     *
     * @param string $searchTerm
     *
     * @return $this
     */
    public function setSearchTerm(string $searchTerm): self
    {
        $this->searchTerm = $searchTerm;

        return $this;
    }

    /**
     * Set the fetched data
     *
     * @param mixed $data
     *
     * @return $this
     */
    public function setData($data): self
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Set the default suggestion
     *
     * @param string $default
     *
     * @return $this
     */
    public function setDefault(string $default): self
    {
        $this->default = trim($default, '"\'');

        return $this;
    }

    /**
     * Fetch suggestions according to the input in the search field
     *
     * @param string $searchTerm The given input in the search field
     * @param array $exclude Already added terms to be excluded from the suggestion list
     *
     * @return mixed
     */
    abstract protected function fetchSuggestions(string $searchTerm, array $exclude = []);

    protected function assembleDefault(): void
    {
        if ($this->default === null) {
            return;
        }

        $attributes = [
            'type'          => 'button',
            'tabindex'      => -1,
            'data-label'    => $this->default,
            'value'         => $this->default,
        ];

        $button = new ButtonElement(null, $attributes);
        $button->addHtml(FormattedString::create(
            t('Add %s'),
            new HtmlElement('em', null, Text::create($this->default))
        ));

        $this->prependHtml(new HtmlElement('li', Attributes::create(['class' => 'default']), $button));
    }

    protected function assemble()
    {
        if ($this->data === null) {
            $data = [];
        } else {
            $data = $this->data;
            if (is_array($data)) {
                $data = new ArrayIterator($data);
            }

            $data = new LimitIterator(new IteratorIterator($data), 0, self::DEFAULT_LIMIT);
        }

        foreach ($data as $term => $label) {
            if (is_int($term)) {
                $term = $label;
            }

            $attributes = [
                'type'          => 'button',
                'tabindex'      => -1,
                'data-search'   => $term
            ];

            $attributes['value'] = $label;
            $attributes['data-label'] = $label;

            $this->addHtml(new HtmlElement('li', null, new InputElement(null, $attributes)));
        }

        $showDefault = true;
        if ($this->searchTerm && $this->count() === 1) {
            // The default option is not shown if the user's input result in an exact match
            $input = $this->getFirst('li')->getFirst('input');
            $showDefault = $input->getValue() != $this->searchTerm
                && $input->getAttributes()->get('data-search')->getValue() != $this->searchTerm;
        }

        if ($showDefault) {
            $this->assembleDefault();
        }
    }

    /**
     * Load suggestions as requested by the client
     *
     * @param ServerRequestInterface $request
     *
     * @return $this
     */
    public function forRequest(ServerRequestInterface $request): self
    {
        if ($request->getMethod() !== 'POST') {
            return $this;
        }

        $requestData = json_decode($request->getBody()->read(8192), true);
        if (empty($requestData)) {
            return $this;
        }

        $search = $requestData['term']['search'];
        $label = $requestData['term']['label'];
        $exclude = $requestData['exclude'];

        $this->setSearchTerm($search);

        $this->setData($this->fetchSuggestions($label, $exclude));

        if (! empty($search)) {
            $this->setDefault($search);
        }

        return $this;
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
