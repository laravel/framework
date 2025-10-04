<?php

namespace Illuminate\Tests\View\Blade;

use Illuminate\Contracts\View\ViewCompilationException;

class BladeMaybeStatementsTest extends AbstractBladeTestCase
{
    public function testMaybeWithMissingParameter()
    {
        $this->expectException(ViewCompilationException::class);
        $this->expectExceptionMessage('The @maybe directive requires exactly 2 parameters.');

        $string = "@maybe('title')";
        $this->compiler->compileString($string);
    }

    public function testMaybeWithSimpleVariable()
    {
        $string = '<a @maybe(\'title\', $title)>Link</a>';
        $expected = '<a <?php if($title !== \'\' && $title !== null && trim(is_bool($title) ? ($title ? \'true\' : \'false\') : $title) !== \'\') echo \'title\' . \'="\' . e(is_bool($title) ? ($title ? \'true\' : \'false\') : $title) . \'"\'; ?>>Link</a>';

        $this->assertEquals($expected, $this->compiler->compileString($string));
    }

    public function testMaybeWithObjectProperty()
    {
        $string = '<a @maybe(\'title\', $link->title)>Link</a>';
        $expected = '<a <?php if($link->title !== \'\' && $link->title !== null && trim(is_bool($link->title) ? ($link->title ? \'true\' : \'false\') : $link->title) !== \'\') echo \'title\' . \'="\' . e(is_bool($link->title) ? ($link->title ? \'true\' : \'false\') : $link->title) . \'"\'; ?>>Link</a>';

        $this->assertEquals($expected, $this->compiler->compileString($string));
    }

    public function testMaybeWithArrayAccess()
    {
        $string = '<a @maybe(\'title\', $data[\'title\'])>Link</a>';
        $expected = '<a <?php if($data[\'title\'] !== \'\' && $data[\'title\'] !== null && trim(is_bool($data[\'title\']) ? ($data[\'title\'] ? \'true\' : \'false\') : $data[\'title\']) !== \'\') echo \'title\' . \'="\' . e(is_bool($data[\'title\']) ? ($data[\'title\'] ? \'true\' : \'false\') : $data[\'title\']) . \'"\'; ?>>Link</a>';

        $this->assertEquals($expected, $this->compiler->compileString($string));
    }

    public function testMaybeWithMultipleAttributes()
    {
        $string = '<img @maybe(\'alt\', $alt) @maybe(\'data-src\', $src)/>';
        $expected = '<img <?php if($alt !== \'\' && $alt !== null && trim(is_bool($alt) ? ($alt ? \'true\' : \'false\') : $alt) !== \'\') echo \'alt\' . \'="\' . e(is_bool($alt) ? ($alt ? \'true\' : \'false\') : $alt) . \'"\'; ?> <?php if($src !== \'\' && $src !== null && trim(is_bool($src) ? ($src ? \'true\' : \'false\') : $src) !== \'\') echo \'data-src\' . \'="\' . e(is_bool($src) ? ($src ? \'true\' : \'false\') : $src) . \'"\'; ?>/>';

        $this->assertEquals($expected, $this->compiler->compileString($string));
    }

    public function testMaybeWithSpacesAroundParameters()
    {
        $string = '<a @maybe( \'title\' ,   $title )>Link</a>';
        $expected = '<a <?php if($title !== \'\' && $title !== null && trim(is_bool($title) ? ($title ? \'true\' : \'false\') : $title) !== \'\') echo \'title\' . \'="\' . e(is_bool($title) ? ($title ? \'true\' : \'false\') : $title) . \'"\'; ?>>Link</a>';

        $this->assertEquals($expected, $this->compiler->compileString($string));
    }

    public function testMaybeWithNonEmptyString()
    {
        $this->assertSame(
            "<?php if(\$title !== '' && \$title !== null && trim(is_bool(\$title) ? (\$title ? 'true' : 'false') : \$title) !== '') echo 'title' . '=\"' . e(is_bool(\$title) ? (\$title ? 'true' : 'false') : \$title) . '\"'; ?>",
            $this->compiler->compileString("@maybe('title', \$title)")
        );
    }

    public function testMaybeWithEmptyString()
    {
        $string = "@maybe('title', \$title)";
        $compiled = $this->compiler->compileString($string);

        $this->assertSame('', $this->evaluateBlade($compiled, ['title' => '']));
    }

    public function testMaybeWithNull()
    {
        $string = "@maybe('title', \$title)";
        $compiled = $this->compiler->compileString($string);

        $this->assertSame('', $this->evaluateBlade($compiled, ['title' => null]));
    }

    public function testMaybeWithZero()
    {
        $string = "@maybe('data-count', \$count)";
        $compiled = $this->compiler->compileString($string);

        $this->assertSame('data-count="0"', $this->evaluateBlade($compiled, ['count' => 0]));
    }

    public function testMaybeWithFalse()
    {
        $string = "@maybe('data-active', \$active)";
        $compiled = $this->compiler->compileString($string);

        $this->assertSame('data-active="false"', $this->evaluateBlade($compiled, ['active' => false]));
    }

    public function testMaybeWithTrue()
    {
        $string = "@maybe('data-active', \$active)";
        $compiled = $this->compiler->compileString($string);

        $this->assertSame('data-active="true"', $this->evaluateBlade($compiled, ['active' => true]));
    }

    public function testMaybeWithValidString()
    {
        $string = "@maybe('title', \$title)";
        $compiled = $this->compiler->compileString($string);

        $this->assertSame('title="You can just do things"', $this->evaluateBlade($compiled, ['title' => 'You can just do things']));
    }

    public function testMaybeEscapesHtmlEntities()
    {
        $string = "@maybe('title', \$title)";
        $compiled = $this->compiler->compileString($string);

        $this->assertSame('title="&lt;script&gt;alert(&#039;xss&#039;)&lt;/script&gt;"',
            $this->evaluateBlade($compiled, ['title' => "<script>alert('xss')</script>"]));
    }

    public function testMaybeWithWhitespaceOnlyString()
    {
        $string = "@maybe('title', \$title)";
        $compiled = $this->compiler->compileString($string);

        // Whitespace-only strings are considered empty.
        $this->assertSame('', $this->evaluateBlade($compiled, ['title' => '   ']));
    }

    public function testMaybeWithNumericString()
    {
        $string = "@maybe('data-id', \$id)";
        $compiled = $this->compiler->compileString($string);

        $this->assertSame('data-id="123"', $this->evaluateBlade($compiled, ['id' => '123']));
    }

    public function testMaybeWithInt()
    {
        $string = "@maybe('data-id', \$id)";
        $compiled = $this->compiler->compileString($string);

        $this->assertSame('data-id="123"', $this->evaluateBlade($compiled, ['id' => 123]));
    }

    public function testMaybeInHtmlContext()
    {
        $string = '<a href="#" @maybe(\'title\', $title)>Link</a>';
        $compiled = $this->compiler->compileString($string);

        $this->assertSame('<a href="#" title="click">Link</a>',
            $this->evaluateBlade($compiled, ['title' => 'click']));

        $this->assertSame('<a href="#" >Link</a>',
            $this->evaluateBlade($compiled, ['title' => '']));
    }

    protected function evaluateBlade(string $compiled, array $data = []): string
    {
        extract($data);
        ob_start();
        eval('?>'.$compiled);

        return ob_get_clean();
    }
}
