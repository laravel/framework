<?php

namespace Illuminate\Tests\View\Blade;

class BladeVarTest extends AbstractBladeTestCase
{
    public function testUseStatementsAreCompiled()
    {
        $string = 'Foo @var(App\Livewire\SomeComponent $this) bar';
        $expected = 'Foo <?php /** @var \App\Livewire\SomeComponent $this */ ?> bar';
        $this->assertEquals($expected, $this->compiler->compileString($string));
    }

    public function testVarStatementsWithBackslashAtBeginningAreCompiled()
    {
        $string = 'Foo @var(\App\Livewire\SomeComponent $this) bar';
        $expected = 'Foo <?php /** @var \App\Livewire\SomeComponent $this */ ?> bar';
        $this->assertEquals($expected, $this->compiler->compileString($string));
    }
}
