<?php

namespace Illuminate\Database;

use Closure;
use Illuminate\Console\Command;
use Illuminate\Console\View\Components\TwoColumnDetail;
use Illuminate\Contracts\Container\Container;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use InvalidArgumentException;
use ReflectionMethod;
use ReflectionObject;
use Throwable;

abstract class Seeder
{
    /**
     * The container instance.
     *
     * @var \Illuminate\Contracts\Container\Container
     */
    protected $container;

    /**
     * The console command instance.
     *
     * @var \Illuminate\Console\Command
     */
    protected $command;

    /**
     * Determine if the seeder should output to the console.
     *
     * @var bool
     */
    protected $silent = false;

    /**
     * Seeders that have been called at least one time.
     *
     * @var array
     */
    protected static $called = [];

    /**
     * Seeding registry to continue from if desired by the developer.
     *
     * @var array<class-string, string[]>
     */
    protected static $continue = [];

    /**
     * Run the database seeds.
     *
     * @param  array  $parameters
     * @return mixed
     *
     * @throws \InvalidArgumentException
     */
    public function __invoke(array $parameters = [])
    {
        $method = match (true) {
            method_exists($this, 'run') => 'run',
            method_exists($this, 'runSeedTasks') => 'runSeedTasks',
            default => throw new InvalidArgumentException('Method [run] missing from '.get_class($this))
        };

        $callback = fn () => isset($this->container)
            ? $this->container->call([$this, $method], $parameters)
            : $this->{$method}(...$parameters);

        $uses = array_flip(class_uses_recursive(static::class));

        if (isset($uses[WithoutModelEvents::class])) {
            $callback = $this->withoutModelEvents($callback);
        }

        return $callback();
    }

    /**
     * Determine if the Seeder should wrap each seed task into a database transaction.
     *
     * @return bool
     */
    public function useTransactions()
    {
        return true;
    }

    /**
     * Skips the current seed task. Skips the entire seeder if invoked inside the "before" method.
     *
     * @param  string  $reason
     * @return never
     */
    protected function skip($reason = '')
    {
        throw new SeederSkipped($reason);
    }

    /**
     * Run the given seeder class.
     *
     * @param  array|string  $class
     * @param  bool  $silent
     * @param  array  $parameters
     * @return $this
     */
    public function call($class, $silent = false, array $parameters = [])
    {
        $classes = Arr::wrap($class);

        foreach ($classes as $class) {
            $seeder = $this->resolve($class);

            $name = get_class($seeder);

            if ($silent === false && isset($this->command)) {
                with(new TwoColumnDetail($this->command->getOutput()))->render(
                    $name,
                    '<fg=yellow;options=bold>RUNNING</>'
                );
            }

            $seeder->setSilent($silent);

            $startTime = microtime(true);

            try {
                $seeder->__invoke($parameters);
            } catch (SeederSkipped $e) {
                if ($silent === false && isset($this->command)) {
                    with(new TwoColumnDetail($this->command->getOutput()))->render(
                        $name,
                        '<fg=cyan;options=bold>SKIPPED</>'
                    );

                    if ($e->getMessage()) {
                        with(new TwoColumnDetail($this->command->getOutput()))->render('🛈 '.$e->getMessage());
                    }
                }

                static::$called[] = $class;

                return $this;
            } catch (Throwable $e) {
                if (method_exists($seeder, 'onError')) {
                    $seeder->onError($e);
                }

                throw $e;
            }

            if ($silent === false && isset($this->command)) {
                $runTime = number_format((microtime(true) - $startTime) * 1000);

                with(new TwoColumnDetail($this->command->getOutput()))->render(
                    $name,
                    "<fg=gray>$runTime ms</> <fg=green;options=bold>DONE</>"
                );

                $this->command->getOutput()->writeln('');
            }

            static::$called[] = $class;
        }

        return $this;
    }

    /**
     * Run the given seeder class.
     *
     * @param  array|string  $class
     * @param  array  $parameters
     * @return void
     */
    public function callWith($class, array $parameters = [])
    {
        $this->call($class, false, $parameters);
    }

    /**
     * Silently run the given seeder class.
     *
     * @param  array|string  $class
     * @param  array  $parameters
     * @return void
     */
    public function callSilent($class, array $parameters = [])
    {
        $this->call($class, true, $parameters);
    }

    /**
     * Run the given seeder class once.
     *
     * @param  array|string  $class
     * @param  bool  $silent
     * @return void
     */
    public function callOnce($class, $silent = false, array $parameters = [])
    {
        $classes = Arr::wrap($class);

        foreach ($classes as $class) {
            if (in_array($class, static::$called)) {
                continue;
            }

            $this->call($class, $silent, $parameters);
        }
    }

