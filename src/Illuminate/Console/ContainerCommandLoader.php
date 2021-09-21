<?php

namespace Illuminate\Console;

use InvalidArgumentException;
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
     * A map of command names to classes.
     *
     * @var array
     */
    protected $commandMap = [];

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
     * Merge the command map list
     * 
     * @param array $commandMap
     * @return void
     */
    public function merge(array $commandMap)
    {
        $this->commandMap = array_merge($this->commandMap, $commandMap);
    }

    /**
     * Determine if the command is accepted by the cmand loader.
     *
     * @param string $name
     * @return bool
     */
    public function accepts(string $name): bool
    {
        if (in_array($name, $this->commandMap)) {
            return true;
        }

        return class_exists($name) && ! is_null($name::getDefaultName());
    }

    /**
     * Add class to the loader.
     *
     * @param string $name
     * @return void
     *
     * @throws \InvalidArgumentException
     */
    public function add(string $name)
    {
        if (in_array($name, $this->commandMap)) {
            return;
        }

        if (! $this->accepts($name)) {
            throw new InvalidArgumentException(sprintf('Command "%s" was not accepted by the command loader.', $name));
        }

        $this->commandMap[$name::getDefaultName()] = $name;
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

        return $this->container->get($this->commandMap[$name]);
    }

    /**
     * Determines if a command exists.
     *
     * @param  string  $name
     * @return bool
     */
    public function has(string $name): bool
    {
        return isset($this->commandMap[$name]);
    }

    /**
     * Get the command names.
     *
     * @return string[]
     */
    public function getNames(): array
    {
        return array_keys($this->commandMap);
    }

    /**
     * Get the full list of commands.
     *
     * @return array
     */
    public function all(): array
    {
        return $this->commandMap;
    }
}
