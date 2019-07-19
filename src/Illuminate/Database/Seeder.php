<?php

namespace Illuminate\Database;

use Illuminate\Support\Arr;
use InvalidArgumentException;
use Illuminate\Console\Command;
use Illuminate\Support\Stopwatch;
use Illuminate\Container\Container;

abstract class Seeder
{
    /**
     * The container instance.
     *
     * @var \Illuminate\Container\Container
     */
    protected $container;

    /**
     * The console command instance.
     *
     * @var \Illuminate\Console\Command
     */
    protected $command;

    /**
     * Timer instance.
     *
     * @var \Illuminate\Support\Stopwatch
     */
    protected $watch;

    /**
     * Seed the given connection from the given path.
     *
     * @param  array|string  $class
     * @param  bool  $silent
     * @return $this
     */
    public function call($class, $silent = false)
    {
        $classes = Arr::wrap($class);

        foreach ($classes as $class) {
            $this->note("<comment>Seeding:</comment> $class", $silent);

            $this->watch->start($class);

            $this->resolve($class)->__invoke();

            $this->note("<info>Seeded:</info> $class ({$this->watch->check($class)} seconds)", $silent);
        }

        return $this;
    }

    /**
     * Silently seed the given connection from the given path.
     *
     * @param  array|string  $class
     * @return void
     */
    public function callSilent($class)
    {
        $this->call($class, true);
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

        if (isset($this->watch)) {
            $instance->setWatch($this->watch);
        }

        return $instance;
    }

    /**
     * Set the IoC container instance.
     *
     * @param  \Illuminate\Container\Container  $container
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

    public function setWatch(Stopwatch $watch)
    {
        $this->watch = $watch;

        return $this;
    }

    /**
     * Write a note to the console's output.
     *
     * @param  string  $message
     * @param  bool  $silent
     * @return void
     */
    protected function note($message, $silent = false)
    {
        if ($silent === false && isset($this->command)) {
            $this->command->line($message);
        }
    }

    /**
     * Run the database seeds.
     *
     * @return mixed
     *
     * @throws \InvalidArgumentException
     */
    public function __invoke()
    {
        if (! method_exists($this, 'run')) {
            throw new InvalidArgumentException('Method [run] missing from '.get_class($this));
        }

        return isset($this->container)
            ? $this->container->call([$this, 'run'])
            : $this->run();
    }
}