    /**
     * Execute each seeder's seed tasks.
     *
     * @return void
     */
    public function runSeedTasks()
    {
        if (method_exists($this, 'before')) {
            $this->container->call([$this, 'before']);
        }

        $parameters = func_get_args();

        Collection::make((new ReflectionObject($this))->getMethods())
            ->filter(function (ReflectionMethod $method) {
                return Str::startsWith($method->name, 'seed')
                    || ! empty($method->getAttributes(Attributes\SeedTask::class));
            })
            ->map(fn (ReflectionMethod $method) => function () use ($method, $parameters) {
                $name = $method->getAttributes(Attributes\SeedTask::class)[0]?->newInstance()->as
                    ?: Str::ucfirst(Str::snake($method->name, ' '));

                // If the developer has specified to continue the seeding operation, the seed tasks
                // that have already been run will be set to true in the static::$continue array.
                // We'll check if this seed task ran, and skip it if it's present in this list.
                if ($this->hasSeedTaskRan($method->name)) {
                    $this->printTwoColumns("↳ $name", '<fg=gray;options=bold>CONTINUE</>');

                    return;
                }

                try {
                    $result = $this->container->call([$this, $method->name], $parameters[$method->name] ?? []);
                } catch (SeederSkipped $e) {
                    $this->printTwoColumns("↳ $name", '<fg=blue;options=bold>SKIPPED</>');

                    if ($e->getMessage()) {
                        $this->printTwoColumns("🛈 {$e->getMessage()}");
                    }

                    self::$continue[get_class($this)][$method->name] = true;

                    return;
                } catch (Throwable $e) {
                    $this->printTwoColumns("⚠ $name", '<fg=red;options=bold>ERROR</>');

                    throw $e;
                }

                if ($result instanceof Eloquent\Factories\Factory) {
                    $result->create();
                } elseif ($result instanceof Eloquent\Collection) {
                    $result->each->push();
                } elseif ($result instanceof Eloquent\Model) {
                    $result->push();
                }

                $this->printTwoColumns("↳ $name", '<fg=green;options=bold>DONE</>');

                self::$continue[get_class($this)][$method->name] = true;
            })
            ->when($this->useTransactions())->map(function (Closure $callback): Closure {
                return function () use ($callback): void {
                    $this->container->make('db.connection')->getConnection()->transaction($callback);
                };
            })
            ->each(fn (Closure $callback) => $callback());

        if (method_exists($this, 'after')) {
            $this->container->call([$this, 'after']);
        }
    }

    /**
     * Resolve an instance of the given seeder class.
     *
     * @param  string  $class
     * @return \Illuminate\Database\Seeder
     */
    protected function resolve($class)
    {
        if (isset($this->container)) {
            $instance = $this->container->make($class);

            $instance->setContainer($this->container);
        } else {
            $instance = new $class;
        }

        if (isset($this->command)) {
            $instance->setCommand($this->command);
        }

        return $instance;
    }

    /**
     * Set the IoC container instance.
     *
     * @param  \Illuminate\Contracts\Container\Container  $container
     * @return $this
     */
    public function setContainer(Container $container)
    {
        $this->container = $container;

        return $this;
    }

    /**
     * Set the console command instance.
     *
     * @param  \Illuminate\Console\Command  $command
     * @return $this
     */
    public function setCommand(Command $command)
    {
        $this->command = $command;

        return $this;
    }

    /**
     * Returns the list of database seeders and seed tasks called.
     *
     * @return array<class-string, string[]>
     */
    public static function getContinue()
    {
        return self::$continue;
    }

    /**
     * Sets the list of database seeders that have been called.
     *
     * @param  array<class-string, string[]>  $tasksCalled
     * @return void
     */
    public static function setContinue(array $tasksCalled)
    {
        self::$continue = $tasksCalled;
    }

    /**
     * Send a two-column detail to the console output.
     *
     * @param  string  $first
     * @param  string|null  $second
     * @return void
     */
    protected function printTwoColumns($first, $second = null)
    {
        if ($this->silent || ! $this->command) {
            return;
        }

        (new TwoColumnDetail($this->command->getOutput()))->render($first, $second);
    }

    /**
     * Makes the seeder not output to the console.
     *
     * @param  bool  $silent
     * @return void
     */
    public function setSilent($silent)
    {
        $this->silent = $silent || ! isset($this->command);
    }

    /**
     * Check if the seed task has been run before.
     *
     * @param  string  $name
     * @return bool
     */
    protected function hasSeedTaskRan($name)
    {
        return isset(self::$continue[get_class($this)][$name]);
    }
}
