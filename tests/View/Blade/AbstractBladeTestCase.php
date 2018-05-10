<?php

namespace Illuminate\Tests\View\Blade;

use Illuminate\View\Compilers\BladeCompiler;
use Mockery as m;
use PHPUnit\Framework\TestCase;

abstract class AbstractBladeTestCase extends TestCase
{
    protected $compiler;

    public function setUp()
    {
        $this->compiler = new BladeCompiler(m::mock('Illuminate\Filesystem\Filesystem'), __DIR__);
        parent::setUp();
    }

    public function tearDown()
    {
        m::close();

        parent::tearDown();
    }
}
