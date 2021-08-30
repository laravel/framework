<?php

namespace Illuminate\Console;

use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\CommandLoader\CommandLoaderInterface;
use Symfony\Component\Console\Exception\CommandNotFoundException;

class ContainerCommandLoader implements CommandLoaderInterface
{
    /**
     * The container instance.
     *
     * @var \Psr\Container\ContainerInterface
     */
    protected $container;

    /**
     * A list of class names.
     *
     * @var array
     */
    protected $classes = [];

    /**
     * Create a new command loader instance.
     *
     * @param  \Psr\Container\ContainerInterface  $container
     * @return void
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * Add class to the loader.
     *
     * @param string $name
     * @return void
     */
    public function add(string $name)
    {
        $this->classes[] = $name;
    }

    /**
     * Resolve a command from the container.
     *
     * @param  string  $name
     * @return \Symfony\Component\Console\Command\Command
     *
     * @throws \Symfony\Component\Console\Exception\CommandNotFoundException
     */
    public function get(string $name): Command
    {
        if (! $this->has($name)) {
            throw new CommandNotFoundException(sprintf('Command "%s" does not exist.', $name));
        }

        return $this->container->get($name);
    }

    /**
     * Determines if a command exists.
     *
     * @param  string  $name
     * @return bool
     */
    public function has(string $name): bool
    {
        return in_array($name, $this->classes);
    }

    /**
     * Get the command names.
     *
     * @return string[]
     */
    public function getNames(): array
    {
        $names = [];

        foreach ($this->classes as $class) {
            $names[] = $class::getDefaultName();
        }

        return $names;
    }
}
