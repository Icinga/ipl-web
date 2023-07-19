<?php

namespace ipl\Web;

use ipl\Html\Attribute;
use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;
use ipl\Html\HtmlString;
use ipl\Html\ValidHtml;
use Throwable;

class Style extends LessRuleset implements ValidHtml
{
    /** @var ?string */
    protected $module;

    /** @var ?string */
    protected $nonce;

    public function getNonce(): ?string
    {
        return $this->nonce;
    }

    public function setNonce(?string $nonce): self
    {
        $this->nonce = $nonce;

        return $this;
    }

    public function getModule(): ?string
    {
        return $this->module;
    }

    public function setModule(?string $name): self
    {
        $this->module = $name;

        return $this;
    }

    /**
     * Add given css properties for given element
     *
     * @param BaseHtmlElement $element Element for which the style is to apply
     * @param array $properties Css properties
     *
     * @return $this
     */
    public function addFor(BaseHtmlElement $element, array $properties): self
    {
        $id = $element->getAttribute('id')->getValue();

        if ($id === null) {
            $id = uniqid('csp-style', false);
            $element->setAttribute('id', $id);
        }

        return $this->add('#' . $id, $properties);
    }

    public function render(): string
    {
        if ($this->module !== null) {
            $ruleset = (new static())
                ->setSelector(".icinga-module.module-$this->module")
                ->addRuleset($this);
        } else {
            $ruleset = $this;
        }

        return (new HtmlElement(
            'style',
            (new Attributes())->addAttribute(new Attribute('nonce', $this->nonce)),
            HtmlString::create($ruleset->renderCss())
        ))->render();
    }

    /**
     * Render style to HTML when treated like a string
     *
     * @return string
     */
    public function __toString(): string
    {
        try {
            return $this->render();
        } catch (Throwable $e) {
            return sprintf('<!-- Failed to render style: %s -->', $e->getMessage());
        }
    }
}
