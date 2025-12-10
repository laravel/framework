<?php

namespace Illuminate\Tests\View\Blade;

class BladeJsonTest extends AbstractBladeTestCase
{
    public function testStatementIsCompiledWithSafeDefaultEncodingOptions()
    {
        $string = 'var foo = @json($var);';
        $expected = 'var foo = <?php echo json_encode($var, 15, 512) ?>;';

        $this->assertEquals($expected, $this->compiler->compileString($string));
    }

    public function testEncodingOptionsCanBeOverwritten()
    {
        $string = 'var foo = @json($var, JSON_HEX_TAG);';
        $expected = 'var foo = <?php echo json_encode($var, JSON_HEX_TAG, 512) ?>;';

        $this->assertEquals($expected, $this->compiler->compileString($string));
    }

    public function testJsonWithComplexArrayContainingNestedStructures()
    {
        $string = '@json([\'items\' => collect([1, 2, 3])->map(fn($x) => [\'id\' => $x, \'name\' => "test"]), \'translation\' => \'%:booking.benefits%\'])';
        $expected = '<?php echo json_encode([\'items\' => collect([1, 2, 3])->map(fn($x) => [\'id\' => $x, \'name\' => "test"]), \'translation\' => \'%:booking.benefits%\'], 15, 512) ?>';

        $this->assertEquals($expected, $this->compiler->compileString($string));
    }

    public function testJsonWithArrayContainingMultipleCommas()
    {
        $string = '@json([\'a\' => 1, \'b\' => 2, \'c\' => 3], JSON_PRETTY_PRINT)';
        $expected = '<?php echo json_encode([\'a\' => 1, \'b\' => 2, \'c\' => 3], JSON_PRETTY_PRINT, 512) ?>';

        $this->assertEquals($expected, $this->compiler->compileString($string));
    }

    public function testJsonWithClosureContainingCommas()
    {
        $string = '@json($items->map(fn($item) => [\'icon\' => $item->icon, \'title\' => (string)$item->title, \'description\' => (string)$item->description]))';
        $expected = '<?php echo json_encode($items->map(fn($item) => [\'icon\' => $item->icon, \'title\' => (string)$item->title, \'description\' => (string)$item->description]), 15, 512) ?>';

        $this->assertEquals($expected, $this->compiler->compileString($string));
    }

    public function testJsonWithAllThreeArguments()
    {
        $string = '@json($data, JSON_PRETTY_PRINT, 256)';
        $expected = '<?php echo json_encode($data, JSON_PRETTY_PRINT, 256) ?>';

        $this->assertEquals($expected, $this->compiler->compileString($string));
    }

    public function testJsonWithEmptyExpressionDefaultsToNull()
    {
        $string = '@json()';
        $expected = '<?php echo json_encode(null, 15, 512) ?>';

        $this->assertEquals($expected, $this->compiler->compileString($string));
    }

    public function testJsonWithIssue56331ExactCase()
    {
        // This is the exact case from GitHub issue #56331
        $string = '@json([\'items\' => $helpers[\'benefit\'][\'getAll\']()->map(fn($item) => [\'icon\' => $item->icon, \'title\' => (string)$item->title, \'description\' => (string)$item->description]), \'translation\' => \'%:booking.benefits%\'])';
        $expected = '<?php echo json_encode([\'items\' => $helpers[\'benefit\'][\'getAll\']()->map(fn($item) => [\'icon\' => $item->icon, \'title\' => (string)$item->title, \'description\' => (string)$item->description]), \'translation\' => \'%:booking.benefits%\'], 15, 512) ?>';

        $this->assertEquals($expected, $this->compiler->compileString($string));
    }
}
