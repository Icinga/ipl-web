<?php

namespace ipl\Web\Widget;

use Exception;
use InvalidArgumentException;
use ipl\Html\ValidHtml;

/**
 * @TODO(el): Don't depend on Icinga Web's Tabs
 */
class Tabs extends \Icinga\Web\Widget\Tabs implements ValidHtml
{
    /**
     * Activate the tab with the given name
     *
     * @param string $name
     *
     * @return $this
     *
     * @throws InvalidArgumentException
     */
    public function activate($name)
    {
        try {
            parent::activate($name);
        } catch (Exception $e) {
            throw new InvalidArgumentException($e->getMessage());
        }

        return $this;
    }

    /**
     * Add the given tab
     *
     * @param string $name
     * @param mixed  $tab
     *
     * @return $this
     *
     * @throws InvalidArgumentException
     */
    public function add($name, $tab)
    {
        try {
            parent::add($name, $tab);
        } catch (Exception $e) {
            throw new InvalidArgumentException($e->getMessage());
        }

        return $this;
    }
}
