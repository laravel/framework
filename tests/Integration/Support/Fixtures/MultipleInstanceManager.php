<?php

namespace Illuminate\Tests\Integration\Support\Fixtures;

use Illuminate\Support\MultipleInstanceManager as BaseMultipleInstanceManager;
use Illuminate\Tests\Integration\Support\Fixtures\Enums\Bar;
use Illuminate\Tests\Integration\Support\Fixtures\Enums\Foo;

class MultipleInstanceManager extends BaseMultipleInstanceManager
{
    protected $defaultInstance = 'foo';

    protected function createFooDriver(array $config)
    {
        return DummyInstanceFactory::withConfig($config);
    }

    protected function createBarDriver(array $config)
    {
        return DummyInstanceFactory::withConfig($config);
    }

    protected function createMysqlDatabaseConnectionDriver(array $config)
    {
        return DummyInstanceFactory::withConfig($config);
    }

    protected function createMysqlDriver(array $config)
    {
        return DummyInstanceFactory::withConfig($config);
    }

    /**
     * Get the default instance name.
     *
     * @return string
     */
    public function getDefaultInstance()
    {
        return $this->defaultInstance;
    }

    /**
     * Set the default instance name.
     *
     * @param  string  $name
     * @return void
     */
    public function setDefaultInstance($name)
    {
        $this->defaultInstance = $name;
    }

    /**
     * Get the instance specific configuration.
     *
     * @param  string  $name
     * @return array
     */
    public function getInstanceConfig($name)
    {
        return match ($name) {
            'foo' => [
                'driver' => 'foo',
                'foo-option' => 'option-value',
            ],
            'bar' => [
                'driver' => 'bar',
                'bar-option' => 'option-value',
            ],
            'mysql_database-connection' => [
                'driver' => 'mysql_database-connection',
                'mysql_database-connection-option' => 'option-value',
            ],
            Bar::MonitoringDb => [
                'driver' => Foo::MySql,
                'database_name' => 'monitoring',
            ],

            default => [],
        };
    }
}
