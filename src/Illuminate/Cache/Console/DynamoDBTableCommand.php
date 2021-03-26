<?php

namespace Illuminate\Cache\Console;

use Aws\Exception\AwsException;
use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Illuminate\Support\Str;

class DynamoDBTableCommand extends Command
{
    use ConfirmableTrait;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'cache:dynamodb
                    {--force : Force the operation to run when in production}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create the cache table for the DynamoDB cache store.';

    /**
     * @var \Aws\DynamoDb\DynamoDbClient
     */
    protected $client;

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        if (! $this->confirmToProceed()) {
            return;
        }

        $config = $this->laravel['config']['cache.stores.dynamodb'];

        if ($this->dynamoTableExists($config['table'])) {
            $this->info('DynamoDB cache table already exists.');

            return;
        }

        $this->client()->createTable([
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

        $this->info('DynamoDB cache table created successfully!');
    }

    /**
     * Determine if the given DynamoDB table exists.
     *
     * @param  string  $table
     * @return bool
     */
    protected function dynamoTableExists($table)
    {
        try {
            $this->client()->describeTable(['TableName' => $table]);

            return true;
        } catch (AwsException $e) {
            $errorMessages = [
                'resource not found',
                'Cannot do operations on a non-existent table',
            ];

            if (Str::contains($e->getAwsErrorMessage(), $errorMessages)) {
                return false;
            }

            throw $e;
        }
    }

    /**
     * Get the DynamoDB cache client.
     *
     * @return \Aws\DynamoDb\DynamoDbClient
     */
    protected function client()
    {
        if ($this->client) {
            return $this->client;
        }

        return $this->client = $this->laravel->make('cache.dynamodb.client');
    }
}
