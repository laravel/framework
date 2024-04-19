<?php

namespace Illuminate\Tests\View\Blade;

class BladeVerifiedStatementsTest extends AbstractBladeTestCase
{
    public function testBladeVerifiedAreCompiled()
    {
        $string = '@verified
        Foo bar baz
@endverified';

        $expected = '<?php if(auth()->guard()->user()->hasVerifiedEmail()): ?>
        Foo bar baz
<?php endif; ?>';

        $this->assertEquals($expected, $this->compiler->compileString($string));
    }
}
