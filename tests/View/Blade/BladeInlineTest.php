<?php

namespace Illuminate\Tests\View\Blade;

use Illuminate\Container\Container;
use Illuminate\Filesystem\Filesystem;
use Illuminate\View\Compilers\BladeCompiler;
use Illuminate\View\FileViewFinder;
use Mockery as m;

class BladeInlineTest extends AbstractBladeTestCase
{
    protected $files;

    protected function setUp(): void
    {
        $this->files = m::mock(Filesystem::class);

        $this->files->shouldReceive('get')
            ->with('/views/partials/greeting.blade.php')
            ->andReturn('Hello, {{ $name }}!');

        $this->files->shouldReceive('get')
            ->with('/views/partials/props.blade.php')
            ->andReturn("@props(['name'])\nHello, {{ \$name }}!");

        $this->files->shouldReceive('get')
            ->with('/views/partials/card.blade.php')
            ->andReturn('<div>{{ $title }}</div>');

        $this->compiler = new BladeCompiler($this->files, __DIR__);

        $container = Container::setInstance(new Container);

        $finder = m::mock(FileViewFinder::class);
        $finder->shouldReceive('find')->with('partials.greeting')->andReturn('/views/partials/greeting.blade.php');
        $finder->shouldReceive('find')->with('partials.props')->andReturn('/views/partials/props.blade.php');
        $finder->shouldReceive('find')->with('partials.card')->andReturn('/views/partials/card.blade.php');

        $container->instance('view.finder', $finder);
    }

    protected function tearDown(): void
    {
        Container::setInstance(null);
        m::close();

        parent::tearDown();
    }

    public function testInlineIsCompiled()
    {
        $result = $this->compiler->compileString("@inline('partials.greeting')");

        $this->assertStringContainsString('<?php echo e($name); ?>', $result);
        $this->assertStringContainsString('Hello, ', $result);
    }

    public function testInlineWithDataArray()
    {
        $result = $this->compiler->compileString("@inline('partials.card', ['title' => \$item->name])");

        $this->assertStringContainsString("extract(['title' => \$item->name]);", $result);
        $this->assertStringContainsString('<?php echo e($title); ?>', $result);
    }

    public function testInlineStripsPropsDirective()
    {
        $result = $this->compiler->compileString("@inline('partials.props')");

        $this->assertStringNotContainsString('@props', $result);
        $this->assertStringContainsString('Hello, ', $result);
        $this->assertStringContainsString('<?php echo e($name); ?>', $result);
    }

    public function testInlinePreservesSurroundingContent()
    {
        $result = $this->compiler->compileString("Before @inline('partials.greeting') After");

        $this->assertStringStartsWith('Before ', $result);
        $this->assertStringEndsWith(' After', $result);
        $this->assertStringContainsString('<?php echo e($name); ?>', $result);
    }
}
