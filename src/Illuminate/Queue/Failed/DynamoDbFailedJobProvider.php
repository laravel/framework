<?php

namespace Illuminate\Queue\Failed;

use Aws\DynamoDb\DynamoDbClient;
use DateTimeInterface;
use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Str;

class DynamoDbFailedJobProvider implements FailedJobProviderInterface
{
    /**
     * The DynamoDB client instance.
     *
     * @var \Aws\DynamoDb\DynamoDbClient
     */
    protected $dynamo;

    /**
     * The application name.
     *
     * @var string
     */
    protected $applicationName;

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
     * @param  string  $applicationName
     * @param  string  $table
     * @return void
     */
    public function __construct(DynamoDbClient $dynamo, $applicationName, $table)
    {
        $this->table = $table;
        $this->dynamo = $dynamo;
        $this->applicationName = $applicationName;
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

        $failedAt = Date::now();

        $this->dynamo->putItem([
            'TableName' => $this->table,
            'Item' => [
                'application' => ['S' => $this->applicationName],
                'uuid' => ['S' => $id],
                'connection' => ['S' => $connection],
                'queue' => ['S' => $queue],
                'payload' => ['S' => $payload],
                'exception' => ['S' => (string) $exception],
                'failed_at' => ['N' => (string) $failedAt->getTimestamp()],
                'expires_at' => ['N' => (string) $failedAt->addDays(3)->getTimestamp()],
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
                ':application' => ['S' => $this->applicationName],
            ],
            'ScanIndexForward' => false,
        ]);

        return collect($results['Items'])->map(function ($result) {
            return (object) [
                'id' => $result['uuid']['S'],
                'connection' => $result['connection']['S'],
                'queue' => $result['queue']['S'],
                'payload' => $result['payload']['S'],
                'exception' => $result['exception']['S'],
                'failed_at' => Carbon::createFromTimestamp(
                    (int) $result['failed_at']['N']
                )->format(DateTimeInterface::ISO8601),
            ];
        })->all();
    }

    /**
     * Get a single failed job.
     *
     * @param  mixed  $id
     * @return object|null
     */
    public function find($id)
    {
        $result = $this->dynamo->getItem([
            'TableName' => $this->table,
            'Key' => [
                'application' => ['S' => $this->applicationName],
                'uuid' => ['S' => $id],
            ],
        ]);

        if (! isset($result['Item'])) {
            return;
        }

        return (object) [
            'id' => $result['Item']['uuid']['S'],
            'connection' => $result['Item']['connection']['S'],
            'queue' => $result['Item']['queue']['S'],
            'payload' => $result['Item']['payload']['S'],
            'exception' => $result['Item']['exception']['S'],
            'failed_at' => Carbon::createFromTimestamp(
                (int) $result['Item']['failed_at']['N']
            )->format(DateTimeInterface::ISO8601),
        ];
    }

    /**
     * Delete a single failed job from storage.
     *
     * @param  mixed  $id
     * @return bool
     */
    public function forget($id)
    {
        $this->dynamo->deleteItem([
            'TableName' => $this->table,
            'Key' => [
                'application' => ['S' => $this->applicationName],
                'uuid' => ['S' => $id],
            ],
        ]);

        return true;
    }

    /**
     * Flush all of the failed jobs from storage.
     *
     * @return void
     *
     * @throws \Exception
     */
    public function flush()
    {
        throw new Exception("DynamoDb failed job storage may not be flushed. Please use DynamoDb's TTL features on your expires_at attribute.");
    }
}
