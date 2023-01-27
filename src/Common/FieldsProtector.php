<?php

namespace ipl\Web\Common;

trait FieldsProtector
{
    /** @var callable */
    protected $protector;

    /**
     * Set callback to protect ids with
     *
     * @param ?callable $protector
     *
     * @return $this
     */
    public function setIdProtector(?callable $protector): self
    {
        $this->protector = $protector;

        return $this;
    }

    /**
     * Protect the given html id
     *
     * The provided id is returned as is, if no protector is specified
     *
     * @param mixed $id
     *
     * @return mixed
     */
    public function protectId($id)
    {
        if (is_callable($this->protector)) {
            return call_user_func($this->protector, $id);
        }

        return $id;
    }
}
