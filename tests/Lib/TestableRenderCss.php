<?php

namespace ipl\Tests\Web\Lib;

use ipl\Web\LessRuleset;

trait TestableRenderCss
{
    public function renderCss(): string
    {
        return $this->renderLess();
    }
}
