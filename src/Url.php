<?php

namespace ipl\Web;

use ipl\Stdlib\Filter\Rule;
use ipl\Web\Filter\QueryString;

/**
 * @TODO(el): Don't depend on Icinga Web's Url
 */
class Url extends \Icinga\Web\Url
{
    /**
     * Set the given filter and preserve existing query parameters
     *
     * @param Rule $filter
     *
     * @return $this
     */
    public function setFilter(Rule $filter): self
    {
        $existingParams = $this->getParams();
        $this->setQueryString(QueryString::render($filter));
        foreach ($existingParams->toArray(false) as $name => $value) {
            if (is_int($name)) {
                $name = $value;
                $value = true;
            }

            $this->getParams()->addEncoded($name, $value);
        }

        return $this;
    }

    public function __toString()
    {
        return $this->getAbsoluteUrl('&');
    }
}
