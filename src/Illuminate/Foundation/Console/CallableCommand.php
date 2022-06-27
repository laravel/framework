<?php

namespace Illuminate\Foundation\Console;

use Closure;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Laravel\SerializableClosure\SerializableClosure;
use ReflectionMethod;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CallableCommand extends Command
{
    /**
     * The command callback.
     *
     * @var callable
     */
    protected $callback;

    /**
     * The callable object.
     */
    protected $callableInstance;

    /**
     * Create a new command instance.
     *
     * @param  string  $signature
     * @param  callable  $callback
     * @return void
     */
    public function __construct($signature, $callable)
    {
        $this->callback = is_array($callable)
            ? [$callable[0] ?? null, $callable[1] ?? '__invoke']
            : Str::parseCallback($callable, '__invoke');

        $this->signature = $signature;

        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @param  \Symfony\Component\Console\Input\InputInterface  $input
     * @param  \Symfony\Component\Console\Output\OutputInterface  $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $inputs = array_merge($input->getArguments(), $input->getOptions());

        $parameters = [];

        [$object, $method] = $this->callback;

        foreach ((new ReflectionMethod($object, $method))->getParameters() as $parameter) {
            if (isset($inputs[$parameter->getName()])) {
                $parameters[$parameter->getName()] = $inputs[$parameter->getName()];
            }
        }

        $this->callableInstance = is_string($object)
            ? $this->laravel->make($object)
            : $object;

        $closure = unserialize(serialize(new SerializableClosure(
            Closure::fromCallable([$this->callableInstance, $method])
        )))->getClosure();

        return $this->laravel->call(
            $closure->bindTo($this, $this->callableInstance), $parameters
        );
    }

    /**
     * Set the description for the command.
     *
     * @param  string  $description
     * @return $this
     */
    public function purpose($description)
    {
        return $this->describe($description);
    }

    /**
     * Set the description for the command.
     *
     * @param  string  $description
     * @return $this
     */
    public function describe($description)
    {
        $this->setDescription($description);

        return $this;
    }

    /**
     * Forward property access to underlying callable.
     *
     * @param  string  $key
     * @return mixed
     */
    public function __get($key)
    {
        return $this->callableInstance->$key;
    }

    /**
     * Dynamically set properties on the underlying callable.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return void
     */
    public function __set($key, $value)
    {
        $this->callableInstance->$key = $value;
    }

    /**
     * Handle dynamic method calls into the underlying callable.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->callableInstance->$method(...$parameters);
    }
}
