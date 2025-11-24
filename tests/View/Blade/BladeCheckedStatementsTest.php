<?php

namespace Illuminate\Tests\View\Blade;

class BladeCheckedStatementsTest extends AbstractBladeTestCase
{
    public function testSelectedStatementsAreCompiled()
    {
        $string = '<input @selected(name(foo(bar)))/>';
        $expected = "<input <?php if(name(foo(bar))): echo 'selected'; endif; ?>/>";

        $this->assertEquals($expected, $this->compiler->compileString($string));
    }

    public function testCheckedStatementsAreCompiled()
    {
        $string = '<input @checked(name(foo(bar)))/>';
        $expected = "<input <?php if(name(foo(bar))): echo 'checked'; endif; ?>/>";

        $this->assertEquals($expected, $this->compiler->compileString($string));
    }

    public function testDisabledStatementsAreCompiled()
    {
        $string = '<button @disabled(name(foo(bar)))>Foo</button>';
        $expected = "<button <?php if(name(foo(bar))): echo 'disabled'; endif; ?>>Foo</button>";

        $this->assertEquals($expected, $this->compiler->compileString($string));
    }

    public function testRequiredStatementsAreCompiled()
    {
        $string = '<input @required(name(foo(bar)))/>';
        $expected = "<input <?php if(name(foo(bar))): echo 'required'; endif; ?>/>";

        $this->assertEquals($expected, $this->compiler->compileString($string));
    }

    public function testReadonlyStatementsAreCompiled()
    {
        $string = '<input @readonly(name(foo(bar)))/>';
        $expected = "<input <?php if(name(foo(bar))): echo 'readonly'; endif; ?>/>";

        $this->assertEquals($expected, $this->compiler->compileString($string));
    }

    public function testActiveStatementsAreCompiled()
    {
        $string = '<a @active(request()->routeIs(\'home\'))>Home</a>';
        $expected = "<a <?php if(request()->routeIs('home')): echo 'active'; endif; ?>>Home</a>";

        $this->assertEquals($expected, $this->compiler->compileString($string));
    }

    public function testActiveStatementsWithComplexConditions()
    {
        $string = '<li @active($current === \'dashboard\')>Dashboard</li>';
        $expected = "<li <?php if(\$current === 'dashboard'): echo 'active'; endif; ?>>Dashboard</li>";

        $this->assertEquals($expected, $this->compiler->compileString($string));
    }
}
