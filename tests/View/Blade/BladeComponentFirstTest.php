<?php

namespace Illuminate\Tests\View\Blade;

class BladeComponentFirstTest extends AbstractBladeTestCase
{
    public function testComponentFirstsAreCompiled()
    {
        $this->assertEquals('<?php $__env->startComponentFirst(["one", "two"]); ?>', $this->compiler->compileString('@componentFirst(["one", "two"])'));
        $this->assertEquals('<?php $__env->startComponentFirst(["one", "two"], ["foo" => "bar"]); ?>', $this->compiler->compileString('@componentFirst(["one", "two"], ["foo" => "bar"])'));
    }
}
