<?php

namespace Illuminate\Tests\Database;

use Illuminate\Console\Command;
use Illuminate\Console\OutputStyle;
use Illuminate\Console\View\Components\Factory;
use Illuminate\Container\Container;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Database\Console\Seeds\SeedCommand;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Events\NullDispatcher;
use Illuminate\Testing\Assert;
use Mockery as m;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

class SeedCommandTest extends TestCase
{
    public function testHandleExecutesSeederRunWithCastedWithParameters()
    {
        SeedCommandExecutedSeeder::$calledWith = null;

        $input = new ArrayInput([
            '--force' => true,
            '--database' => 'sqlite',
            '--class' => SeedCommandExecutedSeeder::class,
            '--with' => ['count=10', 'active=true', 'name=TestUser'],
        ]);
        $output = new NullOutput;
        $outputStyle = new OutputStyle($input, $output);

        $resolver = m::mock(ConnectionResolverInterface::class);
        $resolver->shouldReceive('getDefaultConnection')->once()->andReturn(null);
        $resolver->shouldReceive('setDefaultConnection')->once()->with('sqlite');

        $container = new SeedCommandTestApplication;
        $container->instance(OutputStyle::class, $outputStyle);
        $container->instance(Factory::class, new Factory($outputStyle));

        $command = new SeedCommand($resolver);
        $command->setLaravel($container);

        $command->run($input, $output);

        $this->assertSame([
            'count' => 10,
            'active' => true,
            'name' => 'TestUser',
        ], SeedCommandExecutedSeeder::$calledWith);
    }

    public function testHandleExecutesSeederRunWithContainerDependencyAndWithParameters()
    {
        SeedCommandExecutedWithDependencySeeder::$calledWith = null;

        $input = new ArrayInput([
            '--force' => true,
            '--database' => 'sqlite',
            '--class' => SeedCommandExecutedWithDependencySeeder::class,
            '--with' => ['count=10'],
        ]);
        $output = new NullOutput;
        $outputStyle = new OutputStyle($input, $output);

        $resolver = m::mock(ConnectionResolverInterface::class);
        $resolver->shouldReceive('getDefaultConnection')->once()->andReturn(null);
        $resolver->shouldReceive('setDefaultConnection')->once()->with('sqlite');

        $container = new SeedCommandTestApplication;
        $container->instance(OutputStyle::class, $outputStyle);
        $container->instance(Factory::class, new Factory($outputStyle));

        $command = new SeedCommand($resolver);
        $command->setLaravel($container);

        $command->run($input, $output);

        $this->assertSame([
            'dependency' => SeedCommandDependency::class,
            'count' => 10,
        ], SeedCommandExecutedWithDependencySeeder::$calledWith);
    }

    public function testHandleExecutesSeederRunWithJsonArrayWithParameters()
    {
        SeedCommandExecutedArraySeeder::$calledWith = null;

        $input = new ArrayInput([
            '--force' => true,
            '--database' => 'sqlite',
            '--class' => SeedCommandExecutedArraySeeder::class,
            '--with' => ['tags=["admin","staff"]'],
        ]);
        $output = new NullOutput;
        $outputStyle = new OutputStyle($input, $output);

        $resolver = m::mock(ConnectionResolverInterface::class);
        $resolver->shouldReceive('getDefaultConnection')->once()->andReturn(null);
        $resolver->shouldReceive('setDefaultConnection')->once()->with('sqlite');

        $container = new SeedCommandTestApplication;
        $container->instance(OutputStyle::class, $outputStyle);
        $container->instance(Factory::class, new Factory($outputStyle));

        $command = new SeedCommand($resolver);
        $command->setLaravel($container);

        $command->run($input, $output);

        $this->assertSame(['admin', 'staff'], SeedCommandExecutedArraySeeder::$calledWith);
    }

