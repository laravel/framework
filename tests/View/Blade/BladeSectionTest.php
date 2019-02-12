<?php

namespace Illuminate\Tests\View\Blade;

class BladeSectionTest extends AbstractBladeTestCase
{
    public function testSectionStartsAreCompiled()
    {
        $this->assertEquals('<?php $__env->startSection(\'foo\'); ?>', $this->compiler->compileString('@section(\'foo\')'));
        $this->assertEquals('<?php $__env->startSection(name(foo)); ?>', $this->compiler->compileString('@section(name(foo))'));
    }
}
