<?php

namespace Illuminate\Tests\View\Blade;

use Illuminate\Filesystem\Filesystem;
use Illuminate\View\Compilers\BladeCompiler;
use Mockery as m;
use PHPUnit\Framework\TestCase;

abstract class AbstractBladeTestCase extends TestCase
{
    protected $compiler;

    protected function setUp(): void
    {
        $this->compiler = new BladeCompiler(m::mock(Filesystem::class), __DIR__);
        parent::setUp();
    }

    protected function tearDown(): void
    {
        m::close();

        parent::tearDown();
    }
}
