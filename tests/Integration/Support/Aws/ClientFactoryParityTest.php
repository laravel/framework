<?php

namespace Illuminate\Tests\Integration\Support\Aws;

use Aws\DynamoDb\DynamoDbClient;
use Aws\S3\S3Client;
use Aws\Ses\SesClient;
use Aws\SesV2\SesV2Client;
use Aws\Sqs\SqsClient;
use Illuminate\Bus\DynamoBatchRepository;
use Illuminate\Support\Aws\ClientFactory;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class ClientFactoryParityTest extends TestCase
{
    /**
     * The configuration each client was created with, keyed by context.
     *
     * @var array<string, array>
     */
    protected $clients = [];

    #[DataProvider('credentialConfigurations')]
    public function testEveryAwsClientResolvesTheSameCredentialsFromTheSameConfiguration(array $credentials)
    {
        $this->configureEverySite($credentials);

        Storage::disk('s3');
        Queue::connection('sqs');
        Mail::mailer('ses')->getSymfonyTransport();
        Mail::mailer('ses-v2')->getSymfonyTransport();
        Cache::store('dynamodb');
        $this->app['queue.failer'];
        $this->app->make(DynamoBatchRepository::class);

        $this->assertSame([
            'filesystems.disks.s3',
            'queue.connections.sqs',
            'mail.mailers.ses',
            'mail.mailers.ses-v2',
            'cache.stores.dynamodb',
            'queue.failed',
            'queue.batching',
        ], array_keys($this->clients));

        $expected = $this->clients['filesystems.disks.s3']['credentials'];

        foreach ($this->clients as $context => $config) {
            $this->assertSame($expected, $config['credentials'], "The [{$context}] client resolved different credentials.");
        }

        foreach ($this->clients as $context => $config) {
            $this->assertArrayNotHasKey('token', $config, "The [{$context}] client received the session token as an auth token.");
            $this->assertArrayNotHasKey('credential_cache', $config, "The [{$context}] client received the credential cache options.");
        }
    }

    public static function credentialConfigurations()
    {
        return [
            'default chain' => [[]],
            'static keys' => [['key' => 'foo', 'secret' => 'bar', 'token' => 'baz']],
            'named provider' => [['credentials' => 'ecs']],
            'named provider with options' => [['credentials' => ['provider' => 'instance', 'timeout' => 2]]],
            'named provider with cache' => [['credentials' => 'ecs', 'credential_cache' => ['enabled' => true, 'store' => 'array']]],
            'profile' => [['profile' => 'reports']],
        ];
    }

    /**
     * Configure all seven AWS client sites with the same credential configuration.
     */
    protected function configureEverySite(array $credentials): void
    {
        $common = ['region' => 'us-east-1', ...$credentials];

        $this->app['config']->set([
            'filesystems.disks.s3' => ['driver' => 's3', 'bucket' => 'bucket', ...$common],
            'queue.connections.sqs' => ['driver' => 'sqs', 'queue' => 'default', ...$common],
            'services.ses' => $common,
            'mail.mailers.ses' => ['transport' => 'ses'],
            'mail.mailers.ses-v2' => ['transport' => 'ses-v2'],
            'cache.stores.dynamodb' => ['driver' => 'dynamodb', 'table' => 'cache', ...$common],
            'queue.failed' => ['driver' => 'dynamodb', 'table' => 'failed_jobs', ...$common],
            'queue.batching' => ['driver' => 'dynamodb', 'table' => 'job_batches', ...$common],
        ]);

        $factory = $this->app->make(ClientFactory::class);

        // The global options callback sees every client's context first, the resolver then captures its final config...
        $factory->globalOptions(function ($client, $config, $context) use (&$current) {
            $current = $context;

            return [];
        });

        foreach ([S3Client::class, SqsClient::class, SesClient::class, SesV2Client::class, DynamoDbClient::class] as $client) {
            $factory->resolveUsing($client, function ($config) use ($client, &$current) {
                $this->clients[$current] = $config;

                return new $client($config);
            });
        }
    }
}
