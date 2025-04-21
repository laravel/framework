<?php

namespace Illuminate\Tests\Database;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Container\Container;
use Illuminate\Database\Attributes\SeedTask;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Seeder;
use Mockery as m;
use Mockery\Mock;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

class TestSeeder extends Seeder
{
    public function run()
    {
        //
    }
}

class TestDepsSeeder extends Seeder
{
    public function run(Mock $someDependency, $someParam = '')
    {
        //
    }
}

class TestSeedTaskSeeder extends Seeder
{
    public function seedFirst()
    {
        //
    }

    public function seedSecond()
    {
        //
    }

    #[SeedTask]
    public function thirdThing()
    {
        //
    }

    #[SeedTask(as: 'Test name')]
    public function fourth()
    {
        //
    }
}

class TestSeedTaskSeederWithBeforeAndAfter extends Seeder
{
    public function before()
    {
        //
    }

    public function seed()
    {
        //
    }

    public function after()
    {
        //
    }
}

class TestSeederSkipsCompletely extends Seeder
{
    public function before()
    {
        $this->skip('test reason');
    }

    public function seed()
    {
        //
    }
}

class TestSeederSkipsSingleSeedTask extends Seeder
{
    public function before()
    {
        //
    }

    public function seedFirst()
    {
        //
    }

    public function seedSecond()
    {
        $this->skip('test reason');

        throw new Exception('Should not be thrown');
    }

    public function seedThird()
    {
        //
    }
}

class TestSeedTaskSeederWithError extends Seeder
{
    public function seedFirst()
    {
        //
    }

    public function seedSecond()
    {
        throw new Exception('test error');
    }

    public function seedThird()
    {
        //
    }
}

class TestSeedTaskSeederWithoutTransactions extends Seeder
{
    public function seedFirst()
    {
        //
    }

    public function useTransactions()
    {
        return false;
    }
}

class TestSeederCallsSeedTask extends Seeder
{
    public function run()
    {
        $this->call(TestSeederSkipsCompletely::class);
    }
}

class TestSeederCallsFailingSeedTask extends Seeder
{
    public function run()
    {
        $this->call(TestSeedTaskSeederRunsOnErrorMethod::class);
    }
}

class TestSeedTaskSeederRunsOnErrorMethod extends Seeder
{
    public function seedFirst()
    {
        throw new Exception('test error');
    }

    public function seedSecond()
    {
        //
    }

    public function onError($throwable)
    {
        throw new Exception('finally called', 0, $throwable);
    }
}

class DatabaseSeederTest extends TestCase
{
    protected function setUp(): void
    {
        Seeder::setContinue([]);
    }

    protected function tearDown(): void
    {
        Seeder::setContinue([]);

        m::close();
    }

    public function testCallResolveTheClassAndCallsRun()
    {
        $seeder = new TestSeeder;
        $seeder->setContainer($container = m::mock(Container::class));
        $output = m::mock(OutputInterface::class);
        $output->shouldReceive('writeln')->times(3);
        $command = m::mock(Command::class);
        $command->shouldReceive('getOutput')->times(3)->andReturn($output);
        $seeder->setCommand($command);
        $container->shouldReceive('make')->once()->with('ClassName')->andReturn($child = m::mock(Seeder::class));
        $child->shouldReceive('setContainer')->once()->with($container)->andReturn($child);
        $child->shouldReceive('setCommand')->once()->with($command)->andReturn($child);
        $child->shouldReceive('setSilent')->with(false)->once();
        $child->shouldReceive('__invoke')->once();

        $seeder->call('ClassName');
    }

    public function testSetContainer()
    {
        $seeder = new TestSeeder;
        $container = m::mock(Container::class);
        $this->assertEquals($seeder->setContainer($container), $seeder);
    }

    public function testSetCommand()
    {
        $seeder = new TestSeeder;
        $command = m::mock(Command::class);
        $this->assertEquals($seeder->setCommand($command), $seeder);
    }

    public function testInjectDependenciesOnRunMethod()
    {
        $container = m::mock(Container::class);
        $container->shouldReceive('call');

        $seeder = new TestDepsSeeder;
        $seeder->setContainer($container);

        $seeder->__invoke();

        $container->shouldHaveReceived('call')->once()->with([$seeder, 'run'], []);
    }

    public function testSendParamsOnCallMethodWithDeps()
    {
        $container = m::mock(Container::class);
        $container->shouldReceive('call');

        $seeder = new TestDepsSeeder;
        $seeder->setContainer($container);

        $seeder->__invoke(['test1', 'test2']);

        $container->shouldHaveReceived('call')->once()->with([$seeder, 'run'], ['test1', 'test2']);
    }

