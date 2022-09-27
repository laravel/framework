<?php

namespace Illuminate\Tests\View\Blade;

use Illuminate\Contracts\View\ViewCompilationException;

class BladeVarTest extends AbstractBladeTestCase
{
    public function testVarStatementsAreCompiled()
    {
        $string = '@var($foo = 37)
@var($bar = \'bar\')
@var($foo++)
@var($baz = strtoupper($bar))
@var($foo += 10)
';
        $expected = '<?php $foo = 37 ?>
<?php $bar = \'bar\' ?>
<?php $foo++ ?>
<?php $baz = strtoupper($bar) ?>
<?php $foo += 10 ?>
';
        $this->assertEquals($expected, $this->compiler->compileString($string));
    }

    public function testVarStatementWithNoVariable()
    {
        $this->expectException(ViewCompilationException::class);
        $this->compiler->compileString('@var(echo)');
    }

    public function testVarStatementWithoutEquals()
    {
        $this->expectException(ViewCompilationException::class);
        $this->compiler->compileString('@var($foo)');
    }

    public function testUnfinishedVarStatement()
    {
        $this->expectException(ViewCompilationException::class);
        $this->compiler->compileString('@var($foo = )');
    }
}
