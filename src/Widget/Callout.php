<?php

namespace ipl\Web\Widget;

use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\Html\ValidHtml;
use ipl\I18n\Translation;
use ipl\Web\Common\CalloutType;

/**
 * An information box that can be used to display information to the user.
 * It consists of a set of standardized colors and icons that can be used to
 * visually distinguish the type of information. A content string and an
 * optional title can be passed to the constructor.
 */
class Callout extends BaseHtmlElement
{
    use Translation;

    /** @var string Classname for form-element callouts */
    protected const CLASS_FORM_ELEMENT = 'callout-form-element';

    /** @var string Classname for full-width callouts */
    protected const CLASS_FULL_WIDTH = 'callout-full-width';

    protected $tag = 'div';

    protected $defaultAttributes = ['class' => 'callout'];

    /**
     * Create a new callout
     *
     * The $type parameter determines the color and icon of the callout.
     *
     * @param CalloutType $type The type of the callout.
     * @param ValidHtml|string $content The content of the callout
     * @param string|null $title An optional title, displayed above the content
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
                $this->title
                    ? HtmlElement::create('strong', ['class' => 'callout-title'], new Text($this->title))
                    : null,
                is_string($this->content) ? new Text($this->content) : $this->content,
            ],
        ));
    }

    /**
     * Set the callout width to be 100% of its parent container
     *
     * Callouts are normally only as wide as their content.
     * Setting it to fullwidth will force the callout to be as wide as its container.
     *
     * @param bool $isFullWidth Whether the callout should be full width
     *
     * @return $this
     */
    public function setFullwidth(bool $isFullWidth = true): static
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
