<?php

namespace Illuminate\Tests\View\Blade;

use Illuminate\Container\Container;
use Illuminate\Filesystem\Filesystem;
use Illuminate\View\Compilers\BladeCompiler;
use Illuminate\View\Component;
use Mockery as m;
use PHPUnit\Framework\TestCase;

abstract class AbstractBladeTestCase extends TestCase
{
    /**
     * @var \Illuminate\View\Compilers\BladeCompiler
     */
    protected $compiler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->compiler = new BladeCompiler($this->getFiles(), __DIR__);
    }

    protected function tearDown(): void
    {
        Container::setInstance(null);
        Component::flushCache();
        Component::forgetComponentsResolver();
        Component::forgetFactory();

        m::close();

        parent::tearDown();
    }

    protected function getFiles()
    {
        return m::mock(Filesystem::class);
    }
}