    public function testSeedTasks()
    {
        $seeder = new TestSeedTaskSeeder();

        $container = m::mock(Container::class);
        $container->shouldReceive('call')->once()->with([$seeder, 'runSeedTasks'], []);

        $seeder->setContainer($container);

        $seeder->__invoke();
    }

    public function testSeedsTasksInOrder()
    {
        $seeder = new TestSeedTaskSeeder();

        $output = m::mock(OutputInterface::class);
        $output->shouldReceive('writeln')->with(
            '  ↳ Seed first <fg=gray>..........................................................</> <fg=green;options=bold>DONE</>  ',
            32,
        );
        $output->shouldReceive('writeln')->with(
            '  ↳ Seed second <fg=gray>.........................................................</> <fg=green;options=bold>DONE</>  ',
            32,
        );
        $output->shouldReceive('writeln')->with(
            '  ↳ Third thing <fg=gray>.........................................................</> <fg=green;options=bold>DONE</>  ',
            32,
        );
        $output->shouldReceive('writeln')->with(
            '  ↳ Test name <fg=gray>...........................................................</> <fg=green;options=bold>DONE</>  ',
            32,
        );

        $command = m::mock(Command::class);
        $command->shouldReceive('getOutput')->andReturn($output);

        $seeder->setCommand($command);

        $connection = m::mock(ConnectionInterface::class);
        $connection->shouldReceive('getConnection->transaction')->andReturnUsing(fn ($callback) => $callback());

        $container = m::mock(Container::class);

        $container->shouldReceive('make')->with('db.connection')->andReturn($connection);

        $container->shouldReceive('call')->once()->with([$seeder, 'seedFirst'], []);
        $container->shouldReceive('call')->once()->with([$seeder, 'seedSecond'], []);
        $container->shouldReceive('call')->once()->with([$seeder, 'thirdThing'], []);
        $container->shouldReceive('call')->once()->with([$seeder, 'fourth'], []);

        $container->shouldReceive('call')->once()->withArgs(function ($callable, $parameters) use ($seeder) {
            $this->assertSame([$seeder, 'runSeedTasks'], $callable);
            $this->assertSame([], $parameters);

            $callable();

            return true;
        })->andReturn();

        $seeder->setContainer($container);

        $seeder->__invoke();
    }

    public function testSeedsTasksSeederBeforeAndAfter()
    {
        $seeder = new TestSeedTaskSeederWithBeforeAndAfter();

        $connection = m::mock(ConnectionInterface::class);
        $connection->shouldReceive('getConnection->transaction')->andReturnUsing(fn ($callback) => $callback());

        $container = m::mock(Container::class);
        $container->shouldReceive('make')->with('db.connection')->andReturn($connection);
        $container->shouldReceive('call')->once()->with([$seeder, 'before']);
        $container->shouldReceive('call')->once()->with([$seeder, 'seed'], []);
        $container->shouldReceive('call')->once()->with([$seeder, 'after']);
        $container->shouldReceive('call')->with([$seeder, 'runSeedTasks'], [])->andReturnUsing(fn ($callback) => $callback());

        $seeder->setContainer($container);

        $seeder->__invoke();
    }

    public function testSeedsTasksSeederSkipsCompletely()
    {
        $seeder = new TestSeederSkipsCompletely();

        $output = m::mock(OutputInterface::class);
        $output->shouldReceive('writeln')->with(
            '  Illuminate\Tests\Database\TestSeederSkipsCompletely <fg=gray>................</> <fg=yellow;options=bold>RUNNING</>  ',
            32,
        );
        $output->shouldReceive('writeln')->with(
            '  Illuminate\Tests\Database\TestSeederSkipsCompletely <fg=gray>................</> <fg=cyan;options=bold>SKIPPED</>  ',
            32,
        );
        $output->shouldReceive('writeln')->with(
            '  🛈 test reason <fg=gray>..............................................................</>  ',
            32,
        );

        $command = m::mock(Command::class);
        $command->shouldReceive('getOutput')->andReturn($output);

        $seeder->setCommand($command);

        $connection = m::mock(ConnectionInterface::class);
        $connection->shouldReceive('getConnection->transaction')->never();

        $container = m::mock(Container::class);
        $container->shouldReceive('make')->with(TestSeederSkipsCompletely::class)->andReturn($seeder);
        $container->shouldReceive('make')->with('db.connection')->andReturn($connection);
        $container->shouldReceive('call')->once()->with([$seeder, 'before'])->andReturnUsing(fn ($callback) => $callback());
        $container->shouldReceive('call')->never()->with([$seeder, 'seed'], []);
        $container->shouldReceive('call')->never()->with([$seeder, 'after']);
        $container->shouldReceive('call')->with([$seeder, 'runSeedTasks'], [])->andReturnUsing(fn ($callback) => $callback());

        $seeder->setContainer($container);

        $class = new TestSeederCallsSeedTask();
        $class->setContainer($container);
        $class->setCommand($command);

        $container->shouldReceive('call')->with([$class, 'run'], [])->andReturnUsing(fn ($callable) => $callable());

        $class->__invoke();
    }

