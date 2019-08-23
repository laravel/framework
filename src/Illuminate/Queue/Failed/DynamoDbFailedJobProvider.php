<?php

namespace Illuminate\Queue\Failed;

use Illuminate\Support\Str;
use Aws\DynamoDb\DynamoDbClient;
use Illuminate\Support\Facades\Date;

class DynamoDbFailedJobProvider implements FailedJobProviderInterface
{
    /**
     * The DynamoDB client instance.
     *
     * @var \Aws\DynamoDb\DynamoDbClient
     */
    protected $dynamo;

    /**
     * The table name.
     *
     * @var string
     */
    protected $table;

    /**
     * Create a new DynamoDb failed job provider.
     *
     * @param  \Aws\DynamoDb\DynamoDbClient  $dynamo
     * @param  string  $table
     * @return void
     */
    public function __construct(DynamoDbClient $dynamo, $table)
    {
        $this->table = $table;
        $this->dynamo = $dynamo;
    }

    /**
     * Log a failed job into storage.
     *
     * @param  string  $connection
     * @param  string  $queue
     * @param  string  $payload
     * @param  \Exception  $exception
     * @return string|int|null
     */
    public function log($connection, $queue, $payload, $exception)
    {
        $id = (string) Str::orderedUuid();

        $this->dynamo->putItem([
            'TableName' => $this->table,
            'Item' => [
                'application' => ['S' => 'laravel'],
                'uuid' => ['S' => $id],
                'connection' => ['S' => $connection],
                'queue' => ['S' => $queue],
                'payload' => ['S' => $payload],
                'exception' => ['S' => (string) $exception],
                'failed_at' => ['N' => (string) Date::now()->getTimestamp()],
                'expires_at' => ['N' => (string) Date::now()->addDays(3)->getTimestamp()]
            ],
        ]);

        return $id;
    }

    /**
     * Get a list of all of the failed jobs.
     *
     * @return array
     */
    public function all()
    {
        $results = $this->dynamo->query([
            'TableName' => $this->table,
            'Select' => 'ALL_ATTRIBUTES',
            'KeyConditionExpression' => 'application = :application',
            'ExpressionAttributeValues' => [
                ':application' => ['S' => 'laravel'],
            ],
            'ScanIndexForward' => false,
        ]);

        dd($results);
    }

    /**
     * Get a single failed job.
     *
     * @param  mixed  $id
     * @return object|null
     */
    public function find($id)
    {
        $results = $this->dynamo->getItem([
            'TableName' => $this->table,
            'Key' => [
                'application' => ['S' => 'laravel'],
                'uuid' => ['S' => $id],
            ],
        ]);

        dd($results);
    }

    /**
     * Delete a single failed job from storage.
     *
     * @param  mixed  $id
     * @return bool
     */
    public function forget($id)
    {
        $results = $this->dynamo->deleteItem([
            'TableName' => $this->table,
            'Key' => [
                'application' => ['S' => 'laravel'],
                'uuid' => ['S' => $id],
            ],
        ]);
    }

    /**
     * Flush all of the failed jobs from storage.
     *
     * @return void
     */
    public function flush()
    {
        //
    }
}
