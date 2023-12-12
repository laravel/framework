<?php

namespace Illuminate\Tests\Integration\Queue;

use Illuminate\Support\Env;

class DynamoBatchTestWithTTL extends DynamoBatchTest
{
    protected function defineEnvironment($app)
    {
        if (is_null($endpoint = Env::get('DYNAMODB_ENDPOINT'))) {
            $this->markTestSkipped('Require `dynamodb` to be configured');
        }

        $app['config']->set('queue.batching', [
            'driver' => 'dynamodb',
            'region' => 'us-west-2',
            'endpoint' => $endpoint,
            'key' => 'key',
            'secret' => 'secret',
            'ttl' => 1,
            'ttlAttribute' => 'ttl_value',
        ]);
    }
}
