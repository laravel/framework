<?php

namespace Illuminate\Tests\Integration\Support\Fixtures;

use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Manager as BaseManager;

class Manager extends BaseManager
{
    public static function makeWithDefaultDriver(Container $container, ?\UnitEnum $default): self
    {
        return (new self($container))
            ->useAsDefault($default);
    }

    protected ?\UnitEnum $defaultDriver = null;

    protected function useAsDefault(?\UnitEnum $default)
    {
        $this->defaultDriver = $default;

        return $this;
    }

    public function getDefaultDriver()
    {
        return $this->defaultDriver;
    }

    public function createMysqlDriver()
    {
        return DummyInstanceFactory::withName('@mysql');
    }

    public function createMariaDbDriver()
    {
        return DummyInstanceFactory::withName('@mariadb');
    }
}
