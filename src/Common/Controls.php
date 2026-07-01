<?php

namespace ipl\Web\Common;

use Icinga\Web\UrlParams;
use ipl\Html\Form;
use ipl\Web\Control\ViewModeSwitcher;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Provides factory methods to prepare reusable web controls and allows to handle their requests
 */
trait Controls
{
    /** @var Form[] Controls registered for handling by {@see self::handleControls()} */
    private array $trackedControls = [];

    /**
     * Create a {@see ViewModeSwitcher} control
     *
     * @param UrlParams $params The url params; the view mode param is shifted out of them
     * @param ?string $viewModeParam Custom view mode param, null uses the default of the given class
     * @param class-string<ViewModeSwitcher> $viewModeSwitcherClass
     *
     * @return ViewModeSwitcher
     */
    public function createViewModeSwitcher(
        UrlParams $params,
        ?string $viewModeParam = null,
        string $viewModeSwitcherClass = ViewModeSwitcher::class
    ): ViewModeSwitcher {
        $viewModeSwitcher = new $viewModeSwitcherClass();
        if ($viewModeParam !== null) {
            $viewModeSwitcher->setViewModeParam($viewModeParam);
        }

        $viewModeSwitcher->populate([
            $viewModeSwitcher->getViewModeParam() => $params->shift($viewModeSwitcher->getViewModeParam())
        ]);

        $this->trackControl($viewModeSwitcher);

        return $viewModeSwitcher;
    }

    /**
     * Register the given control to be handled by {@see self::handleControls()}
     *
     * @param Form $control
     *
     * @return $this
     */
    protected function trackControl(Form $control): static
    {
        $this->trackedControls[] = $control;

        return $this;
    }

    /**
     * Call {@see Form::handleRequest()} on every control in {@see self::$trackedControls}
     *
     * @param ServerRequestInterface $request
     *
     * @return $this
     */
    protected function handleControls(ServerRequestInterface $request): static
    {
        foreach ($this->trackedControls as $control) {
            $control->handleRequest($request);
        }

        return $this;
    }
}
