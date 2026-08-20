<?php

namespace Illuminate\Tests\View\Blade;

use Illuminate\Filesystem\Filesystem;
use Illuminate\View\Compilers\BladeCompiler;
use Illuminate\View\Component;
use Mockery;
use PHPUnit\Framework\TestCase;

abstract class AbstractBladeTestCase extends TestCase
{
    /**
     * @var \Illuminate\View\Compilers\BladeCompiler
     */
    protected $compiler;

    protected function setUp(): void
    {
        $this->compiler = new BladeCompiler($this->getFiles(), __DIR__);
    }

    protected function tearDown(): void
    {
        Component::flushCache();
        Component::forgetComponentsResolver();
        Component::forgetFactory();
    }

    protected function getFiles()
    {
        return Mockery::mock(Filesystem::class);
    }
}
