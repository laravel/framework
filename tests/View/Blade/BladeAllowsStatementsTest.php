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

        $expected = '<?php if (isset($access)) { $__accessOriginal = $access; }
$access = app(\Illuminate\\Contracts\\Auth\\Access\\Gate::class)->inspect("create", $post); ?>
<?php if ($access->allowed()): ?>
<?php if (isset($message)) { $__messageOriginal = $message; } $message = $access->message(); ?>
allowed
<?php else: ?>
<?php if (isset($message)) { $__messageOriginal = $message; } $message = $access->message(); ?>
forbidden
<?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
unset($access);
if (isset($__accessOriginal)) { $access = $__accessOriginal; }
endif; ?>';

        $this->assertEquals($expected, $this->compiler->compileString($string));
    }
}
