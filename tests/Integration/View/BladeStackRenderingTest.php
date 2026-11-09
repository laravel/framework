<?php

namespace Illuminate\Tests\Integration\View;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use Orchestra\Testbench\TestCase;

class BladeStackRenderingTest extends TestCase
{
    public function testPushAfterStackInTheSameViewIsRendered()
    {
        $html = $this->flatten(Blade::render(<<<'BLADE'
@push('scripts')
before
@endpush
[@stack('scripts')]
@push('scripts')
after
@endpush
BLADE));

        $this->assertSame('[before after ]', $html);
    }

    public function testComponentRenderedAfterStackStillPushes()
    {
        $html = View::make('welcome')->render();
        $flat = $this->flatten($html);

        $chatInView = strpos($flat, 'pushed from chat [view]');
        $fromView = strpos($flat, 'pushed from view');
        $chatInLayout = strpos($flat, 'pushed from chat [layout]');

        $this->assertNotFalse($chatInView);
        $this->assertNotFalse($fromView);
        $this->assertNotFalse($chatInLayout);
        $this->assertLessThan($fromView, $chatInView);
        $this->assertLessThan($chatInLayout, $fromView);
        $this->assertSame(2, substr_count($flat, 'The Chat'));
    }

    public function testExtendingLayoutIncludesPushesFromTheChildAndFromAfterTheStack()
    {
        $flat = $this->flatten(View::make('child')->render());

        $child = strpos($flat, 'from-child');
        $layout = strpos($flat, 'from-layout');

        $this->assertNotFalse($child);
        $this->assertNotFalse($layout);
        $this->assertLessThan($layout, $child);
        $this->assertStringContainsString('<div>page</div>', $flat);
    }

    public function testExplicitYieldPushContentIsNotDeferred()
    {
        $html = $this->flatten(Blade::render(<<<'BLADE'
@push('scripts')
hello
@endpush
({{ $__env->yieldPushContent('scripts') }})
@push('scripts')
world
@endpush
BLADE));

        $this->assertStringContainsString('(hello )', $html);
        $this->assertStringNotContainsString('world', $html);
    }

    /**
     * @param  string  $html
     * @return string
     */
    protected function flatten($html)
    {
        return trim(preg_replace('/\s+/', ' ', $html));
    }

    /** {@inheritdoc} */
    #[\Override]
    protected function defineEnvironment($app)
    {
        $app['config']->set('view.paths', [__DIR__.'/Fixtures/stacks']);
    }
}
