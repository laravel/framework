<?php

namespace Illuminate\Tests\View\Blade;

class BladeSnippetStatementsTest extends AbstractBladeTestCase
{
    public function testSnippetStatementsAreCompiled()
    {
        $string = '@snippet
default breeze
@endsnippet

@snippet ("bar")
breeze with double quotes
@endsnippet

@snippet (\'foo\', $bar)
breeze with single quotes {{ $bar }}
@endsnippet

@snippet (foobar, string $barfoo)
breeze without quotes {{ $barfoo }}
@endsnippet

@snippet ("foo-bar", ?string $barfoo = null)
breeze with slugged snippet name {{ $barfoo }}
@endsnippet';
        $expected = '<?php if (! isset($__snippet_function)):
$__snippet_function = static function() use($__env) {
?>
default breeze
<?php } ?>
<?php endif; ?>

<?php if (! isset($__snippet_bar)):
$__snippet_bar = static function() use($__env) {
?>
breeze with double quotes
<?php } ?>
<?php endif; ?>

<?php if (! isset($__snippet_foo)):
$__snippet_foo = static function($bar) use($__env) {
?>
breeze with single quotes <?php echo e($bar); ?>

<?php } ?>
<?php endif; ?>

<?php if (! isset($__snippet_foobar)):
$__snippet_foobar = static function(string $barfoo) use($__env) {
?>
breeze without quotes <?php echo e($barfoo); ?>

<?php } ?>
<?php endif; ?>

<?php if (! isset($__snippet_fooBar)):
$__snippet_fooBar = static function(?string $barfoo = null) use($__env) {
?>
breeze with slugged snippet name <?php echo e($barfoo); ?>

<?php } ?>
<?php endif; ?>';
        $this->assertEquals($expected, $this->compiler->compileString($string));
    }

    public function testRenderSnippetStatementsAreCompiled()
    {
        $string = '@renderSnippet

@renderSnippet ("bar")

@renderSnippet (\'foo\', $bar)

@renderSnippet (foobar, $barfoo)

@renderSnippet ("foo-bar", $barfoo)';
        $expected = '<?php echo $__snippet_function(); ?>

<?php echo $__snippet_bar(); ?>

<?php echo $__snippet_foo($bar); ?>

<?php echo $__snippet_foobar($barfoo); ?>

<?php echo $__snippet_fooBar($barfoo); ?>';
        $this->assertEquals($expected, $this->compiler->compileString($string));
    }
}
