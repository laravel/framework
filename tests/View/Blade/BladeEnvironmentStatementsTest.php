<?php

namespace Illuminate\Tests\View\Blade;

class BladeEnvironmentStatementsTest extends AbstractBladeTestCase
{
    public function testEnvStatementsAreCompiled()
    {
        $string = "@env('staging')
breeze
@else
boom
@endenv";
        $expected = "<?php if(app()->environment('staging')): ?>
breeze
<?php else: ?>
boom
<?php endif; ?>";
        $this->assertEquals($expected, $this->compiler->compileString($string));
    }

    public function testEnvStatementsWithMultipleStringParamsAreCompiled()
    {
        $string = "@env('staging', 'production')
breeze
@else
boom
@endenv";
        $expected = "<?php if(app()->environment('staging', 'production')): ?>
breeze
<?php else: ?>
boom
<?php endif; ?>";
        $this->assertEquals($expected, $this->compiler->compileString($string));
    }

    public function testEnvStatementsWithArrayParamAreCompiled()
    {
        $string = "@env(['staging', 'production'])
breeze
@else
boom
@endenv";
        $expected = "<?php if(app()->environment(['staging', 'production'])): ?>
breeze
<?php else: ?>
boom
<?php endif; ?>";
        $this->assertEquals($expected, $this->compiler->compileString($string));
    }

    public function testProductionStatementsAreCompiled()
    {
        $string = '@production
breeze
@else
boom
@endproduction';
        $expected = "<?php if(app()->environment('production')): ?>
breeze
<?php else: ?>
boom
<?php endif; ?>";
        $this->assertEquals($expected, $this->compiler->compileString($string));
    }
}
