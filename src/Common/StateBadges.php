<?php

namespace ipl\Web\Common;

use ipl\Html\BaseHtmlElement;
use ipl\Html\Html;
use ipl\Stdlib\BaseFilter;
use ipl\Stdlib\Filter;
use ipl\Web\Filter\QueryString;
use ipl\Web\Url;
use ipl\Web\Widget\Link;
use ipl\Web\Widget\StateBadge;

/**
 * @deprecated Use {@see \Icinga\Module\Icingadb\Common\StateBadges} instead.
 */
abstract class StateBadges extends BaseHtmlElement
{
    use BaseFilter;

    /** @var object $item */
    protected $item;

    /** @var string */
    protected $type;

    /** @var string Prefix */
    protected $prefix;

    /** @var Url Badge link */
    protected $url;

    protected $tag = 'ul';

    protected $defaultAttributes = ['class' => 'state-badges'];

    /**
     * Create a new widget for state badges
     *
     * @param object $item
     */
    public function __construct($item)
    {
        $this->item = $item;
        $this->type = $this->getType();
        $this->prefix = $this->getPrefix();
        $this->url = $this->getBaseUrl();
    }

    /**
     * Get the badge base URL
     *
     * @return Url
     */
    abstract protected function getBaseUrl(): Url;

    /**
     * Get the type of the items
     *
     * @return string
     */
    abstract protected function getType(): string;

    /**
     * Get the prefix for accessing state information
     *
     * @return string
     */
    abstract protected function getPrefix(): string;

    /**
     * Get the integer of the given state text
     *
     * @param string $state
     *
     * @return int
     */
    abstract protected function getStateInt(string $state): int;

    /**
     * Get the badge URL
     *
     * @return Url
     */
    public function getUrl(): Url
    {
        return $this->url;
    }

    /**
     * Set the badge URL
     *
     * @param Url $url
     *
     * @return $this
     */
    public function setUrl(Url $url): self
    {
        $this->url = $url;

        return $this;
    }

    /**
     * Create a badge link
     *
     * @param mixed $content
     * @param ?array $filter
     *
     * @return Link
     */
    public function createLink($content, array $filter = null): Link
    {
        $url = clone $this->getUrl();

        $urlFilter = Filter::all();
        if (! empty($filter)) {
            foreach ($filter as $column => $value) {
                $urlFilter->add(Filter::equal($column, $value));
            }
        }

        if ($this->hasBaseFilter()) {
            $urlFilter->add($this->getBaseFilter());
        }

        if (! $urlFilter->isEmpty()) {
            $url->setFilter($urlFilter);
        }

        return new Link($content, $url);
    }

    /**
     * Create a state bade
     *
     * @param string $state
     *
     * @return ?BaseHtmlElement
     */
    protected function createBadge(string $state)
    {
        $key = $this->prefix . "_{$state}";

        if (isset($this->item->$key) && $this->item->$key) {
            return Html::tag('li', $this->createLink(
                new StateBadge($this->item->$key, $state),
                [$this->type . '.state.soft_state' => $this->getStateInt($state)]
            ));
        }

        return null;
    }

    /**
     * Create a state group
     *
     * @param string $state
     *
     * @return ?BaseHtmlElement
     */
    protected function createGroup(string $state)
    {
        $content = [];
        $handledKey = $this->prefix . "_{$state}_handled";
        $unhandledKey = $this->prefix . "_{$state}_unhandled";

        if (isset($this->item->$unhandledKey) && $this->item->$unhandledKey) {
            $content[] = Html::tag('li', $this->createLink(
                new StateBadge($this->item->$unhandledKey, $state),
                [
                    $this->type . '.state.soft_state' => $this->getStateInt($state),
                    $this->type . '.state.is_handled' => 'n'
                ]
            ));
        }

        if (isset($this->item->$handledKey) && $this->item->$handledKey) {
            $content[] = Html::tag('li', $this->createLink(
                new StateBadge($this->item->$handledKey, $state, true),
                [
                    $this->type . '.state.soft_state' => $this->getStateInt($state),
                    $this->type . '.state.is_handled' => 'y'
                ]
            ));
        }

        if (empty($content)) {
            return null;
        }

        return Html::tag('li', Html::tag('ul', $content));
    }
}
