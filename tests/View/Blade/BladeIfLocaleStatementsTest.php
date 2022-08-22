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

    public function testIfLocaleStatementsWithArrayPassedAreCompiled()
    {
        $string = '@locale ([foo, bar])
foo or bar
@elselocale ([baz,qux])
baz or qux
@endlocale';

        $expected = '<?php if(in_array(app()->getLocale(), ([foo, bar]))): ?>
foo or bar
<?php elseif(in_array(app()->getLocale(), ([baz,qux]))): ?>
baz or qux
<?php endif; ?>';
        $this->assertEquals($expected, $this->compiler->compileString($string));
    }
}
