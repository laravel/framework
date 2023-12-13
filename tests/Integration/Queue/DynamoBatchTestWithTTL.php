<?php

namespace Illuminate\Tests\Integration\Queue;

use Illuminate\Support\Env;
use Orchestra\Testbench\Attributes\RequiresEnv;
use PHPUnit\Framework\Attributes\RequiresOperatingSystem;

#[RequiresOperatingSystem('Linux|Darwin')]
#[RequiresEnv('DYNAMODB_ENDPOINT')]
class DynamoBatchTestWithTTL extends DynamoBatchTest
{
    protected function defineEnvironment($app)
    {
        $app['config']->set('queue.batching', [
            'driver' => 'dynamodb',
            'region' => 'us-west-2',
            'endpoint' => Env::get('DYNAMODB_ENDPOINT'),
            'key' => 'key',
            'secret' => 'secret',
            'ttl' => 1,
            'ttlAttribute' => 'ttl_value',
        ]);
    }
}
