<?php

namespace ipl\Web\Widget;

use InvalidArgumentException;
use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\Html\ValidHtml;
use ipl\Web\Common\CalloutType;

/**
 * Information box with a type specific color and icon
 *
 * The type controls both the color scheme and the icon. An optional title
 * is displayed above the content.
 */
class Callout extends BaseHtmlElement
{
    /** @var string Class name for form element callouts */
    protected const CLASS_FORM_ELEMENT = 'callout-form-element';

    /** @var string Class name for full width callouts */
    protected const CLASS_FULL_WIDTH = 'callout-full-width';

    protected $tag = 'div';

    protected $defaultAttributes = ['class' => 'callout'];

    /**
     * Create a new callout
     *
     * The $type parameter determines the color and icon of the callout.
     *
     * @param CalloutType $type The type of the callout
     * @param ValidHtml|string $content The content of the callout
     * @param ?string $title An optional title, displayed above the content
     */
    public function __construct(
        protected CalloutType $type,
        protected ValidHtml|string $content,
        protected ?string $title = null
    ) {
        $this->addAttributes(Attributes::create(['class' => $type->value]));
    }

    protected function assemble(): void
    {
        $this->addHtml($this->type->getIcon());

        $this->addHtml(HtmlElement::create(
            'div',
            ['class' => 'callout-text'],
            [
                $this->title !== null
                    ? HtmlElement::create('strong', ['class' => 'callout-title'], Text::create($this->title))
                    : null,
                is_string($this->content) ? Text::create($this->content) : $this->content,
            ],
        ));
    }

    /**
     * Set the callout width to 100% of its parent container
     *
     * Callouts are normally only as wide as their content.
     * Setting it to full width will force the callout to be as wide as its container.
     *
     * @param bool $isFullWidth Whether the callout should be full width
     *
     * @return $this
     */
    public function setFullWidth(bool $isFullWidth = true): static
    {
        if ($isFullWidth) {
            $this->addAttributes(Attributes::create(['class' => static::CLASS_FULL_WIDTH]));
        } else {
            $this->removeAttribute('class', static::CLASS_FULL_WIDTH);
        }

        return $this;
    }

    /**
     * Set up the callout to be used inside a form
     *
     * Setting this to true will allow the callout to be used for a single form element.
     * This is used to visually align the callout to the content of the form element.
     *
     * @param bool $isFormElement Whether the callout should be used for a form element
     *
     * @return $this
     */
    public function setFormElement(bool $isFormElement = true): static
    {
        if ($isFormElement) {
            $this->addAttributes(Attributes::create(['class' => static::CLASS_FORM_ELEMENT]));
        } else {
            $this->removeAttribute('class', static::CLASS_FORM_ELEMENT);
        }

        return $this;
    }
}