    public function testHandleExecutesSeederRunWithScalarArrayParameterFallback()
    {
        SeedCommandExecutedArraySeeder::$calledWith = null;

        $input = new ArrayInput([
            '--force' => true,
            '--database' => 'sqlite',
            '--class' => SeedCommandExecutedArraySeeder::class,
            '--with' => ['tags=admin'],
        ]);
        $output = new NullOutput;
        $outputStyle = new OutputStyle($input, $output);

        $resolver = m::mock(ConnectionResolverInterface::class);
        $resolver->shouldReceive('getDefaultConnection')->once()->andReturn(null);
        $resolver->shouldReceive('setDefaultConnection')->once()->with('sqlite');

        $container = new SeedCommandTestApplication;
        $container->instance(OutputStyle::class, $outputStyle);
        $container->instance(Factory::class, new Factory($outputStyle));

        $command = new SeedCommand($resolver);
        $command->setLaravel($container);

        $command->run($input, $output);

        $this->assertSame(['admin'], SeedCommandExecutedArraySeeder::$calledWith);
    }

    public function testHandle()
    {
        $input = new ArrayInput(['--force' => true, '--database' => 'sqlite']);
        $output = new NullOutput;
        $outputStyle = new OutputStyle($input, $output);

        $seeder = m::mock(Seeder::class);
        $seeder->shouldReceive('setContainer')->once()->andReturnSelf();
        $seeder->shouldReceive('setCommand')->once()->andReturnSelf();
        $seeder->shouldReceive('__invoke')->once();

        $resolver = m::mock(ConnectionResolverInterface::class);
        $resolver->shouldReceive('getDefaultConnection')->once();
        $resolver->shouldReceive('setDefaultConnection')->once()->with('sqlite');

        $container = m::mock(Container::class);
        $container->shouldReceive('call');
        $container->shouldReceive('environment')->once()->andReturn('testing');
        $container->shouldReceive('runningUnitTests')->andReturn('true');
        $container->shouldReceive('make')->with('DatabaseSeeder')->andReturn($seeder);
        $container->shouldReceive('make')->with(OutputStyle::class, m::any())->andReturn(
            $outputStyle
        );
        $container->shouldReceive('make')->with(Factory::class, m::any())->andReturn(
            new Factory($outputStyle)
        );

        $command = new SeedCommand($resolver);
        $command->setLaravel($container);

        // call run to set up IO, then fire manually.
        $command->run($input, $output);
        $command->handle();

        $container->shouldHaveReceived('call')->with([$command, 'handle']);
    }

    public function testHandlePassesWithParametersToSeeder()
    {
        $input = new ArrayInput([
            '--force' => true,
            '--database' => 'sqlite',
            '--class' => SeedCommandTypedParametersSeeder::class,
            '--with' => ['count=10', 'active=true', 'name=TestUser'],
        ]);
        $output = new NullOutput;
        $outputStyle = new OutputStyle($input, $output);

        $seeder = m::mock(new SeedCommandTypedParametersSeeder);
        $seeder->shouldReceive('setContainer')->once()->andReturnSelf();
        $seeder->shouldReceive('setCommand')->once()->andReturnSelf();
        $seeder->shouldReceive('__invoke')->once()->with([
            'count' => 10,
            'active' => true,
            'name' => 'TestUser',
        ]);

        $resolver = m::mock(ConnectionResolverInterface::class);
        $resolver->shouldReceive('getDefaultConnection')->once();
        $resolver->shouldReceive('setDefaultConnection')->once()->with('sqlite');

        $container = m::mock(Container::class);
        $container->shouldReceive('call');
        $container->shouldReceive('environment')->once()->andReturn('testing');
        $container->shouldReceive('runningUnitTests')->andReturn('true');
        $container->shouldReceive('make')->with(SeedCommandTypedParametersSeeder::class)->andReturn($seeder);
        $container->shouldReceive('make')->with(OutputStyle::class, m::any())->andReturn(
            $outputStyle
        );
        $container->shouldReceive('make')->with(Factory::class, m::any())->andReturn(
            new Factory($outputStyle)
        );

        $command = new SeedCommand($resolver);
        $command->setLaravel($container);

        $command->run($input, $output);
        $command->handle();
    }

    public function testHandleThrowsExceptionForUnknownSeederParameter()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown seeder parameter [unknown]');

