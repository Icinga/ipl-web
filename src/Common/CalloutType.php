<?php

namespace ipl\Web\Common;

use ipl\Web\Widget\Icon;

/**
 * An enum containing all possible callout types for the {@see Callout} widget.
 */
enum CalloutType: string
{
    case Info = "info";
    case Success = "success";
    case Warning = "warning";
    case Error = "error";

    /**
     * Get the icon element for use in the callout
     *
     * @return Icon
     */
    public function getIcon(): Icon
    {
        return match ($this) {
            self::Info => new Icon('circle-info'),
            self::Success => new Icon('circle-check'),
            self::Warning => new Icon('warning'),
            self::Error => new Icon('circle-xmark'),
        };
    }
}
