<?php

namespace Illuminate\Tests\Integration\Foundation\Exceptions\Renderer;

use Orchestra\Testbench\Attributes\WithConfig;
use Orchestra\Testbench\TestCase;

use function Orchestra\Testbench\after_resolving;
use function Orchestra\Testbench\package_path;

#[WithConfig('app.debug', true)]
class RenderBladeFilesTest extends TestCase
{
    protected function defineEnvironment($app)
    {
        after_resolving($app, 'view.engine.resolver', function ($resolver) {
            $resolver->resolve('blade')->getCompiler()->withoutComponentTags();
        });
    }

    public function testFormattedSourceTooltipRendersMultilineSafely(): void
    {
        $frame = new class
        {
            public function class()
            {
                return null;
            }

            public function previous()
            {
                return null;
            }

            public function source()
            {
                return "Foo::bar(1)\nAnother line";
            }
        };

        $path = package_path('src/Illuminate/Foundation/resources/exceptions/renderer/components/formatted-source.blade.php');

        $html = (string) $this->app['view']->file($path, ['frame' => $frame])->render();

        $this->assertStringContainsString('data-tippy-content="', $html);
        $this->assertStringNotContainsString('<br', $html);
    }

    public function testQueryTooltipRendersMultilineSafely(): void
    {
        $sql = "SELECT * FROM tests\nWHERE id = 1";
        $queries = [['connectionName' => 'mysql', 'sql' => $sql, 'time' => 1.23]];

        $path = package_path('src/Illuminate/Foundation/resources/exceptions/renderer/components/query.blade.php');

        $html = (string) $this->app['view']->file($path, ['queries' => $queries])->render();

        $this->assertStringContainsString('data-tippy-content="', $html);
        $this->assertMatchesRegularExpression('/&lt;br\s*\/?&gt;/', $html);
    }

    public function testRequestHeaderTooltipRendersMultilineSafely(): void
    {
        $headers = ['X-Test' => "A\nB<script>bad()</script>"];

        $path = package_path('src/Illuminate/Foundation/resources/exceptions/renderer/components/request-header.blade.php');

        $html = (string) $this->app['view']->file($path, ['headers' => $headers])->render();

        $this->assertStringContainsString('data-tippy-content="', $html);
        $this->assertStringNotContainsString('<br', $html);
        $this->assertStringContainsString('&lt;script&gt;bad()&lt;/script&gt;', $html);
    }

    public function testRoutingTooltipRendersMultilineSafely(): void
    {
        $routing = ['URI' => "users/1\nedit"];

        $path = package_path('src/Illuminate/Foundation/resources/exceptions/renderer/components/routing.blade.php');

        $html = (string) $this->app['view']->file($path, ['routing' => $routing])->render();

        $this->assertStringContainsString('data-tippy-content="', $html);
        $this->assertStringNotContainsString('<br', $html);
    }
}
