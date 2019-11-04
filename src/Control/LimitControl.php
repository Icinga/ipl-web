<?php

namespace ipl\Web\Control;

use ipl\Web\Compat\CompatForm;
use ipl\Web\Url;

/**
 * Allows to adjust the limit of the number of items to display
 */
class LimitControl extends CompatForm
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

    /** @var Url */
    protected $url;

    /** @var string Name of the URL parameter which stores the limit */
    protected $limitParam = 'limit';

    protected $method = 'GET';

    public function __construct(Url $url)
    {
        $this->url = $url;
    }

    /**
     * Get the name of the URL parameter which stores the limit
     *
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

    /**
     * Get the limit
     *
     * @return int
     */
    public function getLimit()
    {
        return $this->url->getParam($this->getLimitParam(), static::DEFAULT_LIMIT);
    }

    protected function assemble()
    {
        // TODO(el): Remove 'method' => 'GET' once ipl-html supports this out of the box
        $this->addAttributes(['class' => 'limit-control inline', 'method' => 'GET']);

        $limit = $this->getLimit();
        $limits = static::$limits;
        if (! isset($limits[$limit])) {
            $limits[$limit] = $limit;
        }

        $this->addElement('select', $this->getLimitParam(), [
            'class'   => 'autosubmit',
            'label'   => '#',
            'options' => $limits,
            'value'   => $limit
        ]);
    }
}
