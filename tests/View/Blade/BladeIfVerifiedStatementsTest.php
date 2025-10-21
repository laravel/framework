<?php

namespace Illuminate\Tests\View\Blade;

class BladeIfVerifiedStatementsTest extends AbstractBladeTestCase
{
    public function testIfStatementsAreCompiled()
    {
        $string = '@verified("api")
verified
@endverified';
        $expected = '<?php if(auth()->guard("api")->check() && auth()->guard("api")->user() instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && auth()->guard("api")->user()->hasVerifiedEmail()): ?>
verified
<?php endif; ?>';
        $this->assertEquals($expected, $this->compiler->compileString($string));
    }

    public function testPlainIfStatementsAreCompiled()
    {
        $string = '@verified
verified
@endverified';
        $expected = '<?php if(auth()->guard()->check() && auth()->guard()->user() instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && auth()->guard()->user()->hasVerifiedEmail()): ?>
verified
<?php endif; ?>';
        $this->assertEquals($expected, $this->compiler->compileString($string));
    }
}
