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

    public function testJsonDirectiveWithNestedClosures()
    {
        $string = '@json([
            \'items\' => $helpers[\'benefit\'][\'getAll\']()->map(fn($item) => [
                \'icon\' => $item->icon,
                \'title\' => (string)$item->title,
                \'description\' => (string)$item->description
            ]),
            \'translation\' => \'%:booking.benefits%\'
        ])';

        $expected = '<?php echo json_encode([
            \'items\' => $helpers[\'benefit\'][\'getAll\']()->map(fn($item) => [
                \'icon\' => $item->icon,
                \'title\' => (string)$item->title,
                \'description\' => (string)$item->description
            ]),
            \'translation\' => \'%:booking.benefits%\'
        ], 15, 512) ?>';

        $this->assertEquals($expected, $this->compiler->compileString($string));
    }

    public function testJsonDirectiveWithSimpleNestedArray()
    {
        $string = '@json([\'items\' => collect([(object)[\'a\'=>\'b\']])->map(fn($x) => [\'a\' => $x->a])])';

        $expected = '<?php echo json_encode([\'items\' => collect([(object)[\'a\'=>\'b\']])->map(fn($x) => [\'a\' => $x->a])], 15, 512) ?>';

        $this->assertEquals($expected, $this->compiler->compileString($string));
    }

    public function testJsonDirectiveWithCustomOptions()
    {
        $string = '@json([\'items\' => collect([(object)[\'a\'=>\'b\']])->map(fn($x) => [\'a\' => $x->a])], JSON_UNESCAPED_UNICODE)';

        $expected = '<?php echo json_encode([\'items\' => collect([(object)[\'a\'=>\'b\']])->map(fn($x) => [\'a\' => $x->a])], JSON_UNESCAPED_UNICODE, 512) ?>';

        $this->assertEquals($expected, $this->compiler->compileString($string));
    }

    public function testJsonDirectiveWithCustomOptionsAndDepth()
    {
        $string = '@json([\'items\' => collect([(object)[\'a\'=>\'b\']])->map(fn($x) => [\'a\' => $x->a])], JSON_UNESCAPED_UNICODE, 256)';

        $expected = '<?php echo json_encode([\'items\' => collect([(object)[\'a\'=>\'b\']])->map(fn($x) => [\'a\' => $x->a])], JSON_UNESCAPED_UNICODE, 256) ?>';

        $this->assertEquals($expected, $this->compiler->compileString($string));
    }

    public function testJsonDirectiveWithComplexNestedStructures()
    {
        $string = '@json([
            \'data\' => $collection->map(fn($item) => [
                \'id\' => $item->id,
                \'nested\' => [
                    \'value\' => $item->value,
                    \'callback\' => fn($x) => $x * 2
                ]
            ]),
            \'meta\' => [
                \'count\' => $collection->count(),
                \'filter\' => fn($items) => $items->filter(fn($item) => $item->active)
            ]
        ])';

        $expected = '<?php echo json_encode([
            \'data\' => $collection->map(fn($item) => [
                \'id\' => $item->id,
                \'nested\' => [
                    \'value\' => $item->value,
                    \'callback\' => fn($x) => $x * 2
                ]
            ]),
            \'meta\' => [
                \'count\' => $collection->count(),
                \'filter\' => fn($items) => $items->filter(fn($item) => $item->active)
            ]
        ], 15, 512) ?>';

        $this->assertEquals($expected, $this->compiler->compileString($string));
    }

    /**
     * Test case from GitHub issue #56331
     * This reproduces the exact issue described in the bug report.
     */
    public function testJsonDirectiveIssue56331()
    {
        // This is the exact code from the GitHub issue that was failing
        $string = '@json([
            \'items\' => $helpers[\'benefit\'][\'getAll\']()->map(fn($item) => [
                \'icon\' => $item->icon,
                \'title\' => (string)$item->title,
                \'description\' => (string)$item->description
            ]),
            \'translation\' => \'%:booking.benefits%\'
        ])';

        $expected = '<?php echo json_encode([
            \'items\' => $helpers[\'benefit\'][\'getAll\']()->map(fn($item) => [
                \'icon\' => $item->icon,
                \'title\' => (string)$item->title,
                \'description\' => (string)$item->description
            ]),
            \'translation\' => \'%:booking.benefits%\'
        ], 15, 512) ?>';

        $this->assertEquals($expected, $this->compiler->compileString($string));
    }

    /**
     * Test the simplified version that was working before the fix
     */
    public function testJsonDirectiveIssue56331Simplified()
    {
        // This is the simplified version that was working
        $string = '@json([
            \'items\' => $helpers[\'benefit\'][\'getAll\']()->map(fn($item) => [
                \'icon\' => $item->icon,
                \'title\' => (string)$item->title
            ]),
            \'translation\' => \'%:booking.benefits%\'
        ])';

        $expected = '<?php echo json_encode([
            \'items\' => $helpers[\'benefit\'][\'getAll\']()->map(fn($item) => [
                \'icon\' => $item->icon,
                \'title\' => (string)$item->title
            ]),
            \'translation\' => \'%:booking.benefits%\'
        ], 15, 512) ?>';

        $this->assertEquals($expected, $this->compiler->compileString($string));
    }

    /**
     * Test edge cases to ensure our parser is robust
     */
    public function testJsonDirectiveEdgeCases()
    {
        // Test with strings containing commas
        $string = '@json([\'key\' => \'value, with comma\', \'another\' => \'test\'])';
        $expected = '<?php echo json_encode([\'key\' => \'value, with comma\', \'another\' => \'test\'], 15, 512) ?>';
        $this->assertEquals($expected, $this->compiler->compileString($string));

        // Test with escaped quotes
        $string = '@json([\'key\' => \'value with \\\'escaped quotes\\\'\'])';
        $expected = '<?php echo json_encode([\'key\' => \'value with \\\'escaped quotes\\\'\'], 15, 512) ?>';
        $this->assertEquals($expected, $this->compiler->compileString($string));

        // Test with mixed quotes
        $string = '@json([\'key\' => "value with \'mixed\' quotes"])';
        $expected = '<?php echo json_encode([\'key\' => "value with \'mixed\' quotes"], 15, 512) ?>';
        $this->assertEquals($expected, $this->compiler->compileString($string));
    }

    /**
     * Test that our solution doesn't break existing functionality
     */
    public function testJsonDirectiveBackwardCompatibility()
    {
        // Test simple variable
        $string = '@json($var)';
        $expected = '<?php echo json_encode($var, 15, 512) ?>';
        $this->assertEquals($expected, $this->compiler->compileString($string));

        // Test simple array
        $string = '@json([\'a\' => \'b\'])';
        $expected = '<?php echo json_encode([\'a\' => \'b\'], 15, 512) ?>';
        $this->assertEquals($expected, $this->compiler->compileString($string));

        // Test with options only
        $string = '@json($var, JSON_UNESCAPED_UNICODE)';
        $expected = '<?php echo json_encode($var, JSON_UNESCAPED_UNICODE, 512) ?>';
        $this->assertEquals($expected, $this->compiler->compileString($string));
    }
}
