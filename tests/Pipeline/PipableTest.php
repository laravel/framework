<?php

namespace Illuminate\Tests\Pipeline;

use Exception;
use Illuminate\Container\Container;
use Illuminate\Pipeline\Pipable;
use PHPUnit\Framework\TestCase;

class PipableTest extends TestCase
{
    public function testBasicPipable()
    {
        $calc = new PipableCalculator();

        $pipes = [
            ToIntParamsPipe::class,
            new ToStringResultPipe(),
        ];

        $strFive = $calc->pipe($pipes, 'handle', new Container)->add('3', '2');

        $this->assertEquals('5', $strFive);
        $this->assertIsString($strFive);
    }

    public function testPrivateMethodsCanBePiped()
    {
        $calc = new SecretiveCalculator();

        $this->assertEquals(2, $calc->iPipeToPrivate());
    }

    public function testAlternativeMethodsAsPipes()
    {
        $calc = new PipableCalculator();

        $pipes = [
            ToIntParamsPipe::class,
            new ToStringResultPipe(),
        ];

        $strFive = $calc->pipe($pipes, 'alternative_handler', new Container)->add('100', '100');

        $this->assertEquals('3', $strFive);
        $this->assertIsString($strFive);
    }

    public function testItExecutesPipesInCaseOfException()
    {
        $value = (new FailyPipable())->pipe(MyPipe::class, 'handle', new Container)->faily();

        $this->assertInstanceOf(Exception::class, MyPipe::$resp);
        $this->assertEquals('Oh My God', $value);
    }
}

class PipableCalculator
{
    use Pipable;

    public function add($num1, $num2): int
    {
        if (is_int($num1) && is_int($num2)) {
            return $num1 + $num2;
        }

        return 0;
    }
}

class SecretiveCalculator
{
    use Pipable;

    private function privateAdd(int $num1, int $num2): int
    {
        return $num1 + $num2;
    }

    public function iPipeToPrivate()
    {
        return $this->pipe(ToIntParamsPipe::class, 'handle', new Container)->privateAdd('1', '1');
    }
}

class FailyPipable
{
    use Pipable;

    public function faily()
    {
        throw new Exception('Oh My God');
    }
}

class ToIntParamsPipe
{
    public function handle($data, $next)
    {
        $data[0] = (int) $data[0];
        $data[1] = (int) $data[1];

        return $next($data);
    }

    public function alternative_handler($data, $next)
    {
        $data = [1, 1];

        return $next($data);
    }
}

class ToStringResultPipe
{
    public function handle($data, $next)
    {
        return (string) $next($data);
    }

    public function alternative_handler($data, $next)
    {
        return (string) ($next($data) + 1);
    }
}

class MyPipe
{
    public static $resp;

    public function handle($data, $next)
    {
        $result = $next($data);
        self::$resp = $result;

        return $result->getMessage();
    }
}
