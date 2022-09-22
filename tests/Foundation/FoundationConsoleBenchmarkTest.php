<?php

namespace Illuminate\Tests\Foundation;

use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Benchmark\Factory;
use Illuminate\Foundation\Benchmark\Renderers\ConsoleRenderer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\BufferedOutput;

class FoundationConsoleBenchmarkTest extends TestCase
{
    public function testMeasureFailsOnEmptyCallbacks()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('You must provide at least one callback.');

        [$factory] = $this->factory();

        $factory->measure([]);
    }

    public function testRepeats()
    {
        [$factory, $output] = $this->factory();

        $factory->repeat(5)->measure([
            fn () => $myExpensiveCallA = 1 + 1,
            fn () => $myExpensiveCallB = 'foo',
        ]);

        $buffer = $output->fetch();

        $this->assertStringContainsString('INFO  Benchmarking [2] script(s) using [5] repetitions.', $buffer);
    }

    public function testMeasureUsesTenRepeatsByDefault()
    {
        [$factory, $output] = $this->factory();

        $factory->measure([
            fn () => $myExpensiveCallA = 1 + 1,
            fn () => $myExpensiveCallB = 'foo',
        ]);

        $buffer = $output->fetch();

        $this->assertStringContainsString('INFO  Benchmarking [2] script(s) using [10] repetitions.', $buffer);
    }

    public function testMeasureDoesNotPrefixesNumberOfCallbackWhenUsingOneCallback()
    {
        [$factory, $output] = $this->factory();

        $factory->measure(function () {
            $myExpensiveCallA = 1 + 1;
        });

        $buffer = $output->fetch();

        $this->assertStringContainsString("  \$myExpensiveCallA = 1 + 1; ...", $buffer);
    }

    public function testMeasurePrefixesNumberOfCallbackWhenUsingMultipleCallbacks()
    {
        [$factory, $output] = $this->factory();

        $factory->measure([function () {
            $myExpensiveCallA = 1 + 1;
        }, function () {
            $myExpensiveCallB = 2 + 2;
        }]);

        $buffer = $output->fetch();

        $this->assertStringContainsString("  [1] \$myExpensiveCallA = 1 + 1; ...", $buffer);
        $this->assertStringContainsString("  [2] \$myExpensiveCallB = 2 + 2; ...", $buffer);
    }

    public function testMeasureAddsCodeDescriptionToCallbackByDefault()
    {
        [$factory, $output] = $this->factory();

        $factory->measure([
            fn () => class_exists(User::class),
            fn () => class_exists(Team::class),
        ]);

        $buffer = $output->fetch();

        $this->assertStringContainsString('[1] class_exists(\App\Models\User::class) ...', $buffer);
        $this->assertStringContainsString('[2] class_exists(\App\Models\Team::class) ...', $buffer);
    }

    public function testMeasureAllowsToSetDescription()
    {
        [$factory, $output] = $this->factory();

        $factory->measure([
            'user' => fn () => class_exists(User::class),
            fn () => class_exists(Team::class),
        ]);

        $buffer = $output->fetch();

        $this->assertStringContainsString('[1] user ...', $buffer);
        $this->assertStringContainsString('[2] class_exists(\App\Models\Team::class) ...', $buffer);
    }

    public function testMeasureDisplaysAverageInMilliseconds()
    {
        [$factory, $output] = $this->factory();

        $factory->measure(function () {
            $myExpensiveCall = 1 + 1;
        });

        $buffer = $output->fetch();

        $this->assertStringEndsWith(sprintf('ms  %s%s', PHP_EOL, PHP_EOL), $buffer);
    }

    /**
     * @return array{0: \Illuminate\Foundation\Benchmark\Factory, 1: \Symfony\Component\Console\Output\BufferedOutput}
     */
    protected function factory()
    {
        ConsoleRenderer::terminateUsing(fn () => null);

        $output = new BufferedOutput();
        $renderer = new ConsoleRenderer();

        $renderer->setOutput($output);
        $factory = new Factory($renderer);

        return [$factory, $output];
    }

    public function tearDown(): void
    {
        ConsoleRenderer::terminateUsing(null);

        parent::tearDown();
    }
}
