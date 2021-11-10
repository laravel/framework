<?php

namespace Illuminate\Tests\View\Blade;

class BladeAllowsStatementsTest extends AbstractBladeTestCase
{
    public function testAllowsStatementsAreCompiled()
    {
        $string = '@allows ("create", $post)
allowed
@elseallows
forbidden
@endallows';

        $expected = '<?php $access = app(\Illuminate\\Contracts\\Auth\\Access\\Gate::class)->inspect("create", $post); ?>
<?php if ($access->allowed()): ?>
<?php if (isset($message)) { $__messageOriginal = $message; } $message = $access->message(); ?>
allowed
<?php else: ?>
<?php if (isset($message)) { $__messageOriginal = $message; } $message = $access->message(); ?>
forbidden
<?php endif; ?>';

        $this->assertEquals($expected, $this->compiler->compileString($string));
    }
}
