<?php

namespace Illuminate\Tests\Integration\View;

use Illuminate\Support\EncodedHtmlString;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use Orchestra\Testbench\Attributes\WithCachedViews;
use Orchestra\Testbench\TestCase;

use function Illuminate\Filesystem\join_paths;

#[WithCachedViews]
class BladeCachedViewsTest extends TestCase
{
    /** {@inheritDoc} */
    #[\Override]
    protected function tearDown(): void
    {
        EncodedHtmlString::flushState();

        parent::tearDown();
    }

    public function test_compiled_path_differs_when_echo_format_changes()
    {
        $compiler = Blade::getFacadeRoot();
        $path = join_paths(__DIR__, 'templates', 'uses-link.blade.php');

        $default = $compiler->getCompiledPath($path);

        $custom = $compiler->usingEchoFormat(
            'new \Illuminate\Support\EncodedHtmlString(%s)',
            fn () => $compiler->getCompiledPath($path)
        );

        $this->assertNotSame($default, $custom);
        $this->assertSame($default, $compiler->getCompiledPath($path));
        $this->assertStringEndsWith('.php', $custom);
    }

    public function test_cached_view_is_not_reused_for_a_different_echo_format()
    {
        $compiler = Blade::getFacadeRoot();
        $path = join_paths(__DIR__, 'templates', 'echo-format.blade.php');

        @mkdir(dirname($path), 0777, true);
        file_put_contents($path, '{{ $value }}');

        try {
            $value = '[link](https://evil.example)';

            // Compile with the default echo format, as "view:cache" does.
            $compiler->compile($path);
            $this->assertFileExists($default = $compiler->getCompiledPath($path));

            $rendered = $compiler->usingEchoFormat(
                'new \Illuminate\Support\EncodedHtmlString(%s)',
                function () use ($compiler, $path, $default, $value) {
                    EncodedHtmlString::encodeUsing(fn ($string) => 'SECURED:'.$string);

                    $this->assertNotSame($default, $compiler->getCompiledPath($path));

                    return View::file($path, ['value' => $value])->render();
                }
            );

            $this->assertSame('SECURED:'.$value, $rendered);
        } finally {
            @unlink($path);
        }
    }
}
