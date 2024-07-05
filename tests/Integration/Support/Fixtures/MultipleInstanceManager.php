<?php

namespace Illuminate\Tests\Integration\Support\Fixtures;

use Illuminate\Support\MultipleInstanceManager as BaseMultipleInstanceManager;

class MultipleInstanceManager extends BaseMultipleInstanceManager
{
    protected $defaultInstance = 'foo';

    protected function createFooDriver(array $config)
    {
        return new class($config)
        {
            public $config;

            public function __construct($config)
            {
                $this->config = $config;
            }
        };
    }

    protected function createBarDriver(array $config)
    {
        return new class($config)
        {
            public $config;

            public function __construct($config)
            {
                $this->config = $config;
            }
        };
    }

    protected function createMysqlDatabaseConnectionDriver(array $config)
    {
        return new class($config)
        {
            public function __construct(public $config)
            {
            }
        };
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
        switch ($name) {
            case 'foo':
                return [
                    'driver' => 'foo',
                    'foo-option' => 'option-value',
                ];
            case 'bar':
                return [
                    'driver' => 'bar',
                    'bar-option' => 'option-value',
                ];
            case 'mysql_database-connection':
                return [
                    'driver' => 'mysql_database-connection',
                    'mysql_database-connection-option' => 'option-value',
                ];
            default:
                return [];
        }
    }
}
