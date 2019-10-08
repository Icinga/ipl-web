<?php

namespace ipl\Web\Control;

use ipl\Html\Form;
use ipl\Html\FormDecorator\DivDecorator;

/**
 * Allows to adjust the limit of the number of items to display
 */
class LimitControl extends Form
{
    /** @var int Default limit */
    const DEFAULT_LIMIT = 25;

    /** @var int[] Selectable default limits */
    public static $limits = [
        '25'  => '25',
        '50'  => '50',
        '100' => '100',
        '500' => '500'
    ];

    /** @var string Name of the URL parameter which stores the limit */
    protected $limitParam = 'limit';

    // TODO(el): Remove 'method' => 'GET' once ipl-html supports this out of the box
    protected $defaultAttributes = ['class' => 'limit-control', 'method' => 'GET'];

    protected $method = 'GET';

    /**
     * Get the name of the URL parameter which stores the limit
     * @return string
     */
    public function getLimitParam()
    {
        return $this->limitParam;
    }

    /**
     * Set the name of the URL parameter which stores the limit
     *
     * @param string $limitParam
     *
     * @return $this
     */
    public function setLimitParam($limitParam)
    {
        $this->limitParam = $limitParam;

        return $this;
    }

    protected function assemble()
    {
        $this->setDefaultElementDecorator(new DivDecorator());

        $this->addElement('select', $this->getLimitParam(), [
            'class'   => 'autosubmit',
            'label'   => '#',
            'options' => static::$limits,
            'value'   => static::DEFAULT_LIMIT
        ]);
    }
}
