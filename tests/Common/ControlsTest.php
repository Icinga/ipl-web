<?php

namespace ipl\Tests\Web\Common;

use Icinga\Web\UrlParams;
use ipl\Html\Form;
use ipl\I18n\NoopTranslator;
use ipl\I18n\StaticTranslator;
use ipl\Web\Common\Controls;
use ipl\Web\Control\ViewModeSwitcher;
use ipl\Tests\Web\TestCase;
use Psr\Http\Message\ServerRequestInterface;

class ControlsTest extends TestCase
{
    protected function setUp(): void
    {
        StaticTranslator::$instance = new NoopTranslator();
    }

    public function testCreateViewModeSwitcher(): void
    {
        if (! class_exists('Icinga\Web\UrlParams')) {
            $this->markTestSkipped('This test only runs locally');
        }

        $controller = $this->controls();

        $params = UrlParams::fromQueryString('view=minimal&foo=bar');
        $switcher = $controller->createViewModeSwitcher($params);

        $this->assertSame('minimal', $switcher->getViewMode(), 'The view mode should be populated from the param');
        $this->assertFalse($params->has('view'), 'The view mode param should be shifted out of the params');
        $this->assertSame('bar', $params->get('foo'), 'Other params should be left untouched');
        $this->assertContains($switcher, $controller->trackedControls(), 'The created switcher should be tracked');

        $customParams = UrlParams::fromQueryString('layout=detailed');
        $customSwitcher = $controller->createViewModeSwitcher($customParams, 'layout');

        $this->assertSame(
            'detailed',
            $customSwitcher->getViewMode(),
            'The view mode should be read from the custom param'
        );
        $this->assertFalse(
            $customParams->has('layout'),
            'The custom view mode param should be shifted out of the params'
        );
    }

    public function testCreateViewModeSwitcherResolvesTheDefaultViewMode(): void
    {
        if (! class_exists('Icinga\Web\UrlParams')) {
            $this->markTestSkipped('This test only runs locally');
        }

        $default = $this->controls()->createViewModeSwitcher(UrlParams::fromQueryString('foo=bar'));

        $this->assertSame(
            ViewModeSwitcher::DEFAULT_VIEW_MODE,
            $default->getViewMode(),
            'Without a param the default view mode should apply'
        );

        $default->setDefaultViewMode('detailed');

        $this->assertSame(
            'detailed',
            $default->getViewMode(),
            'A default set after creation should be honored, as it is read lazily'
        );

        $withParam = $this->controls()->createViewModeSwitcher(UrlParams::fromQueryString('view=minimal'));
        $withParam->setDefaultViewMode('detailed');

        $this->assertSame(
            'minimal',
            $withParam->getViewMode(),
            'A present param should take precedence over the default'
        );
    }

    public function testCreateViewModeSwitcherWithCustomClass(): void
    {
        if (! class_exists('Icinga\Web\UrlParams')) {
            $this->markTestSkipped('This test only runs locally');
        }

        $customClass = get_class(new class extends ViewModeSwitcher {
        });

        $switcher = $this->controls()->createViewModeSwitcher(
            UrlParams::fromQueryString('view=minimal'),
            viewModeSwitcherClass: $customClass
        );

        $this->assertInstanceOf($customClass, $switcher);
    }

    public function testCreateViewModeSwitcherThrowsOnClassThatIsNoViewModeSwitcher(): void
    {
        if (! class_exists('Icinga\Web\UrlParams')) {
            $this->markTestSkipped('This test only runs locally');
        }

        $this->expectException(InvalidArgumentException::class);

        $this->controls()->createViewModeSwitcher(
            UrlParams::fromQueryString(''),
            viewModeSwitcherClass: \stdClass::class
        );
    }

    public function testHandleControls(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);

        $first = $this->createMock(Form::class);
        $first->expects($this->once())->method('handleRequest')->with($request);

        $second = $this->createMock(Form::class);
        $second->expects($this->once())->method('handleRequest')->with($request);

        $controller = $this->controls();
        $controller->track($first);
        $controller->track($second);

        $this->assertSame(
            [$first, $second],
            $controller->trackedControls(),
            'Controls should be tracked in registration order'
        );

        $controller->handle($request);
    }

    /**
     * Get a controller using the {@see Controls} trait, with public seams onto its protected orchestration
     */
    private function controls(): object
    {
        return new class {
            use Controls;

            /** @return Form[] */
            public function trackedControls(): array
            {
                return $this->trackedControls;
            }

            public function track(Form $control): void
            {
                $this->trackControl($control);
            }

            public function handle(ServerRequestInterface $request): void
            {
                $this->handleControls($request);
            }
        };
    }
}
