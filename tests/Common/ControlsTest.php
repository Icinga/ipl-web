<?php

namespace ipl\Tests\Web\Common;

use Icinga\Application\EmbeddedWeb;
use Icinga\Application\Icinga;
use Icinga\Web\Request;
use ipl\Html\Form;
use ipl\I18n\NoopTranslator;
use ipl\I18n\StaticTranslator;
use ipl\Stdlib\Events;
use ipl\Tests\Web\TestCase;
use ipl\Web\Common\Controls;
use ipl\Web\Control\ViewModeSwitcher;
use ipl\Web\Control\ViewModeSwitcher\ViewMode;
use ipl\Web\Url;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;

/**
 * Stand-in for {@see \ipl\Web\Compat\CompatController}
 *
 * Provides what the {@see Controls} trait requires of its user, with public seams onto its protected orchestration
 */
class ControlsSubject
{
    use Events;
    use Controls {
        trackControl as public;
        getTrackedControl as public;
        handleControls as public;
    }

    /** @var ?Url The url the controls redirected to */
    public $redirectedTo = null;

    /**
     * Record the redirect, the real implementation ends the request here
     *
     * @param Url $url
     *
     * @return void
     */
    public function redirectNow($url)
    {
        $this->redirectedTo = $url;
    }
}

// phpcs:ignore PSR1.Classes.ClassDeclaration.MultipleClasses
class ControlsTest extends TestCase
{
    protected function setUp(): void
    {
        StaticTranslator::$instance = new NoopTranslator();
    }

    protected function tearDown(): void
    {
        unset($_SERVER['QUERY_STRING']);
    }

    public function testCreateViewModeSwitcherTracksTheSwitcher(): void
    {
        $controls = new ControlsSubject();
        $switcher = $controls->createViewModeSwitcher();

        $this->assertSame(
            $switcher,
            $controls->getTrackedControl(ViewModeSwitcher::class),
            'The created switcher should be tracked'
        );
    }

    public function testGetTrackedControlReturnsNullIfNoControlOfTheGivenTypeIsTracked(): void
    {
        $this->assertNull((new ControlsSubject())->getTrackedControl(ViewModeSwitcher::class));
    }

    public function testHandleControlsHandlesEveryTrackedControl(): void
    {
        $first = $this->createMock(Form::class);
        $first->expects($this->once())->method('handleRequest');

        $second = $this->createMock(Form::class);
        $second->expects($this->once())->method('handleRequest');

        $controls = new ControlsSubject();
        $controls->trackControl($first);
        $controls->trackControl($second);

        $controls->handleControls($this->request('GET'));
    }

    public function testTheSwitcherIsPopulatedWithTheViewModeFromTheUrl(): void
    {
        $controls = new ControlsSubject();
        $switcher = $controls->createViewModeSwitcher();

        $controls->handleControls($this->request('GET', ['view' => 'minimal', 'foo' => 'bar']));

        $this->assertSame(
            'minimal',
            $switcher->getViewMode()->getName(),
            'The view mode should be populated from the url'
        );
    }

    public function testTheDefaultViewModeAppliesIfTheUrlHasNoViewModeParam(): void
    {
        $controls = new ControlsSubject();
        $switcher = $controls->createViewModeSwitcher();

        $controls->handleControls($this->request('GET'));

        $this->assertSame(
            ViewModeSwitcher::DEFAULT_VIEW_MODE,
            $switcher->getViewMode()->getName(),
            'Without a param the default view mode should apply'
        );
    }

    public function testAnUnknownViewModeInTheUrlFallsBackToTheDefault(): void
    {
        $controls = new ControlsSubject();
        $switcher = $controls->createViewModeSwitcher();

        $controls->handleControls($this->request('GET', ['view' => 'no-such-mode']));

        $this->assertSame(
            ViewModeSwitcher::DEFAULT_VIEW_MODE,
            $switcher->getViewMode()->getName(),
            'An unknown view mode should not be adopted'
        );
    }

    public function testTheDefaultViewModeIsResolvedLazily(): void
    {
        $controls = new ControlsSubject();
        $switcher = $controls->createViewModeSwitcher();

        $controls->handleControls($this->request('GET'));
        $switcher->setDefaultViewMode('detailed');

        $this->assertSame(
            'detailed',
            $switcher->getViewMode()->getName(),
            'A default set after the request has been handled should be honored, as it is read lazily'
        );
    }

