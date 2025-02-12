<?php

namespace Illuminate\Tests\View\Blade;

use Illuminate\Contracts\View\ViewCompilationException;
use PHPUnit\Framework\Attributes\DataProvider;

class BladeFilterTest extends AbstractBladeTestCase
{

    public function test_apply_directive_is_compiled()
    {
        $expected = "<?php echo (request()->has('category') and request()->get('category') == 'laravel') ? 'selected' : ''; ?>";
        $this->assertEquals($expected, $this->compiler->compileString("@applied('category','laravel','selected')"));
    }

    #[DataProvider('invalid_foreach_statements_data_provider')]
    public function test_foreach_statements_throw_humanized_message_when_invalid_statement($statement)
    {
        $this->expectException(ViewCompilationException::class);
        $this->expectExceptionMessage('Malformed @applied statement.');

        $this->compiler->compileString($statement);
    }

    public static function invalid_foreach_statements_data_provider()
    {
        return [
            ["@applied"],
            ["@applied()"],
            ["@applied ()"],
            ["@applied(foo)"],
            ["@applied(foo,value)"],
            ["@applied(foo,value,chcked)"],

            ["@applied('foo',value,chcked)"],
            ["@applied('foo','value',chcked)"],

            ["@applied(foo,'value',chcked)"],
            ["@applied(foo,'value','chcked')"],

            ["@applied(foo,value,'chcked')"],
            ["@applied('foo',value,'chcked')"],

            ["@applied('Foo')"],
            ["@applied('Foo','value')"],
        ];
    }
}
