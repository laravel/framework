<?php

namespace Illuminate\Tests\Integration\Cache;

use Aws\DynamoDb\DynamoDbClient;
use Aws\Exception\AwsException;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Orchestra\Testbench\Attributes\RequiresEnv;
use Orchestra\Testbench\TestCase;

#[RequiresEnv('DYNAMODB_CACHE_TABLE')]
class DynamoDbStoreTest extends TestCase
{
    public function testItemsCanBeStoredAndRetrieved()
    {
        Cache::driver('dynamodb')->put('name', 'Taylor', 10);
        $this->assertSame('Taylor', Cache::driver('dynamodb')->get('name'));

        Cache::driver('dynamodb')->put(['name' => 'Abigail', 'age' => 28], 10);
        $this->assertSame('Abigail', Cache::driver('dynamodb')->get('name'));
        $this->assertEquals(28, Cache::driver('dynamodb')->get('age'));

        $this->assertEquals([
            'name' => 'Abigail',
            'age' => 28,
            'height' => null,
        ], Cache::driver('dynamodb')->many(['name', 'age', 'height']));

        Cache::driver('dynamodb')->forget('name');
        $this->assertNull(Cache::driver('dynamodb')->get('name'));
    }

    public function testItemsCanBeAtomicallyAdded()
    {
        $key = Str::random(6);

        $this->assertTrue(Cache::driver('dynamodb')->add($key, 'Taylor', 10));
        $this->assertFalse(Cache::driver('dynamodb')->add($key, 'Taylor', 10));
    }

    public function testItemsCanBeIncrementedAndDecremented()
    {
        Cache::driver('dynamodb')->put('counter', 0, 10);
        Cache::driver('dynamodb')->increment('counter');
        Cache::driver('dynamodb')->increment('counter', 4);

        $this->assertEquals(5, Cache::driver('dynamodb')->get('counter'));

        Cache::driver('dynamodb')->decrement('counter', 5);
        $this->assertEquals(0, Cache::driver('dynamodb')->get('counter'));
    }

    public function testLocksCanBeAcquired()
    {
        Cache::driver('dynamodb')->lock('lock', 10)->get(function () {
            $this->assertFalse(Cache::driver('dynamodb')->lock('lock', 10)->get());
        });
    }

    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    protected function defineEnvironment($app)
    {
        if (! env('DYNAMODB_CACHE_TABLE')) {
            $this->markTestSkipped('DynamoDB not configured.');
        }

        $app['config']->set('cache.default', 'dynamodb');

        $config = $app['config']->get('cache.stores.dynamodb');

        /** @var \Aws\DynamoDb\DynamoDbClient $client */
        $client = $app->make(Repository::class)->getStore()->getClient();

        if ($this->dynamoTableExists($client, $config['table'])) {
            return;
        }

        $client->createTable([
            'TableName' => $config['table'],
            'KeySchema' => [
                [
                    'AttributeName' => $config['attributes']['key'] ?? 'key',
                    'KeyType' => 'HASH',
                ],
            ],
            'AttributeDefinitions' => [
                [
                    'AttributeName' => $config['attributes']['key'] ?? 'key',
                    'AttributeType' => 'S',
                ],
            ],
            'ProvisionedThroughput' => [
                'ReadCapacityUnits' => 1,
                'WriteCapacityUnits' => 1,
            ],
        ]);
    }

    /**
     * Determine if the given DynamoDB table exists.
     *
     * @param  \Aws\DynamoDb\DynamoDbClient  $client
     * @param  string  $table
     * @return bool
     */
    public function dynamoTableExists(DynamoDbClient $client, $table)
    {
        try {
            $client->describeTable([
                'TableName' => $table,
            ]);

            return true;
        } catch (AwsException $e) {
            if (Str::contains($e->getAwsErrorMessage(), ['resource not found', 'Cannot do operations on a non-existent table'])) {
                return false;
            }

            throw $e;
        }
    }
}
