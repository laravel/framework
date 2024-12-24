<?php

namespace Illuminate\Support;

use Closure;

class Benchmark
{
    /**
     * The tasks to be measured by the benchmark.
     *
     * @var array<string, float|float[]>
     */
    protected static array $tasks = [];

    /**
     * Start measuring the time for the given task.
     *
     * @param  string  $task
     * @return void
     */
    public static function start(string $task): void
    {
        static::$tasks[$task]['start'] = hrtime(true);
    }

    /**
     * Tags a task with a given tag. If the tag already exists
     * the time will be computed against that previous tag.
     *
     * @param  string  $task
     * @param  ?string  $tag
     * @return float
     */
    public static function tag(string $task, ?string $tag = null): float
    {
        $tag ??= sprintf('tag.%s', count(static::$tasks[$task]));

        return static::$tasks[$task][$tag] = static::computeTime(static::$tasks[$task]['start']);
    }

    /**
     * Alias for `getTask`.
     *
     * @param  string  $task
     * @return array<string, float>
     */
    public static function getTags(string $task): array
    {
        return static::getTask($task);
    }

    /**
     * Get the tags for the given task.
     *
     * @param  string  $task
     * @return array<string, float>
     */
    public static function getTask(string $task): array
    {
        return static::$tasks[$task];
    }

    /**
     * Get the time in milliseconds since the given task started.
     * Additionally, a tag can be provided to divide the task.
     *
     * @param  string  $task
     * @param  ?string  $tag
     * @param  bool  $deleteTask
     * @return array<string, float>|float
     */
    public static function task(string $task, ?string $tag = null, bool $deleteTask = false): array|float
    {
        $tags = static::$tasks[$task];

        if ($deleteTask) {
            static::reset($task);
        }

        return $tag ? $tags[$tag] : $tags;
    }

    /**
     * Stop measuring the time for the given task.
     *
     * @param  string  $task
     * @return array<string, float>
     */
    public static function stop(string $task): array
    {
        static::$tasks[$task]['stop'] = hrtime(true);
        $tags = static::$tasks[$task];
        static::reset($task);

        $tags['total'] = ($tags['stop'] - $tags['start']) / 1000000;
        unset($tags['start'], $tags['stop']);

        return $tags;
    }

    /**
     * Compute the time in milliseconds between two tags.
     *
     * @param  string  $task
     * @param  string  $start
     * @param  string  $end
     * @return float
     */
    public static function computeTags(string $task, string $start, string $end): float
    {
        $start = static::$tasks[$task][$start] ?? throw new \InvalidArgumentException(
            sprintf('The start tag "%s" does not exist.', $start)
        );
        $end = static::$tasks[$task][$end] ?? throw new \InvalidArgumentException(
            sprintf('The end tag "%s" does not exist.', $end)
        );

        return ($end - $start) / 1000000;
    }

    /**
     * Reset the tasks.
     *
     * @param  ?string  $task
     * @return void
     */
    public static function reset(?string $task = null): void
    {
        if ($task) {
            unset(static::$tasks[$task]);
            return;
        }

        static::$tasks = [];
    }

    /**
     * Compute the time in milliseconds since the given time.
     *
     * @param  float  $time
     * @return float
     */
    protected static function computeTime(float $time): float
    {
        return (hrtime(true) - $time) / 1000000;
    }

    /**
     * Measure a callable or array of callables over the given number of iterations.
     *
     * @param  \Closure|array  $benchmarkables
     * @param  int  $iterations
     * @return array|float
     */
    public static function measure(Closure|array $benchmarkables, int $iterations = 1): array|float
    {
        return Collection::wrap($benchmarkables)->map(function ($callback) use ($iterations) {
            return Collection::range(1, $iterations)->map(function () use ($callback) {
                gc_collect_cycles();

                $start = hrtime(true);

                $callback();

                return (hrtime(true) - $start) / 1000000;
            })->average();
        })->when(
            $benchmarkables instanceof Closure,
            fn ($c) => $c->first(),
            fn ($c) => $c->all(),
        );
    }

    /**
     * Measure a callable once and return the duration and result.
     *
     * @template TReturn of mixed
     *
     * @param  (callable(): TReturn)  $callback
     * @return array{0: TReturn, 1: float}
     */
    public static function value(callable $callback): array
    {
        gc_collect_cycles();

        $start = hrtime(true);

        $result = $callback();

        return [$result, (hrtime(true) - $start) / 1000000];
    }

    /**
     * Measure a callable or array of callables over the given number of iterations, then dump and die.
     *
     * @param  \Closure|array  $benchmarkables
     * @param  int  $iterations
     * @return never
     */
    public static function dd(Closure|array $benchmarkables, int $iterations = 1): void
    {
        $result = (new Collection(static::measure(Arr::wrap($benchmarkables), $iterations)))
            ->map(fn ($average) => number_format($average, 3).'ms')
            ->when($benchmarkables instanceof Closure, fn ($c) => $c->first(), fn ($c) => $c->all());

        dd($result);
    }
}
