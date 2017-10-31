<?php

namespace Illuminate\Tests\View\Blade;

class BladeVerbatimTest extends AbstractBladeTestCase
{
    public function testVerbatimBlocksAreCompiled()
    {
        $string = '@verbatim {{ $a }} @if($b) {{ $b }} @endif @endverbatim';
        $expected = ' {{ $a }} @if($b) {{ $b }} @endif ';
        $this->assertEquals($expected, $this->compiler->compileString($string));
    }

    public function testVerbatimBlocksWithMultipleLinesAreCompiled()
    {
        $string = 'Some text
@verbatim
    {{ $a }}
    @if($b)
        {{ $b }}
    @endif
@endverbatim';
        $expected = 'Some text

    {{ $a }}
    @if($b)
        {{ $b }}
    @endif
';
        $this->assertEquals($expected, $this->compiler->compileString($string));
    }

    public function testMultipleVerbatimBlocksAreCompiled()
    {
        $string = '@verbatim {{ $a }} @endverbatim {{ $b }} @verbatim {{ $c }} @endverbatim';
        $expected = ' {{ $a }}  <?php echo e($b); ?>  {{ $c }} ';
        $this->assertEquals($expected, $this->compiler->compileString($string));
    }

    public function testRawBlocksAreRenderedInTheRightOrder()
    {
        $string = '@php echo "#1"; @endphp @verbatim {{ #2 }} @endverbatim @verbatim {{ #3 }} @endverbatim @php echo "#4"; @endphp';

        $expected = '<?php echo "#1"; ?>  {{ #2 }}   {{ #3 }}  <?php echo "#4"; ?>';

        $this->assertSame($expected, $this->compiler->compileString($string));
    }

    public function testRawBlocksDontGetMixedUpWhenSomeAreRemovedByBladeComments()
    {
        $string = '{{-- @verbatim Block #1 @endverbatim --}} @php "Block #2" @endphp';
        $expected = ' <?php "Block #2" ?>';

        $this->assertSame($expected, $this->compiler->compileString($string));
    }
}
