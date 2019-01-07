<?php

namespace Illuminate\Tests\Container;

use PHPUnit\Framework\TestCase;
use Illuminate\Container\Container;

class DecoratorTest extends TestCase
{
    public function testSimpleDecorator()
    {
        $container = new Container;

        $stringifyDecorator = function ($container, $decorated) {
            return function (...$params) use ($container, $decorated) {
                return (string) $container->call($decorated, $params);
            };
        };

        $container->defineDecorator('stringifyResult', $stringifyDecorator);

        $container->decorateWith(Calculator::class.'@add', 'stringifyResult');

        $result = $container->callWithDecorators(Calculator::class.'@add', [-10, -10]);
        $this->assertIsString($result);
        $this->assertEquals('-20', $result);

        $result = $container->callWithDecorators([Calculator::class, 'add'], [-10, -10]);
        $this->assertIsString($result);
        $this->assertEquals('-20', $result);

        $result = $container->callWithDecorators([new Calculator(), 'add'], [-10, -10]);
        $this->assertIsString($result);
        $this->assertEquals('-20', $result);
    }

    public function testSimpleDecoratorOnInterface()
    {
        $container = new Container;

        $stringifyDecorator = function ($container, $decorated) {
            return function (...$params) use ($container, $decorated) {
                return (string) $container->call($decorated, $params);
            };
        };

        $container->defineDecorator('stringifyResult', $stringifyDecorator);

        $container->decorateWith(ICalculator::class.'@add', 'stringifyResult');
        $container->bind(ICalculator::class, Calculator::class);

        $result = $container->callWithDecorators(ICalculator::class.'@add', [10, 10]);
        $this->assertIsString($result);
        $this->assertEquals('20', $result);
    }

    public function testTwoDecorators()
    {
        $container = new Container;

        $stringifyDecorator = function ($app, $decorated) {
            return function (...$params) use ($app, $decorated) {
                return (string) $app->call($decorated, $params);
            };
        };

        $intifyParamsDecorator = function ($container, $decorated) {
            return function ($x, $y) use ($container, $decorated) {
                return $container->call($decorated, [(int) $x, (int) $y]);
            };
        };

        $container->defineDecorator('stringifyResult', $stringifyDecorator);
        $container->defineDecorator('intifyParams', $intifyParamsDecorator);

        $container->decorateWith(Calculator::class.'@add', 'intifyParams');
        $container->decorateWith(Calculator::class.'@add', 'stringifyResult');

        $result = $container->callWithDecorators(Calculator::class.'@add', ['-10', '-10']);

        $this->assertIsString($result);
        $this->assertEquals('-20', $result);
    }

    public function testMultipleDecorators()
    {
        $container = new Container;

        $container->defineDecorator('noNegativeParam', function ($container, $decorated) {
            return function ($x, $y) use ($container, $decorated) {
                $x = ($x < 0) ? 0 : $x;
                $y = ($y < 0) ? 0 : $y;

                return $container->call($decorated, [$x, $y]);
            };
        });

        $container->defineDecorator('noPositiveResult', function ($container, $decorated) {
            return function (...$params) use ($container, $decorated) {
                return abs($container->call($decorated, $params)) * -1;
            };
        });

        $container->defineDecorator('stringifyResult', function ($container, $decorated) {
            return function (...$params) use ($container, $decorated) {
                return (string) $container->call($decorated, $params);
            };
        });

        $container->decorateWith(Calculator::class.'@add', 'noNegativeParam');
        $container->decorateWith(Calculator::class.'@add', 'noPositiveResult');
        $container->decorateWith(Calculator::class.'@add', 'stringifyResult');

        $result = $container->callWithDecorators(Calculator::class.'@add', ['x' => -100, 'y' => -100]);
        $this->assertEquals('0', $result);
        $this->assertIsString($result);

        $result = $container->callWithDecorators(Calculator::class.'@add', ['x' => 2, 'y' => 2]);

        $this->assertEquals('-4', $result);
        $this->assertIsString($result);

        $result = $container->callWithDecorators(Calculator::class.'@add', ['x' => -200, 'y' => 1]);
        $this->assertEquals('-1', $result);
        $this->assertIsString($result);

        $result = $container->callWithDecorators(Calculator::class.'@add', ['x' => -100, 'y' => -100]);
        $this->assertEquals('0', $result);
        $this->assertIsString($result);
    }
}

interface ICalculator
{
    public function add(int $x, int $y): int;
}

class Calculator implements ICalculator
{
    public function add(int $x, int $y): int
    {
        return $x + $y;
    }
}
