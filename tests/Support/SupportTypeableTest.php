<?php

namespace Illuminate\Tests\Support;

use Illuminate\Console\Application;
use Illuminate\Console\Command;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Stringable;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\NullOutput;

class SupportTypeableTest extends TestCase
{
    public function testTypeableRequest(): void
    {
        $request = Request::create('/', 'GET', ['foo' => 'bar', 'empty' => '', 'checked' => 'on', 'strval' => '1234']);

        $this->assertInstanceOf(Stringable::class, $request->string('strval'));
        $this->assertEquals('1234', $request->string('strval'));

        $this->assertIsBool($request->boolean('checked'));
        $this->assertTrue($request->boolean('checked'));

        $this->assertIsInt($request->integer('strval'));
        $this->assertIsInt($request->integer('strval'));
        $this->assertEquals(1234, $request->integer('strval'));

        $this->assertIsFloat($request->float('strval'));
        $this->assertEquals(1234.0, $request->float('strval'));
    }

    public function testTypeableRoute(): void
    {
        $request = Request::create('/users/1234', 'GET');

        $route = new Route('GET', '/users/{userid}', function () {
            return 'Hello World';
        });

        $route->bind($request);

        $this->assertInstanceOf(Stringable::class, $route->typed()->parameter->string('userid'));
        $this->assertEquals('1234', $route->typed()->parameter->string('userid'));

        $this->assertIsBool($boolean = $route->typed()->parameter->boolean('unexisting'));
        $this->assertFalse($boolean);

        $this->assertIsInt($integer = $route->typed()->parameter->integer('userid'));
        $this->assertEquals(1234, $integer);

        $this->assertIsFloat($float = $route->typed()->parameter->float('userid'));
        $this->assertEquals(1234.0, $float);
    }

    public function testTypeableCommand(): void
    {
        $app = new Application(
            $laravel = new \Illuminate\Foundation\Application(__DIR__),
            $events = m::mock(Dispatcher::class, ['dispatch' => null, 'fire' => null]),
            'testing'
        );

        $app->addCommands([$command = new FooCommand()]);

        $command->setLaravel($laravel);

        $input = new StringInput('foo:command first second --userid=4 --all');
        $output = new NullOutput;

        $command->run($input, $output);

        $this->assertInstanceOf(Stringable::class, $string = $command->typed()->option->string('userid'));
        $this->assertEquals('4', $string);

        $this->assertIsBool($boolean = $command->typed()->option->boolean('all'));
        $this->assertTrue($boolean);

        $this->assertIsInt($integer = $command->typed()->option->integer('userid'));
        $this->assertEquals(4, $integer);

        $this->assertIsFloat($float = $command->typed()->option->float('userid'));
        $this->assertEquals(4.0, $float);

        $this->assertIsArray($array = $command->typed()->argument->array('theargument'));
        $this->assertSame(['first', 'second'], $array);
    }
}

class FooCommand extends Command
{
    protected $signature = 'foo:command {theargument*} {--userid=} {--all}';

    public function handle(): int
    {
        return self::SUCCESS;
    }
}