        $input = new ArrayInput([
            '--force' => true,
            '--database' => 'sqlite',
            '--class' => SeedCommandTypedParametersSeeder::class,
            '--with' => ['unknown=value'],
        ]);
        $output = new NullOutput;
        $outputStyle = new OutputStyle($input, $output);

        $seeder = m::mock(new SeedCommandTypedParametersSeeder);
        $seeder->shouldReceive('setContainer')->once()->andReturnSelf();
        $seeder->shouldReceive('setCommand')->once()->andReturnSelf();
        $seeder->shouldNotReceive('__invoke');

        $resolver = m::mock(ConnectionResolverInterface::class);
        $resolver->shouldReceive('getDefaultConnection')->once();
        $resolver->shouldReceive('setDefaultConnection')->once()->with('sqlite');

        $container = m::mock(Container::class);
        $container->shouldReceive('call');
        $container->shouldReceive('environment')->once()->andReturn('testing');
        $container->shouldReceive('runningUnitTests')->andReturn('true');
        $container->shouldReceive('make')->with(SeedCommandTypedParametersSeeder::class)->andReturn($seeder);
        $container->shouldReceive('make')->with(OutputStyle::class, m::any())->andReturn(
            $outputStyle
        );
        $container->shouldReceive('make')->with(Factory::class, m::any())->andReturn(
            new Factory($outputStyle)
        );

        $command = new SeedCommand($resolver);
        $command->setLaravel($container);

