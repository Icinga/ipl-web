<?php

namespace ipl\Web\Common;

use Icinga\Web\UrlParams;
use ipl\Html\Form;
use ipl\Web\Compat\CompatController;
use ipl\Web\Control\LimitControl;
use ipl\Web\Control\PaginationControl;
use ipl\Web\Control\ViewModeSwitcher;
use ipl\Web\Url;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Provides factory methods to prepare reusable web controls and allows to handle their requests
 *
 * @phpstan-require-extends CompatController
 */
trait Controls
{
    /** @var Form[] Controls registered for handling by {@see self::handleControls()} */
    private array $trackedControls = [];

    /** @var ?Url Url to redirect to in {@see self::handleControls()} */
    private ?Url $redirectUrl = null;

    /**
     * Create a {@see ViewModeSwitcher} control
     *
     * The control is registered via {@see self::trackControl()} and gets a default {@see Form::ON_SUBMIT} handler
     * that writes the chosen view mode to the {@see self::getRedirectUrl()}.
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

        $viewModeSwitcher->on(ViewModeSwitcher::ON_SUBMIT, function (ViewModeSwitcher $switcher): void {
            $this->getRedirectUrl()->setParam($switcher->getViewModeParam(), $switcher->getViewMode());
        });

        return $viewModeSwitcher;
    }

    /**
     * Double the default item limit and page size for `minimal` view mode
     *
     * @param ViewModeSwitcher $viewModeSwitcher
     * @param LimitControl $limitControl
     * @param ?PaginationControl $paginationControl
     *
     * @return void
     */
    protected function applyViewModeLimit(
        ViewModeSwitcher $viewModeSwitcher,
        LimitControl $limitControl,
        ?PaginationControl $paginationControl = null
    ): void {
        if ($viewModeSwitcher->getViewMode() === 'minimal') {
            $limitControl->setDefaultLimit($limitControl->getDefaultLimit() * 2);

            $paginationControl
                ?->setDefaultPageSize($paginationControl->getDefaultPageSize() * 2)
                ->apply();
        }
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
     * Get the Url {@see self::handleControls()} redirects to
     *
     * @return Url
     */
    protected function getRedirectUrl(): Url
    {
        return $this->redirectUrl ??= Url::fromRequest();
    }

    /**
     * Call {@see Form::handleRequest()} on every control in {@see self::$trackedControls}. If {@see self::$redirectUrl}
     * has been set, a redirect is performed afterwards.
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

        if ($this->redirectUrl !== null) {
            $this->redirectNow($this->redirectUrl);
        }

        return $this;
    }
}
