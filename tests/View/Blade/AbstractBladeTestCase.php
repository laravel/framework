<?php

namespace Illuminate\Tests\View\Blade;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Illuminate\View\Compilers\BladeCompiler;

abstract class AbstractBladeTestCase extends TestCase
{
    protected $compiler;

    protected function setUp()
    {
        $this->compiler = new BladeCompiler(m::mock('Illuminate\Filesystem\Filesystem'), __DIR__);
        parent::setUp();
    }

    protected function tearDown()
    {
        m::close();

        parent::tearDown();
    }
}
