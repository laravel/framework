<?php

namespace Illuminate\Tests\Console\Concerns;

use Generator;
use Illuminate\Console\Command;
use Illuminate\Console\Concerns\InteractsWithIO;
use Illuminate\Console\OutputStyle;
use Mockery as m;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\BufferedOutput;

class InteractsWithIOTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    #[DataProvider('iterableDataProvider')]
    public function testWithProgressBarIterable($iterable)
    {
        $command = new CommandInteractsWithIO;
        $bufferedOutput = new BufferedOutput();
        $output = m::mock(OutputStyle::class, [new ArgvInput(), $bufferedOutput])->makePartial();
        $command->setOutput($output);

        $output->shouldReceive('createProgressBar')
            ->once()
            ->with(count($iterable))
            ->andReturnUsing(function ($steps) use ($bufferedOutput) {
                // we can't mock ProgressBar because it's final, so return a real one
                return new ProgressBar($bufferedOutput, $steps);
            });

        $calledTimes = 0;
        $result = $command->withProgressBar($iterable, function ($value, $bar, $key) use (&$calledTimes, $iterable) {
            $this->assertInstanceOf(ProgressBar::class, $bar);
            $this->assertSame(array_values($iterable)[$calledTimes], $value);
            $this->assertSame(array_keys($iterable)[$calledTimes], $key);
            $calledTimes++;
        });

        $this->assertSame(count($iterable), $calledTimes);
        $this->assertSame($iterable, $result);
    }

    public static function iterableDataProvider(): Generator
    {
        yield [['a', 'b', 'c']];

        yield [['foo' => 'a', 'bar' => 'b', 'baz' => 'c']];
    }

    public function testWithProgressBarInteger()
    {
        $command = new CommandInteractsWithIO;
        $bufferedOutput = new BufferedOutput();
        $output = m::mock(OutputStyle::class, [new ArgvInput(), $bufferedOutput])->makePartial();
        $command->setOutput($output);

        $totalSteps = 5;

        $output->shouldReceive('createProgressBar')
            ->once()
            ->with($totalSteps)
            ->andReturnUsing(function ($steps) use ($bufferedOutput) {
                // we can't mock ProgressBar because it's final, so return a real one
                return new ProgressBar($bufferedOutput, $steps);
            });

        $called = false;
        $command->withProgressBar($totalSteps, function ($bar) use (&$called) {
            $this->assertInstanceOf(ProgressBar::class, $bar);
            $called = true;
        });

        $this->assertTrue($called);
    }
}

class CommandInteractsWithIO extends Command
{
    use InteractsWithIO;
}
