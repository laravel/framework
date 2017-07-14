<?php

namespace Illuminate\Tests\View\Blade;

class BladeCommentsTest extends AbstractBladeTestCase
{
    public function testCommentsAreCompiled()
    {
        $string = '{{--this is a comment--}}';
        $this->assertEmpty($this->compiler->compileString($string));

        $string = '{{--
this is a comment
--}}';
        $this->assertEmpty($this->compiler->compileString($string));

        $string = sprintf('{{-- this is an %s long comment --}}', str_repeat('extremely ', 1000));
        $this->assertEmpty($this->compiler->compileString($string));
    }

    public function testBladeCodeInsideCommentsIsNotCompiled()
    {
        $string = '{{-- @foreach() --}}';

        $this->assertEmpty($this->compiler->compileString($string));
    }

    public function testRawBlocksDontGetMixedUpWhenSomeAreRemovedByBladeComments()
    {
        $string = '{{-- @verbatim Block #1 @endverbatim --}} @php "Block #2" @endphp';
        $expected = ' <?php "Block #2" ?>';

        $this->assertSame($expected, $this->compiler->compileString($string));
    }
}