    public function testSeedsTaskSeederSkipsSingleTask()
    {
        $seeder = new TestSeederSkipsSingleSeedTask();

        $output = m::mock(OutputInterface::class);
        $output->shouldReceive('writeln')->with(
            '  ↳ Seed first <fg=gray>..........................................................</> <fg=green;options=bold>DONE</>  ',
            32,
        );
        $output->shouldReceive('writeln')->with(
            '  ↳ Seed second <fg=gray>......................................................</> <fg=blue;options=bold>SKIPPED</>  ',
            32,
        );
        $output->shouldReceive('writeln')->with(
            '  🛈 test reason <fg=gray>..............................................................</>  ',
            32,
        );
        $output->shouldReceive('writeln')->with(
            '  ↳ Seed third <fg=gray>..........................................................</> <fg=green;options=bold>DONE</>  ',
            32,
        );

        $command = m::mock(Command::class);
        $command->shouldReceive('getOutput')->andReturn($output);

        $seeder->setCommand($command);

        $connection = m::mock(ConnectionInterface::class);
        $connection->shouldReceive('getConnection->transaction')->andReturnUsing(fn ($callback) => $callback());

        $container = m::mock(Container::class);
        $container->shouldReceive('make')->with('db.connection')->andReturn($connection);
        $container->shouldReceive('call')->once()->with([$seeder, 'before']);
        $container->shouldReceive('call')->once()->with([$seeder, 'seedFirst'], []);
        $container->shouldReceive('call')->once()->with([$seeder, 'seedSecond'], [])->andReturnUsing(fn ($callback) => $callback());
        $container->shouldReceive('call')->once()->with([$seeder, 'seedThird'], []);
        $container->shouldReceive('call')->with([$seeder, 'runSeedTasks'], [])->andReturnUsing(fn ($callback) => $callback());

        $seeder->setContainer($container);

        $seeder->__invoke();
    }

    public function testSeedTaskSeederWithError()
    {
        $seeder = new TestSeedTaskSeederWithError();

        $output = m::mock(OutputInterface::class);
        $output->shouldReceive('writeln')->with(
            '  ↳ Seed first <fg=gray>..........................................................</> <fg=green;options=bold>DONE</>  ',
            32,
        );
        $output->shouldReceive('writeln')->with(
            '  ⚠ Seed second <fg=gray>........................................................</> <fg=red;options=bold>ERROR</>  ',
            32,
        );

        $command = m::mock(Command::class);
        $command->shouldReceive('getOutput')->andReturn($output);

        $seeder->setCommand($command);

        $connection = m::mock(ConnectionInterface::class);
        $connection->shouldReceive('getConnection->transaction')->andReturnUsing(fn ($callback) => $callback());

        $container = m::mock(Container::class);
        $container->shouldReceive('make')->with('db.connection')->andReturn($connection);
        $container->shouldReceive('call')->once()->with([$seeder, 'seedFirst'], []);
        $container->shouldReceive('call')->once()->with([$seeder, 'seedSecond'], [])->andReturnUsing(fn ($callback) => $callback());
        $container->shouldReceive('call')->never()->with([$seeder, 'seedThird'], []);
        $container->shouldReceive('call')->with([$seeder, 'runSeedTasks'], [])->andReturnUsing(fn ($callback) => $callback());

        $seeder->setContainer($container);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('test error');

        try {
            $seeder->__invoke();
        } catch (Throwable $e) {
            $this->assertSame([TestSeedTaskSeederWithError::class => ['seedFirst' => true]], $seeder->getContinue());

            throw $e;
        }
    }

