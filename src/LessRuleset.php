<?php

namespace ipl\Web;

use ArrayObject;
use Less_Parser;

class LessRuleset extends ArrayObject
{
    /** @var ?string */
    protected $selector;

    /** @var list<LessRuleset> */
    protected $children = [];

    public static function create(string $selector, array $properties): self
    {
        $ruleset = new static();
        $ruleset->selector = $selector;
        $ruleset->exchangeArray($properties);

        return $ruleset;
    }

    public function getSelector(): ?string
    {
        return $this->selector;
    }

    public function setSelector(string $selector): self
    {
        $this->selector = $selector;

        return $this;
    }

    public function getProperty(string $property): string
    {
        return $this[$property];
    }

    public function setProperty(string $property, string $value): self
    {
        $this[$property] = $value;

        return $this;
    }

    public function getProperties(): array
    {
        return $this->getArrayCopy();
    }

    public function setProperties(array $properties): self
    {
        $this->exchangeArray($properties);

        return $this;
    }

    public function add(string $selector, array $properties): self
    {
        $this->children[] = static::create($selector, $properties);

        return $this;
    }

    public function addRuleset(LessRuleset $ruleset): self
    {
        $this->children[] = $ruleset;

        return $this;
    }

    public function renderCss(): string
    {
        $parser = new Less_Parser(['compress' => true]);
        $parser->parse($this->renderLess());

        return $parser->getCss();
    }

    protected function renderLess(): string
    {
        $less = [];

        foreach ($this as $property => $value) {
            $less[] = "$property: $value;";
        }

        foreach ($this->children as $ruleset) {
            $less[] = $ruleset->renderLess();
        }

        if ($this->selector !== null) {
            array_unshift($less, "$this->selector {");
            $less[] = '}';
        }

        return implode("\n", $less);
    }
}
