<?php

namespace Illuminate\Tests\View\Blade;

class BladeUnlessStatementsTest extends AbstractBladeTestCase
{
    public function testUnlessStatementsAreCompiled()
    {
        $string = '@unless (name(foo(bar)))
breeze
@endunless';
        $expected = '<?php if (! (name(foo(bar)))): ?>
breeze
<?php endif; ?>';
        $this->assertEquals($expected, $this->compiler->compileString($string));
    }

    public function testElseUnlessStatementsAreCompiled()
    {
        $string = '@unless (name(foo(bar)))
breeze
@elseunless (name(foo(milwad)))
milwad
@endunless';
        $expected = '<?php if (! (name(foo(bar)))): ?>
breeze
<?php elseif (! (name(foo(milwad)))): ?>
milwad
<?php endif; ?>';
        $this->assertEquals($expected, $this->compiler->compileString($string));
    }
}
