<?php

namespace ipl\Web\Widget;

use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\I18n\Translation;
use ipl\Web\Common\CalloutType;

/**
 * An information box that can be used to display information to the user.
 * It consists of a set of standardized colors and icons that can be used to visually distinguish the type of
 * information.
 * A content string and an optional title can be passed to the constructor.
 */
class Callout extends BaseHtmlElement
{
    use Translation;

    protected $tag = 'div';

    protected $defaultAttributes = ['class' => 'callout'];

    /** @var string|null An optional title */
    protected ?string $title;

    /** @var string The content to display */
    protected string $content;

    /** @var CalloutType The type of callout, determines the color and icon */
    protected CalloutType $type;

    public function __construct(CalloutType $type, string $content, ?string $title = null)
    {
        $this->type = $type;
        $this->content = $content;
        $this->title = $title;

        $this->addAttributes(Attributes::create(['class' => $type->value]));
    }

    public function assemble(): void
    {
        $this->addHtml($this->type->getIcon());

        if ($this->title) {
            $this->addHtml(HtmlElement::create(
                'div',
                ['class' => 'callout-text'],
                [
                    HtmlElement::create('strong', null, new Text($this->title)),
                    HtmlElement::create('p', null, new Text($this->content)),
                ],
            ));
        } else {
            $this->addHtml(HtmlElement::create(
                'div',
                ['class' => 'callout-text'],
                HtmlElement::create('strong', null, new Text($this->content)),
            ));
        }
    }

    /**
     * Callouts are only as wide as their content.
     * Setting it to fullwidth will force the callout to be as wide as its container.
     *
     * @param bool $fullwidth should the callout be fullwidth
     *
     * @return $this
     */
    public function setFullwidth(bool $fullwidth = true): static
    {
        if ($fullwidth) {
            $this->addAttributes(Attributes::create(['class' => 'fullwidth']));
        } else {
            $this->removeAttribute('class', 'fullwidth');
        }

        return $this;
    }

    /**
     * Setting this to true will allow the callout to be used for a single form element.
     * This is used to visually align the callout to the content of the form element.
     *
     * @param bool $isFormElement should the callout be used for a form element
     *
     * @return $this
     */
    public function setFormElement(bool $isFormElement = true): static
    {
        if ($isFormElement) {
            $this->addAttributes(Attributes::create(['class' => 'form-callout']));
        } else {
            $this->removeAttribute('class', 'from-callout');
        }

        return $this;
    }
}
