<?php

namespace Illuminate\Tests\Foundation;

use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Benchmark\Factory;
use Illuminate\Foundation\Benchmark\Renderers\HtmlRenderer;
use PHPUnit\Framework\TestCase;

class FoundationHtmlBenchmarkTest extends TestCase
{
    /**
     * @var array<string, string|int>
     */
    protected $output;

    public function testMeasureFailsOnEmptyCallbacks()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('You must provide at least one callback.');

        $factory = $this->factory();

        $factory->measure([]);
    }

    public function testRepeats()
    {
        $factory = $this->factory();

        $factory->repeat(5)->measure([
            fn () => $myExpensiveCallA = 1 + 1,
            fn () => $myExpensiveCallB = 'foo',
        ]);

        $this->assertSame(5, $this->output['repeats']);
        $this->assertStringContainsString('ms', $this->output['[1] $myExpensiveCallA = 1 + 1']);
        $this->assertStringContainsString('ms', $this->output["[2] \$myExpensiveCallB = 'foo'"]);
        $this->assertCount(3, $this->output);
    }

    public function testMeasureUsesTenRepeatsByDefault()
    {
        $factory = $this->factory();

        $factory->measure([
            fn () => $myExpensiveCallA = 1 + 1,
            fn () => $myExpensiveCallB = 'foo',
        ]);

        $this->assertSame(10, $this->output['repeats']);
    }

    public function testMeasureDoesNotPrefixesNumberOfCallbackWhenUsingOneCallback()
    {
        $factory = $this->factory();

        $factory->measure(function () {
            $myExpensiveCallA = 1 + 1;
        });

        $this->assertStringContainsString('ms', $this->output['$myExpensiveCallA = 1 + 1;']);
    }

    public function testMeasurePrefixesNumberOfCallbackWhenUsingMultipleCallbacks()
    {
        $factory = $this->factory();

        $factory->measure([
            function () {
                $myExpensiveCallA = 1 + 1;
            },
            function () {
                $myExpensiveCallB = 2 + 2;
            }
        ]);

        $this->assertStringContainsString('ms', $this->output['[1] $myExpensiveCallA = 1 + 1;']);
        $this->assertStringContainsString('ms', $this->output['[2] $myExpensiveCallB = 2 + 2;']);
    }

    public function testMeasureAddsCodeDescriptionToCallbackByDefault()
    {
        $factory = $this->factory();

        $factory->measure([
            fn () => class_exists(User::class),
            fn () => class_exists(Team::class),
        ]);

        $this->assertStringContainsString('ms', $this->output['[1] class_exists(\App\Models\User::class)']);
        $this->assertStringContainsString('ms', $this->output['[2] class_exists(\App\Models\Team::class)']);
    }

    public function testMeasureAllowsToSetDescription()
    {
        $factory = $this->factory();

        $factory->measure([
            'user' => fn () => class_exists(User::class),
            fn () => class_exists(Team::class),
        ]);

        $this->assertStringContainsString('ms', $this->output['[1] user']);
        $this->assertStringContainsString('ms', $this->output['[2] class_exists(\App\Models\Team::class)']);
    }

    /**
     * @return \Illuminate\Foundation\Benchmark\Factory
     */
    protected function factory()
    {
        $this->output = [];

        HtmlRenderer::terminateUsing(fn () => null);
        HtmlRenderer::dumpUsing(fn ($results) => $this->output = $results);

        $renderer = new HtmlRenderer();

        return new Factory($renderer);
    }

    public function tearDown(): void
    {
        HtmlRenderer::terminateUsing(null);
        HtmlRenderer::dumpUsing(null);

        parent::tearDown();
    }
}