    public function testAViewModeInTheUrlWinsOverTheDefaultViewMode(): void
    {
        $controls = new ControlsSubject();
        $switcher = $controls->createViewModeSwitcher();

        $controls->handleControls($this->request('GET', ['view' => 'minimal']));
        $switcher->setDefaultViewMode('detailed');

        $this->assertSame(
            'minimal',
            $switcher->getViewMode()->getName(),
            'A present param should win over the default'
        );
    }

    public function testACustomViewModeParamIsRespected(): void
    {
        $controls = new ControlsSubject();
        $switcher = $controls->createViewModeSwitcher();
        $switcher->setViewModeParam('layout');

        $controls->handleControls($this->request('GET', ['layout' => 'detailed', 'view' => 'minimal']));

        $this->assertSame(
            'detailed',
            $switcher->getViewMode()->getName(),
            'The view mode should be read from the custom param'
        );
    }

    public function testViewModeSetIsEmittedWithThePopulatedSwitcher(): void
    {
        $controls = new ControlsSubject();
        $switcher = $controls->createViewModeSwitcher();

        $viewModeName = null;
        $emittedFor = null;
        $controls->on(
            ControlsSubject::ON_VIEW_MODE_SET,
            function (ViewModeSwitcher $switcher) use (&$viewModeName, &$emittedFor): void {
                $viewModeName = $switcher->getViewMode()->getName();
                $emittedFor = $switcher;
            }
        );

        $controls->handleControls($this->request('GET', ['view' => 'detailed']));

        $this->assertSame($switcher, $emittedFor, 'The event should be emitted with the created switcher');
        $this->assertSame(
            'detailed',
            $viewModeName,
            'The switcher should already be populated when the event is emitted'
        );
    }

    public function testChangingTheViewModeRedirectsWithTheNewViewMode(): void
    {
        $this->expectRedirect(['view' => 'common']);

        $controls = new ControlsSubject();
        $controls->createViewModeSwitcher();

        $controls->handleControls($this->submitViewMode('minimal', ['view' => 'common']));

        $this->assertNotNull($controls->redirectedTo, 'A changed view mode should redirect');
        $this->assertSame(
            'minimal',
            $controls->redirectedTo->getParam('view'),
            'The new view mode should be redirected to'
        );
    }

    public function testKeepingTheViewModeDoesNotRedirect(): void
    {
        $controls = new ControlsSubject();
        $controls->createViewModeSwitcher();

        $controls->handleControls($this->submitViewMode('minimal', ['view' => 'minimal']));

        $this->assertNull($controls->redirectedTo, 'Submitting the active view mode should not redirect');
    }

    public function testViewModeChangeIsEmittedWithThePreviousViewMode(): void
    {
        $this->expectRedirect(['view' => 'minimal']);

        $controls = new ControlsSubject();
        $controls->createViewModeSwitcher();

        $previousViewModeName = null;
        $newViewModeName = null;
        $controls->on(
            ControlsSubject::ON_VIEW_MODE_CHANGE,
            function (
                ViewModeSwitcher $switcher,
                ViewMode $previous
            ) use (
                &$previousViewModeName,
                &$newViewModeName
            ): void {
                $previousViewModeName = $previous->getName();
                $newViewModeName = $switcher->getViewMode()->getName();
            }
        );

        $controls->handleControls($this->submitViewMode('detailed', ['view' => 'minimal']));

        $this->assertSame('minimal', $previousViewModeName, 'The view mode of the url should be the previous one');
        $this->assertSame('detailed', $newViewModeName, 'The switcher should already provide the new view mode');
    }

    public function testTheDefaultViewModeIsUsedAsThePreviousViewMode(): void
    {
        $this->expectRedirect();

        $controls = new ControlsSubject();
        $switcher = $controls->createViewModeSwitcher();
        $switcher->setDefaultViewMode('detailed');

        $previousViewModeName = null;
        $controls->on(
            ControlsSubject::ON_VIEW_MODE_CHANGE,
            function (ViewModeSwitcher $switcher, ViewMode $previous) use (&$previousViewModeName): void {
                $previousViewModeName = $previous->getName();
            }
        );

        $controls->handleControls($this->submitViewMode('minimal'));

        $this->assertSame(
            'detailed',
            $previousViewModeName,
            'The default view mode should be the previous one, if the url has no view mode param'
        );
    }

    public function testSubmittingTheDefaultViewModeWithoutAViewModeParamDoesNotRedirect(): void
    {
        $controls = new ControlsSubject();
        $controls->createViewModeSwitcher();

        $controls->handleControls($this->submitViewMode(ViewModeSwitcher::DEFAULT_VIEW_MODE));

        $this->assertNull(
            $controls->redirectedTo,
            'Submitting the view mode that is already shown should not redirect, even if the url does not name it'
        );
    }