    public function testSeedTaskSeederContinuesFromPastSeeding()
    {
        $seeder = new TestSeedTaskSeeder();
        $seeder->setContinue([
            TestSeedTaskSeeder::class => [
                'seedFirst' => true,
            ],
        ]);

        $output = m::mock(OutputInterface::class);
        $output->shouldReceive('writeln')->with(
            '  ↳ Seed first <fg=gray>......................................................</> <fg=gray;options=bold>CONTINUE</>  ',
            32,
        );
        $output->shouldReceive('writeln')->with(
            '  ↳ Seed second <fg=gray>.........................................................</> <fg=green;options=bold>DONE</>  ',
            32,
        );
        $output->shouldReceive('writeln')->with(
            '  ↳ Third thing <fg=gray>.........................................................</> <fg=green;options=bold>DONE</>  ',
            32,
        );
        $output->shouldReceive('writeln')->with(
            '  ↳ Test name <fg=gray>...........................................................</> <fg=green;options=bold>DONE</>  ',
            32,
        );

        $command = m::mock(Command::class);
        $command->shouldReceive('getOutput')->andReturn($output);

        $seeder->setCommand($command);

        $connection = m::mock(ConnectionInterface::class);
        $connection->shouldReceive('getConnection->transaction')->andReturnUsing(fn ($callback) => $callback());

        $container = m::mock(Container::class);
        $container->shouldReceive('make')->with('db.connection')->andReturn($connection);
        $container->shouldReceive('call')->never()->with([$seeder, 'seedFirst'], []);
        $container->shouldReceive('call')->once()->with([$seeder, 'seedSecond'], []);
        $container->shouldReceive('call')->once()->with([$seeder, 'thirdThing'], []);
        $container->shouldReceive('call')->once()->with([$seeder, 'fourth'], []);
        $container->shouldReceive('call')->with([$seeder, 'runSeedTasks'], [])->andReturnUsing(function ($callable) {
            return $callable();
        });

        $seeder->setContainer($container);

        $seeder->__invoke();
    }

    public function testWithoutTransactions()
    {
        $seeder = new TestSeedTaskSeederWithoutTransactions();

        $output = m::mock(OutputInterface::class);
        $output->shouldReceive('writeln')->with(
            '  ↳ Seed first <fg=gray>..........................................................</> <fg=green;options=bold>DONE</>  ',
            32,
        );

        $command = m::mock(Command::class);
        $command->shouldReceive('getOutput')->andReturn($output);

        $seeder->setCommand($command);

        $connection = m::mock(ConnectionInterface::class);
        $connection->shouldReceive('getConnection->transaction')->never();

        $container = m::mock(Container::class);
        $container->shouldReceive('make')->with('db.connection')->andReturn($connection);
        $container->shouldReceive('call')->once()->with([$seeder, 'seedFirst'], []);
        $container->shouldReceive('call')->with([$seeder, 'runSeedTasks'], [])->andReturnUsing(fn ($callback) => $callback());

        $seeder->setContainer($container);

        $seeder->__invoke();
    }

    public function testSeedTaskSeederCallsFinally()
    {
        $seeder = new TestSeedTaskSeederRunsOnErrorMethod();

        $output = m::mock(OutputInterface::class);
        $output->shouldReceive('writeln')->with(
            '  Illuminate\Tests\Database\TestSeedTaskSeederRunsFinally <fg=gray>............</> <fg=yellow;options=bold>RUNNING</>  ',
            32,
        );
        $output->shouldReceive('writeln')->with(
            '  ⚠ Seed first <fg=gray>.........................................................</> <fg=red;options=bold>ERROR</>  ',
            32,
        );

        $command = m::mock(Command::class);
        $command->shouldReceive('getOutput')->andReturn($output);

        $seeder->setCommand($command);

        $connection = m::mock(ConnectionInterface::class);
        $connection->shouldReceive('getConnection->transaction')->andReturnUsing(fn ($callback) => $callback());

        $container = m::mock(Container::class);
        $container->shouldReceive('make')->with(TestSeedTaskSeederRunsOnErrorMethod::class)->andReturn($seeder);
        $container->shouldReceive('make')->with('db.connection')->andReturn($connection);
        $container->shouldReceive('call')->once()->with([$seeder, 'seedFirst'], [])->andReturnUsing(fn ($callback) => $callback());
        $container->shouldReceive('call')->with([$seeder, 'runSeedTasks'], [])->andReturnUsing(fn ($callback) => $callback());

        $seeder->setContainer($container);

        $class = new TestSeederCallsFailingSeedTask();
        $class->setContainer($container);
        $class->setCommand($command);

        $container->shouldReceive('call')->with([$class, 'run'], [])->andReturnUsing(fn ($callable) => $callable());

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('finally called');

        try {
            $class->__invoke();
        } catch (Throwable $e) {
            $this->assertSame('test error', $e->getPrevious()->getMessage());

            throw $e;
        }
    }
}
