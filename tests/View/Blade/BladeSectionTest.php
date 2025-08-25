<?php

namespace Illuminate\Tests\View\Blade;

class BladeSectionTest extends AbstractBladeTestCase
{
    public function testSectionStartsAreCompiled()
    {
        $this->assertSame('<?php $__env->startSection(\'foo\'); ?>', $this->compiler->compileString('@section(\'foo\')'));
        $this->assertSame('<?php $__env->startSection(\'issue#18317 :))\'); ?>', $this->compiler->compileString('@section(\'issue#18317 :))\')'));
        $this->assertSame('<?php $__env->startSection(name(foo)); ?>', $this->compiler->compileString('@section(name(foo))'));
    }
}
