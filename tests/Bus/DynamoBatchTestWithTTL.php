<?php

namespace Illuminate\Tests\Bus;

class DynamoBatchTestWithTTL extends DynamoBatchTest
{
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('queue.connections.sync1', [
            'driver' => 'sync',
        ]);

        $app['config']->set('queue.connections.sync2', [
            'driver' => 'sync',
        ]);

        $app['config']->set('queue.batching', [
            'driver' => 'dynamodb',
            'region' => 'us-west-2',
            'endpoint' => static::DYNAMO_ENDPOINT,
            'key' => 'key',
            'secret' => 'secret',
            'ttl' => 60,
            'ttlAttribute' => 'ttl_value',
        ]);
    }
}
