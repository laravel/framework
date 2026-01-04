<?php

namespace Illuminate\Tests\Integration\Support\Fixtures;

use BackedEnum;
use Illuminate\Support\Manager;
use UnitEnum;

class EnumManager extends Manager
{
    protected BackedEnum|UnitEnum|null $defaultDriver = null;

    public function useAsDefault(BackedEnum|UnitEnum|null $default)
    {
        $this->defaultDriver = $default;

        return $this;
    }

    /**
     * Get the default driver name.
     *
     * @return string|null
     */
    public function getDefaultDriver()
    {
        return $this->defaultDriver;
    }

    public function createMysqlDriver()
    {
        return new class('@mysql')
        {
            public function __construct(public readonly string $name)
            {
            }
        };
    }

    public function createMariaDbDriver()
    {
        return new class('@mariadb')
        {
            public function __construct(public readonly string $name)
            {
            }
        };
    }
}
