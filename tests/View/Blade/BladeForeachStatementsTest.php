<?php

namespace Illuminate\Tests\View\Blade;

use Illuminate\Contracts\View\ViewCompilationException;
use PHPUnit\Framework\Attributes\DataProvider;

class BladeForeachStatementsTest extends AbstractBladeTestCase
{
    public function testForeachStatementsAreCompiled()
    {
        $string = '@foreach ($this->getUsers() as $user)
test
@endforeach';
        $expected = '<?php foreach($__env->addLoop($this->getUsers()) as $user): $loop = $__env->getLastLoop(); ?>
test
<?php $__env->incrementLoopIndices(); endforeach; $loop = $__env->popLoop(); ?>';
        $this->assertEquals($expected, $this->compiler->compileString($string));
    }

    public function testForeachStatementsAreCompileWithUppercaseSyntax()
    {
        $string = '@foreach ($this->getUsers() AS $user)
test
@endforeach';
        $expected = '<?php foreach($__env->addLoop($this->getUsers()) as $user): $loop = $__env->getLastLoop(); ?>
test
<?php $__env->incrementLoopIndices(); endforeach; $loop = $__env->popLoop(); ?>';
        $this->assertEquals($expected, $this->compiler->compileString($string));
    }

    public function testForeachStatementsAreCompileWithMultipleLine()
    {
        $string = '@foreach ([
foo,
bar,
] as $label)
test
@endforeach';
        $expected = '<?php foreach($__env->addLoop([
foo,
bar,
]) as $label): $loop = $__env->getLastLoop(); ?>
test
<?php $__env->incrementLoopIndices(); endforeach; $loop = $__env->popLoop(); ?>';
        $this->assertEquals($expected, $this->compiler->compileString($string));
    }

    public function testNestedForeachStatementsAreCompiled()
    {
        $string = '@foreach ($this->getUsers() as $user)
user info
@foreach ($user->tags as $tag)
tag info
@endforeach
@endforeach';
        $expected = '<?php foreach($__env->addLoop($this->getUsers()) as $user): $loop = $__env->getLastLoop(); ?>
user info
<?php foreach($__env->addLoop($user->tags) as $tag): $loop = $__env->getLastLoop(); ?>
tag info
<?php $__env->incrementLoopIndices(); endforeach; $loop = $__env->popLoop(); ?>
<?php $__env->incrementLoopIndices(); endforeach; $loop = $__env->popLoop(); ?>';
        $this->assertEquals($expected, $this->compiler->compileString($string));
    }

    public function testLoopContentHolderIsExtractedFromForeachStatements()
    {
        $string = '@foreach ($some_uSers1 as $user)';
        $expected = '<?php foreach($__env->addLoop($some_uSers1) as $user): $loop = $__env->getLastLoop(); ?>';
        $this->assertEquals($expected, $this->compiler->compileString($string));

        $string = '@foreach ($users->get() as $user)';
        $expected = '<?php foreach($__env->addLoop($users->get()) as $user): $loop = $__env->getLastLoop(); ?>';
        $this->assertEquals($expected, $this->compiler->compileString($string));

        $string = '@foreach (range(1, 4) as $user)';
        $expected = '<?php foreach($__env->addLoop(range(1, 4)) as $user): $loop = $__env->getLastLoop(); ?>';
        $this->assertEquals($expected, $this->compiler->compileString($string));

        $string = '@foreach (   $users as $user)';
        $expected = '<?php foreach($__env->addLoop($users) as $user): $loop = $__env->getLastLoop(); ?>';
        $this->assertEquals($expected, $this->compiler->compileString($string));

        $string = '@foreach ($tasks as $task)';
        $expected = '<?php foreach($__env->addLoop($tasks) as $task): $loop = $__env->getLastLoop(); ?>';
        $this->assertEquals($expected, $this->compiler->compileString($string));

        $string = "@foreach(resolve('App\\\\DataProviders\\\\'.\$provider)->data() as \$key => \$value)
    <input {{ \$foo ? 'bar': 'baz' }}>
@endforeach";

        $expected = "<?php foreach(\$__env->addLoop(resolve('App\\\\DataProviders\\\\'.\$provider)->data()) as \$key => \$value): \$loop = \$__env->getLastLoop(); ?>
    <input <?php echo e(\$foo ? 'bar': 'baz'); ?>>
<?php \$__env->incrementLoopIndices(); endforeach; \$loop = \$__env->popLoop(); ?>";

        $this->assertEquals($expected, $this->compiler->compileString($string));
    }

    #[DataProvider('invalidForeachStatementsDataProvider')]
    public function testForeachStatementsThrowHumanizedMessageWhenInvalidStatement($initialStatement)
    {
        $this->expectException(ViewCompilationException::class);
        $this->expectExceptionMessage('Malformed @foreach statement.');
        $string = "$initialStatement
test
@endforeach";
        $this->compiler->compileString($string);
    }

    public static function invalidForeachStatementsDataProvider()
    {
        return [
            ['@foreach'],
            ['@foreach()'],
            ['@foreach ()'],
            ['@foreach($test)'],
            ['@foreach($test as)'],
            ['@foreach(as)'],
            ['@foreach ( as )'],
        ];
    }
}
