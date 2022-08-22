<?php

namespace Illuminate\Tests\View\Blade;

class BladeIfLocaleStatementsTest extends AbstractBladeTestCase
{
    public function testIfLocaleStatementsAreCompiled()
    {
        $string = '@locale (name(foo(bar)))
bar
@elselocale (name(foo(baz)))
baz
@endlocale';

        $expected = '<?php if(app()->getLocale() === (name(foo(bar)))): ?>
bar
<?php elseif(app()->getLocale() === (name(foo(baz)))): ?>
baz
<?php endif; ?>';
        $this->assertEquals($expected, $this->compiler->compileString($string));
    }
}