        $command->run($input, $output);
        $command->handle();
    }

    public function testHandleThrowsExceptionForNonScalarSeederParameter()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unable to pass [date]');

        $input = new ArrayInput([
            '--force' => true,
            '--database' => 'sqlite',
            '--class' => SeedCommandNonScalarParameterSeeder::class,
            '--with' => ['date=2024-01-01'],
        ]);
        $output = new NullOutput;
        $outputStyle = new OutputStyle($input, $output);

        $seeder = m::mock(new SeedCommandNonScalarParameterSeeder);
        $seeder->shouldReceive('setContainer')->once()->andReturnSelf();
        $seeder->shouldReceive('setCommand')->once()->andReturnSelf();
        $seeder->shouldNotReceive('__invoke');

        $resolver = m::mock(ConnectionResolverInterface::class);
        $resolver->shouldReceive('getDefaultConnection')->once();
        $resolver->shouldReceive('setDefaultConnection')->once()->with('sqlite');

        $container = m::mock(Container::class);
        $container->shouldReceive('call');
        $container->shouldReceive('environment')->once()->andReturn('testing');
        $container->shouldReceive('runningUnitTests')->andReturn('true');
        $container->shouldReceive('make')->with(SeedCommandNonScalarParameterSeeder::class)->andReturn($seeder);
        $container->shouldReceive('make')->with(OutputStyle::class, m::any())->andReturn(
            $outputStyle
        );
        $container->shouldReceive('make')->with(Factory::class, m::any())->andReturn(
            new Factory($outputStyle)
        );

        $command = new SeedCommand($resolver);
        $command->setLaravel($container);

        $command->run($input, $output);
        $command->handle();
    }

    public function testHandleThrowsExceptionForInvalidIntegerParameter()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The [count] parameter');

        $input = new ArrayInput([
            '--force' => true,
            '--database' => 'sqlite',
            '--class' => SeedCommandTypedParametersSeeder::class,
            '--with' => ['count=abc'],
        ]);
        $output = new NullOutput;
        $outputStyle = new OutputStyle($input, $output);

        $seeder = m::mock(new SeedCommandTypedParametersSeeder);
        $seeder->shouldReceive('setContainer')->once()->andReturnSelf();
        $seeder->shouldReceive('setCommand')->once()->andReturnSelf();
        $seeder->shouldNotReceive('__invoke');

        $resolver = m::mock(ConnectionResolverInterface::class);
        $resolver->shouldReceive('getDefaultConnection')->once();
        $resolver->shouldReceive('setDefaultConnection')->once()->with('sqlite');

        $container = m::mock(Container::class);
        $container->shouldReceive('call');
        $container->shouldReceive('environment')->once()->andReturn('testing');
        $container->shouldReceive('runningUnitTests')->andReturn('true');
        $container->shouldReceive('make')->with(SeedCommandTypedParametersSeeder::class)->andReturn($seeder);
        $container->shouldReceive('make')->with(OutputStyle::class, m::any())->andReturn(
            $outputStyle
        );
        $container->shouldReceive('make')->with(Factory::class, m::any())->andReturn(
            new Factory($outputStyle)
        );

        $command = new SeedCommand($resolver);
        $command->setLaravel($container);

        $command->run($input, $output);
        $command->handle();
    }

    public function testHandleThrowsExceptionForInvalidBooleanParameter()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The [active] parameter');

        $input = new ArrayInput([
            '--force' => true,
            '--database' => 'sqlite',
            '--class' => SeedCommandTypedParametersSeeder::class,
            '--with' => ['active=maybe'],
        ]);
        $output = new NullOutput;
        $outputStyle = new OutputStyle($input, $output);

        $seeder = m::mock(new SeedCommandTypedParametersSeeder);
        $seeder->shouldReceive('setContainer')->once()->andReturnSelf();
        $seeder->shouldReceive('setCommand')->once()->andReturnSelf();
        $seeder->shouldNotReceive('__invoke');

        $resolver = m::mock(ConnectionResolverInterface::class);
        $resolver->shouldReceive('getDefaultConnection')->once();
        $resolver->shouldReceive('setDefaultConnection')->once()->with('sqlite');

        $container = m::mock(Container::class);
        $container->shouldReceive('call');
        $container->shouldReceive('environment')->once()->andReturn('testing');
        $container->shouldReceive('runningUnitTests')->andReturn('true');
        $container->shouldReceive('make')->with(SeedCommandTypedParametersSeeder::class)->andReturn($seeder);
        $container->shouldReceive('make')->with(OutputStyle::class, m::any())->andReturn(
            $outputStyle
        );
        $container->shouldReceive('make')->with(Factory::class, m::any())->andReturn(
            new Factory($outputStyle)
        );

        $command = new SeedCommand($resolver);
        $command->setLaravel($container);

        $command->run($input, $output);
        $command->handle();
    }

    public function testHandleThrowsExceptionForInvalidJsonArrayParameter()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The [tags] parameter');
        $this->expectExceptionMessage('must be a valid JSON array');

        $input = new ArrayInput([
            '--force' => true,
            '--database' => 'sqlite',
            '--class' => SeedCommandArrayParameterSeeder::class,
            '--with' => ['tags=[invalid'],
        ]);
        $output = new NullOutput;
        $outputStyle = new OutputStyle($input, $output);

        $seeder = m::mock(new SeedCommandArrayParameterSeeder);
        $seeder->shouldReceive('setContainer')->once()->andReturnSelf();
        $seeder->shouldReceive('setCommand')->once()->andReturnSelf();
        $seeder->shouldNotReceive('__invoke');

        $resolver = m::mock(ConnectionResolverInterface::class);
        $resolver->shouldReceive('getDefaultConnection')->once();
        $resolver->shouldReceive('setDefaultConnection')->once()->with('sqlite');

        $container = m::mock(Container::class);
        $container->shouldReceive('call');
        $container->shouldReceive('environment')->once()->andReturn('testing');
        $container->shouldReceive('runningUnitTests')->andReturn('true');
        $container->shouldReceive('make')->with(SeedCommandArrayParameterSeeder::class)->andReturn($seeder);
        $container->shouldReceive('make')->with(OutputStyle::class, m::any())->andReturn(
            $outputStyle
        );
        $container->shouldReceive('make')->with(Factory::class, m::any())->andReturn(
            new Factory($outputStyle)
        );

        $command = new SeedCommand($resolver);
        $command->setLaravel($container);

        $command->run($input, $output);
        $command->handle();
    }

    public function testWithoutModelEvents()
    {
        $input = new ArrayInput([
            '--force' => true,
            '--database' => 'sqlite',
            '--class' => UserWithoutModelEventsSeeder::class,
        ]);
        $output = new NullOutput;
        $outputStyle = new OutputStyle($input, $output);

        $instance = new UserWithoutModelEventsSeeder();

        $seeder = m::mock($instance);
        $seeder->shouldReceive('setContainer')->once()->andReturnSelf();
        $seeder->shouldReceive('setCommand')->once()->andReturnSelf();

        $resolver = m::mock(ConnectionResolverInterface::class);
        $resolver->shouldReceive('getDefaultConnection')->once();
        $resolver->shouldReceive('setDefaultConnection')->once()->with('sqlite');

        $container = m::mock(Container::class);
        $container->shouldReceive('call');
        $container->shouldReceive('environment')->once()->andReturn('testing');
        $container->shouldReceive('runningUnitTests')->andReturn('true');
        $container->shouldReceive('make')->with(UserWithoutModelEventsSeeder::class)->andReturn($seeder);
        $container->shouldReceive('make')->with(OutputStyle::class, m::any())->andReturn(
            $outputStyle
        );
        $container->shouldReceive('make')->with(Factory::class, m::any())->andReturn(
            new Factory($outputStyle)
        );

        $command = new SeedCommand($resolver);
        $command->setLaravel($container);

        Model::setEventDispatcher($dispatcher = m::mock(Dispatcher::class));

        // call run to set up IO, then fire manually.
        $command->run($input, $output);
        $command->handle();

        Assert::assertSame($dispatcher, Model::getEventDispatcher());

        $container->shouldHaveReceived('call')->with([$command, 'handle']);
    }

    public function testProhibitable()
    {
        $input = new ArrayInput([]);
        $output = new NullOutput;
        $outputStyle = new OutputStyle($input, $output);

        $resolver = m::mock(ConnectionResolverInterface::class);

        $container = m::mock(Container::class);
        $container->shouldReceive('call');
        $container->shouldReceive('runningUnitTests')->andReturn('true');
        $container->shouldReceive('make')->with(OutputStyle::class, m::any())->andReturn(
            $outputStyle
        );
        $container->shouldReceive('make')->with(Factory::class, m::any())->andReturn(
            new Factory($outputStyle)
        );

        $command = new SeedCommand($resolver);
        $command->setLaravel($container);

        // call run to set up IO, then fire manually.
        $command->run($input, $output);

        SeedCommand::prohibit();

        Assert::assertSame(Command::FAILURE, $command->handle());
    }

    #[DataProvider('parseParametersProvider')]
    public function testParseParameters($input, $expected)
    {
        $arrayInput = new ArrayInput(['--with' => $input]);
        $output = new NullOutput;
        $outputStyle = new OutputStyle($arrayInput, $output);

        $resolver = m::mock(ConnectionResolverInterface::class);
        $resolver->shouldIgnoreMissing();

        $container = m::mock(Container::class);
        $container->shouldIgnoreMissing();
        $container->shouldReceive('make')
            ->with(OutputStyle::class, m::any())
            ->andReturn($outputStyle);

        $command = new SeedCommand($resolver);
        $command->setLaravel($container);
        $command->run($arrayInput, $output);

        $reflection = new \ReflectionClass($command);
        $method = $reflection->getMethod('parseParameters');
        $method->setAccessible(true);

        $result = $method->invoke($command);

        $this->assertEquals($expected, $result);
    }

    public function testParseParametersThrowsExceptionForInvalidFormat()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The --with option expects values in key=value format.');

        $arrayInput = new ArrayInput(['--with' => ['invalidparam']]);
        $output = new NullOutput;
        $outputStyle = new OutputStyle($arrayInput, $output);

        $resolver = m::mock(ConnectionResolverInterface::class);
        $resolver->shouldIgnoreMissing();

        $container = m::mock(Container::class);
        $container->shouldIgnoreMissing();
        $container->shouldReceive('make')
            ->with(OutputStyle::class, m::any())
            ->andReturn($outputStyle);

        $command = new SeedCommand($resolver);
        $command->setLaravel($container);
        $command->run($arrayInput, $output);

        $reflection = new \ReflectionClass($command);
        $method = $reflection->getMethod('parseParameters');
        $method->setAccessible(true);
        $method->invoke($command);
    }

    public function testParseParametersThrowsExceptionForEmptyKeys()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The --with option expects non-empty keys.');

        $arrayInput = new ArrayInput(['--with' => ['=orphaned-value']]);
        $output = new NullOutput;
        $outputStyle = new OutputStyle($arrayInput, $output);

        $resolver = m::mock(ConnectionResolverInterface::class);
        $resolver->shouldIgnoreMissing();

        $container = m::mock(Container::class);
        $container->shouldIgnoreMissing();
        $container->shouldReceive('make')
            ->with(OutputStyle::class, m::any())
            ->andReturn($outputStyle);

        $command = new SeedCommand($resolver);
        $command->setLaravel($container);
        $command->run($arrayInput, $output);

        $reflection = new \ReflectionClass($command);
        $method = $reflection->getMethod('parseParameters');
        $method->setAccessible(true);
        $method->invoke($command);
    }

    public static function parseParametersProvider()
    {
        return [
            'multiple with options' => [
                ['count=10', 'active=true', 'name=TestUser'],
                ['count' => '10', 'active' => 'true', 'name' => 'TestUser'],
            ],

            'comma separated with options' => [
                ['count=10,active=true,name=TestUser'],
                ['count' => '10,active=true,name=TestUser'],
            ],

            'multiple values for the same key' => [
                ['count=10', 'count=20'],
                ['count' => '20'],
            ],

            'single with option' => [
                ['count=10'],
                ['count' => '10'],
            ],

            'spaces around equals' => [
                ['count = 10', ' active = true '],
                ['count' => '10', 'active' => 'true'],
            ],

            'value with equals' => [
                ['url=https://example.com?param=value', 'query=SELECT * FROM users WHERE id=1'],
                ['url' => 'https://example.com?param=value', 'query' => 'SELECT * FROM users WHERE id=1'],
            ],

            'empty values' => [
                ['key=', 'another='],
                ['key' => '', 'another' => ''],
            ],

            'no parameters' => [
                [],
                [],
            ],

            'complex values' => [
                ['json={"key":"value","nested":{"prop":"val"}}', 'csv=val1,val2,val3', 'path=/var/www/html'],
                ['json' => '{"key":"value","nested":{"prop":"val"}}', 'csv' => 'val1,val2,val3', 'path' => '/var/www/html'],
            ],
        ];
    }

    protected function tearDown(): void
    {
        SeedCommandExecutedSeeder::$calledWith = null;
        SeedCommandExecutedWithDependencySeeder::$calledWith = null;
        SeedCommandExecutedArraySeeder::$calledWith = null;

        SeedCommand::prohibit(false);

        Model::unsetEventDispatcher();

        parent::tearDown();
    }
}

class UserWithoutModelEventsSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run()
    {
        Assert::assertInstanceOf(NullDispatcher::class, Model::getEventDispatcher());
    }
}

class SeedCommandTypedParametersSeeder extends Seeder
{
    public function run(int $count = 0, bool $active = false, string $name = '')
    {
        //
    }
}

class SeedCommandNonScalarParameterSeeder extends Seeder
{
    public function run(\DateTime $date)
    {
        //
    }
}

class SeedCommandArrayParameterSeeder extends Seeder
{
    public function run(array $tags = [])
    {
        //
    }
}

class SeedCommandExecutedSeeder extends Seeder
{
    public static ?array $calledWith = null;

    public function run(int $count = 0, bool $active = false, string $name = '')
    {
        static::$calledWith = [
            'count' => $count,
            'active' => $active,
            'name' => $name,
        ];
    }
}

class SeedCommandExecutedWithDependencySeeder extends Seeder
{
    public static ?array $calledWith = null;

    public function run(SeedCommandDependency $dependency, int $count = 0)
    {
        static::$calledWith = [
            'dependency' => get_class($dependency),
            'count' => $count,
        ];
    }
}

class SeedCommandExecutedArraySeeder extends Seeder
{
    public static ?array $calledWith = null;

    public function run(array $tags = [])
    {
        static::$calledWith = $tags;
    }
}

class SeedCommandDependency
{
    //
}

class SeedCommandTestApplication extends Container
{
    public function environment(...$environments)
    {
        return 'testing';
    }

    public function runningUnitTests()
    {
        return true;
    }
}
