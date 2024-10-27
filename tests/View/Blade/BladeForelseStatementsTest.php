<?php

namespace Illuminate\Tests\View\Blade;

use Illuminate\Contracts\View\ViewCompilationException;
use PHPUnit\Framework\Attributes\DataProvider;

class BladeForelseStatementsTest extends AbstractBladeTestCase
{
    public function testForelseStatementsAreCompiled()
    {
        $string = '@forelse ($this->getUsers() as $user)
breeze
@empty
empty
@endforelse';
        $expected = '<?php $__empty_1 = true; foreach($__env->addLoop($this->getUsers()) as $user): $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
breeze
<?php $__env->incrementLoopIndices(); endforeach; $loop = $__env->popLoop(); if ($__empty_1): ?>
empty
<?php endif; ?>';
        $this->assertEquals($expected, $this->compiler->compileString($string));
    }

    public function testForelseStatementsAreCompiledWithUppercaseSyntax()
    {
        $string = '@forelse ($this->getUsers() AS $user)
breeze
@empty
empty
@endforelse';
        $expected = '<?php $__empty_1 = true; foreach($__env->addLoop($this->getUsers()) as $user): $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
breeze
<?php $__env->incrementLoopIndices(); endforeach; $loop = $__env->popLoop(); if ($__empty_1): ?>
empty
<?php endif; ?>';
        $this->assertEquals($expected, $this->compiler->compileString($string));
    }

    public function testForelseStatementsAreCompiledWithMultipleLine()
    {
        $string = '@forelse ([
foo,
bar,
] as $label)
breeze
@empty
empty
@endforelse';
        $expected = '<?php $__empty_1 = true; foreach($__env->addLoop([
foo,
bar,
]) as $label): $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
breeze
<?php $__env->incrementLoopIndices(); endforeach; $loop = $__env->popLoop(); if ($__empty_1): ?>
empty
<?php endif; ?>';
        $this->assertEquals($expected, $this->compiler->compileString($string));
    }

    public function testNestedForelseStatementsAreCompiled()
    {
        $string = '@forelse ($this->getUsers() as $user)
@forelse ($user->tags as $tag)
breeze
@empty
tag empty
@endforelse
@empty
empty
@endforelse';
        $expected = '<?php $__empty_1 = true; foreach($__env->addLoop($this->getUsers()) as $user): $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
<?php $__empty_2 = true; foreach($__env->addLoop($user->tags) as $tag): $loop = $__env->getLastLoop(); $__empty_2 = false; ?>
breeze
<?php $__env->incrementLoopIndices(); endforeach; $loop = $__env->popLoop(); if ($__empty_2): ?>
tag empty
<?php endif; ?>
<?php $__env->incrementLoopIndices(); endforeach; $loop = $__env->popLoop(); if ($__empty_1): ?>
empty
<?php endif; ?>';
        $this->assertEquals($expected, $this->compiler->compileString($string));
    }

    #[DataProvider('invalidForelseStatementsDataProvider')]
    public function testForelseStatementsThrowHumanizedMessageWhenInvalidStatement($initialStatement)
    {
        $this->expectException(ViewCompilationException::class);
        $this->expectExceptionMessage('Malformed @forelse statement.');
        $string = "$initialStatement
breeze
@empty
tag empty
@endforelse";
        $this->compiler->compileString($string);
    }

    public static function invalidForelseStatementsDataProvider()
    {
        return [
            ['@forelse'],
            ['@forelse()'],
            ['@forelse ()'],
            ['@forelse($test)'],
            ['@forelse($test as)'],
            ['@forelse(as)'],
            ['@forelse ( as )'],
        ];
    }
}
