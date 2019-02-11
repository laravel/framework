<?php

namespace Illuminate\Tests\View\Blade;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Illuminate\Filesystem\Filesystem;
use Illuminate\View\Compilers\BladeCompiler;

abstract class AbstractBladeTestCase extends TestCase
{
    protected $compiler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->compiler = new BladeCompiler(m::mock(Filesystem::class), __DIR__);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        m::close();
    }
}