    public function testViewModeChangeIsNotEmittedIfTheViewModeStaysTheSame(): void
    {
        $controls = new ControlsSubject();
        $controls->createViewModeSwitcher();

        $emitted = false;
        $controls->on(ControlsSubject::ON_VIEW_MODE_CHANGE, function () use (&$emitted): void {
            $emitted = true;
        });

        $controls->handleControls($this->submitViewMode('minimal', ['view' => 'minimal']));

        $this->assertFalse($emitted, 'An unchanged view mode should not emit the event');
    }

    public function testViewModeChangeIsEmittedBeforeTheRedirect(): void
    {
        $this->expectRedirect(['view' => 'common']);

        $controls = new ControlsSubject();
        $controls->createViewModeSwitcher();

        // Assertions must not be made in the listener. Form::handleRequest() catches everything an
        // ON_SUBMIT listener throws, which would let a failing assertion pass unnoticed.
        $redirectedWhenEmitted = null;
        $controls->on(
            ControlsSubject::ON_VIEW_MODE_CHANGE,
            function () use ($controls, &$redirectedWhenEmitted): void {
                $redirectedWhenEmitted = $controls->redirectedTo !== null;
            }
        );

        $controls->handleControls($this->submitViewMode('minimal', ['view' => 'common']));

        $this->assertNotNull($redirectedWhenEmitted, 'The view mode change should have been emitted');
        $this->assertFalse(
            $redirectedWhenEmitted,
            'Listeners registered after the factory must run before the redirect'
        );
    }

    public function testListenersMayAdjustTheRedirectUrl(): void
    {
        $this->expectRedirect(['view' => 'common', 'foo' => 'bar']);

        $controls = new ControlsSubject();
        $controls->createViewModeSwitcher();

        $controls->on(
            ControlsSubject::ON_VIEW_MODE_CHANGE,
            function (ViewModeSwitcher $switcher, ViewMode $previous, Url $redirectUrl): void {
                $redirectUrl->setParam('page', '2')
                    ->remove('foo');
            }
        );

        $controls->handleControls($this->submitViewMode('minimal', ['view' => 'common', 'foo' => 'bar']));

        $this->assertNotNull($controls->redirectedTo, 'A changed view mode should redirect');
        $this->assertSame('2', $controls->redirectedTo->getParam('page'), 'Listeners should be able to add params');
        $this->assertFalse($controls->redirectedTo->hasParam('foo'), 'Listeners should be able to remove params');
        $this->assertSame(
            'minimal',
            $controls->redirectedTo->getParam('view'),
            'The new view mode should still be applied'
        );
    }

    /**
     * Get a request submitting the given view mode
     *
     * Tests that expect a redirect have to call {@see self::expectRedirect()} first
     *
     * @param string $viewMode
     * @param array<string, mixed> $queryParams
     *
     * @return ServerRequestInterface
     */
    private function submitViewMode(string $viewMode, array $queryParams = []): ServerRequestInterface
    {
        return $this->request('POST', $queryParams, ['uid' => 'view-mode-switcher', 'view' => $viewMode]);
    }

    /**
     * Get a request mock
     *
     * @param string $method
     * @param array<string, mixed> $queryParams
     * @param array<string, mixed> $body
     *
     * @return ServerRequestInterface
     */
    private function request(string $method, array $queryParams = [], array $body = []): ServerRequestInterface
    {
        return $this->createConfiguredMock(ServerRequestInterface::class, [
            'getMethod' => $method,
            'getUploadedFiles' => [],
            'getQueryParams' => $queryParams,
            'getParsedBody' => $body,
            'getUri' => $this->createConfiguredMock(UriInterface::class, [
                'getQuery' => http_build_query($queryParams)
            ])
        ]);
    }

    /**
     * Set up just enough of Icinga Web to let the redirect's {@see Url::fromRequest()} work
     *
     * Skips the test if Icinga Web is not available, as {@see Url} cannot even be loaded without it
     *
     * @param array<string, mixed> $queryParams The query params the request is expected to be redirected from
     *
     * @return void
     */
    private function expectRedirect(array $queryParams = []): void
    {
        if (! class_exists(Icinga::class)) {
            $this->markTestSkipped('Redirects require Icinga Web');
        }

        $_SERVER['QUERY_STRING'] = http_build_query($queryParams);

        Icinga::setApp(
            $this->createConfiguredMock(EmbeddedWeb::class, [
                'getRequest' => $this->createConfiguredMock(Request::class, [
                    'getPathInfo' => '/test',
                    'getBaseUrl' => '/'
                ])
            ]),
            true
        );
    }
}
