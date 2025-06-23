<?php

namespace ipl\Web\Widget;

/**
 * Button like link generally pointing to CRUD actions
 */
class ButtonLink extends ActionLink
{
    protected $defaultAttributes = [
        'class'            => 'button-link',
        'data-base-target' => '_main'
    ];

    /** @var bool Whether the button is disabled */
    protected bool $isDisabled = false;

    /**
     * Set the disabled state of the button
     *
     * @param bool $disabled default true
     *
     * @return $this
     */
    public function setDisabled(bool $disabled = true): self
    {
        $this->isDisabled = $disabled;

        return $this;
    }

    /**
     * Get whether the button is disabled
     *
     * @return bool
     */
    public function isDisabled(): bool
    {
        return $this->isDisabled;
    }

    public function assemble(): void
    {
        if ($this->isDisabled()) {
            $this
                ->setTag('span')
                ->setUrl('#')
                ->getAttributes()->add('disabled', true);
        }

        parent::assemble();
    }
}
