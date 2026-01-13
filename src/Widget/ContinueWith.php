<?php

namespace ipl\Web\Widget;

use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;
use ipl\Stdlib\Filter;
use ipl\Web\Common\BaseTarget;
use ipl\Web\Url;

class ContinueWith extends BaseHtmlElement
{
    use BaseTarget;

    protected $tag = 'span';

    protected $defaultAttributes = ['class' => 'continue-with'];

    /** @var Url */
    protected Url $url;

    /** @var Filter\Rule|callable */
    protected $filter;

    /** @var ?string */
    protected ?string $title;

    /** @var bool Whether the current query has results */
    protected bool $hasResults;

    /**
     * Whether the current query has results
     *
     * @return bool
     */
    public function hasResults(): bool
    {
        return $this->hasResults;
    }

    /**
     * Create a ContinueWith widget
     *
     * @param Url $url The detail url
     * @param Filter\Rule|callable $filter The filter to apply
     * @param bool $hasResults Whether the current query has results
     */
    public function __construct(Url $url, $filter, bool $hasResults = true)
    {
        $this->url = $url;
        $this->filter = $filter;
        $this->hasResults = $hasResults;
    }

    /**
     * Set title for the anchor
     *
     * @param string $title
     *
     * @return $this
     */
    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function assemble(): void
    {
        $filter = $this->filter;
        if (is_callable($filter)) {
            $filter = $filter(); /** @var Filter\Rule $filter */
        }

        if (! $this->hasResults() || ($filter instanceof Filter\Chain && $filter->isEmpty())) {
            $this->addHtml(new HtmlElement(
                'span',
                Attributes::create(['class' => ['control-button', 'disabled']]),
                new Icon('share')
            ));
        } else {
            $baseFilter = $this->url->getFilter();
            if ($baseFilter && ((! $baseFilter instanceof Filter\Chain) || ! $baseFilter->isEmpty())) {
                $filter = Filter::all($baseFilter, $filter);
            }

            $this->addHtml(new ActionLink(
                null,
                $this->url->setFilter($filter),
                'share',
                ['class' => 'control-button', 'title' => $this->title]
            ));
        }
    }
}
