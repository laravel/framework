<?php

namespace Illuminate\Queue;

class PayloadSerializerManager
{
    /**
     * @var \Illuminate\Foundation\Application
     */
    protected $app;

    /**
     * @param \Illuminate\Foundation\Application $app
     */
    public function __construct($app)
    {
        $this->app = $app;
    }

    /**
     * @return array
     */
    protected function getConfig()
    {
        return $this->app['config']["queue.serializers"] ?? [];
    }

    /**
     * @param string $connectionName
     * @return string
     */
    protected function getSerializerClass($connectionName)
    {
        $config = $this->getConfig();

        return $config[$connectionName] ?? PayloadSerializer::class;
    }

    /**
     * @param string $connectionName
     * @return PayloadSerializer
     */
    public function getSerializer($connectionName)
    {
        $class = $this->getSerializerClass($connectionName);

        return $this->app->make($class);
    }
}