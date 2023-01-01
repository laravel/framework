<?php

namespace Illuminate\Tests\View\Blade;

use Illuminate\Contracts\View\ViewCompilationException;

class BladeForelseStatementsTest extends AbstractBladeTestCase
{
    public function testForelseStatementsAreCompiled()
    {
        $string = '@forelse ($this->getUsers() as $user)
breeze
@empty
empty
@endforelse';
        $expected = '<?php $__empty_1 = true; $__currentLoopData = $this->getUsers(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $user): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
breeze
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
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
        $expected = '<?php $__empty_1 = true; $__currentLoopData = $this->getUsers(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $user): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
breeze
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
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
        $expected = '<?php $__empty_1 = true; $__currentLoopData = [
foo,
bar,
]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
breeze
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
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
        $expected = '<?php $__empty_1 = true; $__currentLoopData = $this->getUsers(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $user): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
<?php $__empty_2 = true; $__currentLoopData = $user->tags; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $tag): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_2 = false; ?>
breeze
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_2): ?>
tag empty
<?php endif; ?>
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
empty
<?php endif; ?>';
        $this->assertEquals($expected, $this->compiler->compileString($string));
    }

    /**
     * @dataProvider invalidForelseStatementsDataProvider
     */
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
