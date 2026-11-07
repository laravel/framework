<?php

namespace Illuminate\Tests\Cache;

use Aws\DynamoDb\DynamoDbClient;
use Illuminate\Cache\DynamoDbStore;
use PHPUnit\Framework\TestCase;

class CacheDynamoDbStoreTest extends TestCase
{
    public function testTouchMethodCorrectlyCallsDynamoDb(): void
    {
        $table = 'table';
        $key = 'key';
        $ttl = 60;

        $this->assertTrue((new DynamoDbStore($dynamo = new TestDynamo, $table))->touch($key, $ttl));

        $this->assertTrue(
            isset($dynamo->args['UpdateExpression'], $dynamo->args['TableName'], $dynamo->args['Key']['key']['S'])
                && $dynamo->args['TableName'] === $table
                && $dynamo->args['Key']['key']['S'] === $key
                && str_contains($dynamo->args['UpdateExpression'], 'SET')
        );

        $this->assertSame(
            $ttl, $dynamo->args['ExpressionAttributeValues'][':expiry']['N']
            - $dynamo->args['ExpressionAttributeValues'][':now']['N']
        );
    }

    public function testManyMethodPreservesNumericKeys(): void
    {
        $dynamo = new TestDynamo;
        $dynamo->batchGetItemResponse = ['Responses' => ['table' => [
            ['key' => ['S' => '10'], 'value' => ['S' => serialize('Taylor')]],
        ]]];

        $this->assertSame(
            [10 => 'Taylor', 20 => null],
            (new DynamoDbStore($dynamo, 'table'))->many([10, 20])
        );
    }
}

class TestDynamo extends DynamoDbClient
{
    public array $args;

    public array $batchGetItemResponse = [];

    public function __construct()
    {
    }

    public function updateItem(array $args): bool
    {
        $this->args = $args;

        return true;
    }

    public function batchGetItem(array $args): array
    {
        $this->args = $args;

        return $this->batchGetItemResponse;
    }
}
