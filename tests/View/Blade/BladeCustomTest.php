<?php

namespace Illuminate\Tests\View\Blade;

use InvalidArgumentException;

class BladeCustomTest extends AbstractBladeTestCase
{
    public function testCustomPhpCodeIsCorrectlyHandled()
    {
        $this->assertSame('<?php if($test): ?> <?php @show(\'test\'); ?> <?php endif; ?>', $this->compiler->compileString("@if(\$test) <?php @show('test'); ?> @endif"));
    }

    public function testMixingYieldAndEcho()
    {
        $this->assertSame('<?php echo $__env->yieldContent(\'title\'); ?> - <?php echo e(Config::get(\'site.title\')); ?>', $this->compiler->compileString("@yield('title') - {{Config::get('site.title')}}"));
    }

    public function testCustomExtensionsAreCompiled()
    {
        $this->compiler->extend(function ($value) {
            return str_replace('foo', 'bar', $value);
        });
        $this->assertSame('bar', $this->compiler->compileString('foo'));
    }

    public function testCustomStatements()
    {
        $this->assertCount(0, $this->compiler->getCustomDirectives());
        $this->compiler->directive('customControl', function ($expression) {
            return "<?php echo custom_control({$expression}); ?>";
        });
        $this->assertCount(1, $this->compiler->getCustomDirectives());

        $string = '@if($foo)
@customControl(10, $foo, \'bar\')
@endif';
        $expected = '<?php if($foo): ?>
<?php echo custom_control(10, $foo, \'bar\'); ?>
<?php endif; ?>';
        $this->assertEquals($expected, $this->compiler->compileString($string));
    }

    public function testCustomShortStatements()
    {
        $this->compiler->directive('customControl', function ($expression) {
            return '<?php echo custom_control(); ?>';
        });

        $string = '@customControl';
        $expected = '<?php echo custom_control(); ?>';
        $this->assertEquals($expected, $this->compiler->compileString($string));
    }

    public function testValidCustomNames()
    {
        $this->assertNull($this->compiler->directive('custom', function () {
            //
        }));
        $this->assertNull($this->compiler->directive('custom_custom', function () {
            //
        }));
        $this->assertNull($this->compiler->directive('customCustom', function () {
            //
        }));
        $this->assertNull($this->compiler->directive('custom::custom', function () {
            //
        }));
    }

    public function testInvalidCustomNames()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The directive name [custom-custom] is not valid.');
        $this->compiler->directive('custom-custom', function () {
            //
        });
    }

    public function testInvalidCustomNames2()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The directive name [custom:custom] is not valid.');
        $this->compiler->directive('custom:custom', function () {
            //
        });
    }

    public function testCustomExtensionOverwritesCore()
    {
        $this->compiler->directive('foreach', function ($expression) {
            return '<?php custom(); ?>';
        });

        $string = '@foreach';
        $expected = '<?php custom(); ?>';
        $this->assertEquals($expected, $this->compiler->compileString($string));
    }

    public function testCustomConditions()
    {
        $this->compiler->if('custom', function ($user) {
            return true;
        });

        $string = '@custom($user)
@endcustom';
        $expected = '<?php if (\Illuminate\Support\Facades\Blade::check(\'custom\', $user)): ?>
<?php endif; ?>';
        $this->assertEquals($expected, $this->compiler->compileString($string));
    }

    public function testCustomIfElseConditions()
    {
        $this->compiler->if('custom', function ($anything) {
            return true;
        });

        $string = '@custom($user)
@elsecustom($product)
@else
@endcustom';
        $expected = '<?php if (\Illuminate\Support\Facades\Blade::check(\'custom\', $user)): ?>
<?php elseif (\Illuminate\Support\Facades\Blade::check(\'custom\', $product)): ?>
<?php else: ?>
<?php endif; ?>';
        $this->assertEquals($expected, $this->compiler->compileString($string));
    }

    public function testCustomUnlessConditions()
    {
        $this->compiler->if('custom', function ($anything) {
            return true;
        });

        $string = '@unlesscustom($user)
@endcustom';
        $expected = '<?php if (! \Illuminate\Support\Facades\Blade::check(\'custom\', $user)): ?>
<?php endif; ?>';
        $this->assertEquals($expected, $this->compiler->compileString($string));
    }

    public function testCustomConditionsAccepts0AsArgument()
    {
        $this->compiler->if('custom', function ($number) {
            return true;
        });

        $string = '@custom(0)
@elsecustom(0)
@endcustom';
        $expected = '<?php if (\Illuminate\Support\Facades\Blade::check(\'custom\', 0)): ?>
<?php elseif (\Illuminate\Support\Facades\Blade::check(\'custom\', 0)): ?>
<?php endif; ?>';
        $this->assertEquals($expected, $this->compiler->compileString($string));
    }

    public function testCustomComponents()
    {
        $this->compiler->aliasComponent('app.components.alert', 'alert');

        $string = '@alert
@endalert';
        $expected = '<?php $__env->startComponent(\'app.components.alert\'); ?>
<?php echo $__env->renderComponent(); ?>';
        $this->assertEquals($expected, $this->compiler->compileString($string));
    }

    public function testCustomComponentsWithSlots()
    {
        $this->compiler->aliasComponent('app.components.alert', 'alert');

        $string = '@alert([\'type\' => \'danger\'])
@endalert';
        $expected = '<?php $__env->startComponent(\'app.components.alert\', [\'type\' => \'danger\']); ?>
<?php echo $__env->renderComponent(); ?>';
        $this->assertEquals($expected, $this->compiler->compileString($string));
    }

    public function testCustomComponentsWithExistingDirective()
    {
        $this->compiler->aliasComponent('app.components.foreach', 'foreach');

        $string = '@foreach
@endforeach';
        $expected = '<?php $__env->startComponent(\'app.components.foreach\'); ?>
<?php echo $__env->renderComponent(); ?>';
        $this->assertEquals($expected, $this->compiler->compileString($string));
    }

    public function testCustomIncludes()
    {
        $this->compiler->include('app.includes.input', 'input');

        $string = '@input';
        $expected = '<?php echo $__env->make(\'app.includes.input\', [], array_diff_key(get_defined_vars(), [\'__data\' => 1, \'__path\' => 1]))->render(); ?>';
        $this->assertEquals($expected, $this->compiler->compileString($string));
    }

    public function testCustomIncludesWithData()
    {
        $this->compiler->include('app.includes.input', 'input');

        $string = '@input([\'type\' => \'email\'])';
        $expected = '<?php echo $__env->make(\'app.includes.input\', [\'type\' => \'email\'], array_diff_key(get_defined_vars(), [\'__data\' => 1, \'__path\' => 1]))->render(); ?>';
        $this->assertEquals($expected, $this->compiler->compileString($string));
    }

    public function testCustomIncludesDefaultAlias()
    {
        $this->compiler->include('app.includes.input');

        $string = '@input';
        $expected = '<?php echo $__env->make(\'app.includes.input\', [], array_diff_key(get_defined_vars(), [\'__data\' => 1, \'__path\' => 1]))->render(); ?>';
        $this->assertEquals($expected, $this->compiler->compileString($string));
    }

    public function testCustomIncludesWithExistingDirective()
    {
        $this->compiler->include('app.includes.foreach');

        $string = '@foreach';
        $expected = '<?php echo $__env->make(\'app.includes.foreach\', [], array_diff_key(get_defined_vars(), [\'__data\' => 1, \'__path\' => 1]))->render(); ?>';
        $this->assertEquals($expected, $this->compiler->compileString($string));
    }

    public function testUnescapedNonRegisteredDirective()
    {
        $string = '@media only screen and (min-width:480px) {';
        $expected = '@media only screen and (min-width:480px) {';
        $this->assertEquals($expected, $this->compiler->compileString($string));
    }
}
