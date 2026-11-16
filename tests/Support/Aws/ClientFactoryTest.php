<?php

namespace Illuminate\Tests\Support\Aws;

use Aws\Credentials\Credentials;
use Aws\DynamoDb\DynamoDbClient;
use Aws\Handler\HttpTransportSharing;
use Aws\S3\S3Client;
use Aws\Sqs\SqsClient;
use GuzzleHttp\Promise\Create;
use GuzzleHttp\Psr7\Response;
use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository;
use Illuminate\Container\Container;
use Illuminate\Support\Aws\ClientFactory;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use UnexpectedValueException;

class ClientFactoryTest extends TestCase
{
    protected $container;

    protected $environment = [];

    protected $fetches = 0;

    protected function setUp(): void
    {
        parent::setUp();

        Container::setInstance($this->container = new Container);

        // Make the default chain skip the environment and files, and resolve from a fake container endpoint...
        foreach (['AWS_ACCESS_KEY_ID', 'AWS_SECRET_ACCESS_KEY', 'AWS_SESSION_TOKEN', 'AWS_CONTAINER_CREDENTIALS_FULL_URI', 'AWS_SHARED_CREDENTIALS_FILE'] as $name) {
            $this->environment[$name] = getenv($name);
            putenv($name);
        }

        putenv('AWS_CONTAINER_CREDENTIALS_FULL_URI=http://127.0.0.1/credentials');
    }

    protected function tearDown(): void
    {
        foreach ($this->environment as $name => $value) {
            putenv($value === false ? $name : "{$name}={$value}");
        }

        Container::setInstance(null);

        @unlink(sys_get_temp_dir().'/laravel-aws-credentials-'.getmypid());

        parent::tearDown();
    }

    public function testFactoryIsASingleton()
    {
        $this->assertSame($this->container->make(ClientFactory::class), $this->container->make(ClientFactory::class));
    }

    public function testFactoryCreatesTheGivenClient()
    {
        $client = $this->factory()->make(SqsClient::class, ['region' => 'us-east-1', 'key' => 'foo', 'secret' => 'bar'], 'queue.connections.sqs');

        $this->assertInstanceOf(SqsClient::class, $client);
        $this->assertSame('foo', $client->getCredentials()->wait()->getAccessKeyId());
    }

    public function testMissingSdkClassThrowsAHelpfulException()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The [aws/aws-sdk-php] package is required to use [queue.connections.sqs].');

        $this->factory()->make('Aws\Missing\MissingClient', [], 'queue.connections.sqs');
    }

    public function testStaticCredentialsAreBuiltFromKeySecretAndToken()
    {
        $config = $this->config(['key' => 'foo', 'secret' => 'bar', 'token' => 'baz']);

        $this->assertSame(['key' => 'foo', 'secret' => 'bar', 'token' => 'baz'], $config['credentials']);
        $this->assertArrayNotHasKey('token', $config);
    }

    public function testCredentialsGivenByTheApplicationAreUsedAsTheyAre()
    {
        $provider = fn () => Create::promiseFor(new Credentials('foo', 'bar'));
        $credentials = new Credentials('foo', 'bar');

        $this->assertSame($provider, $this->config(['credentials' => $provider, 'key' => 'baz', 'secret' => 'qux'])['credentials']);
        $this->assertSame($provider, $this->config(['credentials' => ['provider' => $provider]])['credentials']);
        $this->assertSame($credentials, $this->config(['credentials' => $credentials])['credentials']);
        $this->assertSame(['key' => 'foo', 'secret' => 'bar'], $this->config(['credentials' => ['key' => 'foo', 'secret' => 'bar']])['credentials']);
        $this->assertFalse($this->config(['credentials' => false])['credentials']);
    }

    public function testKeyAndSecretAreUsedWhenCredentialsAreFalse()
    {
        $this->assertSame(['key' => 'foo', 'secret' => 'bar'], $this->config(['credentials' => false, 'key' => 'foo', 'secret' => 'bar'])['credentials']);
    }

    public function testKeyAndSecretReplaceAStaticCredentialsArray()
    {
        $config = $this->config(['credentials' => ['key' => 'old', 'secret' => 'old'], 'key' => 'foo', 'secret' => 'bar']);

        $this->assertSame(['key' => 'foo', 'secret' => 'bar'], $config['credentials']);
    }

    public function testProfilesAreResolvedByTheFactory()
    {
        $this->credentialsFile(['reports' => 'reports-key']);

        $config = $this->config(['profile' => 'reports', 'credentials' => 'ecs', 'token' => 'baz']);

        // The profile wins over other credentials, as it does in the SDK, which must not see it again...
        $this->assertSame('reports-key', $config['credentials']()->wait()->getAccessKeyId());
        $this->assertArrayNotHasKey('profile', $config);
        $this->assertArrayNotHasKey('token', $config);
    }

    public function testProfilesAreMemoizedAndSharedBetweenClientsUsingTheSameProfile()
    {
        $this->credentialsFile(['reports' => 'reports-key', 'billing' => 'billing-key']);

        $factory = $this->factory();

        $first = $this->config(['profile' => 'reports'], $factory)['credentials'];
        $this->assertSame('reports-key', $first()->wait()->getAccessKeyId());

        // A later change to the file isn't read again, because the credentials are memoized...
        $this->credentialsFile(['reports' => 'changed-key', 'billing' => 'billing-key']);

        $this->assertSame($first, $this->config(['profile' => 'reports', 'queue' => 'other'], $factory)['credentials']);
        $this->assertSame('reports-key', $first()->wait()->getAccessKeyId());
        $this->assertSame('billing-key', $this->config(['profile' => 'billing'], $factory)['credentials']()->wait()->getAccessKeyId());
    }

    public function testProfilesWithoutARegionAreLeftToTheSdk()
    {
        $config = $this->config(['profile' => 'reports', 'region' => null]);

        $this->assertSame('reports', $config['profile']);
        $this->assertArrayNotHasKey('credentials', $config);
    }

    public function testInvalidNamedCredentialProvidersThrow()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid credential provider [foo].');

        $this->config(['credentials' => 'foo']);
    }

    public function testNamedCredentialProvidersAreResolved()
    {
        $factory = $this->factory();
        $config = ['region' => 'us-east-1', 'credentials' => ['provider' => 'ecs', 'client' => $this->credentialClient()]];

        $first = $factory->make(SqsClient::class, $config, 'queue.connections.sqs');
        $second = $factory->make(SqsClient::class, $config, 'queue.connections.sqs');

        $this->assertSame('ecs-key', $first->getCredentials()->wait()->getAccessKeyId());
        $this->assertSame('ecs-key', $second->getCredentials()->wait()->getAccessKeyId());
        $this->assertSame(1, $this->fetches);
    }

    public function testCredentialProvidersAreSharedBetweenConnectionsWithTheSameSource()
    {
        foreach ([[], ['credentials' => 'ecs'], ['credentials' => ['provider' => 'instance', 'timeout' => 2]]] as $credentials) {
            $store = $this->recordingCache();
            $factory = $this->factory();
            $config = ['region' => 'us-east-1', 'use_aws_shared_config_files' => false, 'credential_cache' => ['enabled' => true], ...$credentials];

            $clients = [
                $factory->make(S3Client::class, $config + ['bucket' => 'reports'], 'filesystems.disks.s3'),
                $factory->make(SqsClient::class, $config + ['queue' => 'default'], 'queue.connections.sqs'),
                $factory->make(DynamoDbClient::class, $config + ['table' => 'cache'], 'cache.stores.dynamodb'),
            ];

            foreach ($clients as $client) {
                $this->assertSame('cached-key', $client->getCredentials()->wait()->getAccessKeyId());
            }

            // A single shared provider resolves the credentials once for all three clients...
            $this->assertCount(1, $store->keys);
        }
    }

    public function testSourcesWithObjectsAreOnlySharedWithinTheirConnection()
    {
        $factory = $this->factory();
        $client = $this->credentialClient();

        $sqs = $this->config(['credentials' => ['provider' => 'ecs', 'client' => $client]], $factory);
        $sqsAgain = $this->config(['credentials' => ['provider' => 'ecs', 'client' => $client]], $factory);

        $this->assertSame($sqs['credentials'], $sqsAgain['credentials']);

        // New objects on every call replace the connection's provider instead of piling up...
        foreach (range(1, 5) as $i) {
            $this->config(['credentials' => ['provider' => 'ecs', 'client' => $this->credentialClient()]], $factory);
        }

        $this->assertCount(1, (fn () => $this->credentialProviders)->call($factory));
    }

    public function testTheDefaultChainIsLeftToTheSdkWithoutARegion()
    {
        $this->assertArrayNotHasKey('credentials', $this->config(['region' => null]));
    }

    public function testCredentialProvidersAreNotSharedWhenTheSourceDiffers()
    {
        $factory = $this->factory();
        $client = $this->credentialClient();

        $east = $this->config(['region' => 'us-east-1', 'client' => $client], $factory);
        $eastAgain = $this->config(['region' => 'us-east-1', 'client' => $client], $factory);
        $west = $this->config(['region' => 'us-west-2', 'client' => $client], $factory);
        $files = $this->config(['region' => 'us-east-1', 'client' => $client, 'use_aws_shared_config_files' => false], $factory);
        $otherClient = $this->config(['region' => 'us-east-1', 'client' => $this->credentialClient()], $factory);
        $ecs = $this->config(['credentials' => ['provider' => 'ecs', 'timeout' => 5]], $factory);
        $otherEcs = $this->config(['credentials' => ['provider' => 'ecs', 'timeout' => 10]], $factory);
        $cached = $this->config(['credentials' => ['provider' => 'ecs', 'timeout' => 5], 'credential_cache' => ['enabled' => true]], $factory);

        $this->assertSame($east['credentials'], $eastAgain['credentials']);
        $this->assertNotSame($east['credentials'], $west['credentials']);
        $this->assertNotSame($east['credentials'], $files['credentials']);
        $this->assertNotSame($east['credentials'], $otherClient['credentials']);
        $this->assertNotSame($ecs['credentials'], $otherEcs['credentials']);
        $this->assertNotSame($ecs['credentials'], $cached['credentials']);
    }

    public function testCredentialProvidersAreSharedWhenTheSourceIsTheSame()
    {
        $factory = $this->factory();

        $first = $this->config(['credentials' => ['provider' => 'ecs', 'timeout' => 5], 'bucket' => 'foo'], $factory);
        $second = $this->config(['credentials' => ['timeout' => 5, 'provider' => 'ecs'], 'queue' => 'bar'], $factory);

        $this->assertSame($first['credentials'], $second['credentials']);
    }

    public function testCredentialsAreSharedAcrossProcessesThroughTheCache()
    {
        $repository = $this->arrayCache();
        $config = ['region' => 'us-east-1', 'credentials' => 'ecs', 'credential_cache' => ['enabled' => true]];

        $repository->forever(
            $this->factory()->credentialsCacheKey(SqsClient::class, $config, 'queue.connections.sqs'),
            new Credentials('cached-key', 'cached-secret', 'cached-token', time() + 3600),
        );

        // Each factory stands in for a separate process, and none of them fetches the credentials...
        foreach (range(1, 3) as $i) {
            $sqs = $this->factory()->make(SqsClient::class, $config, 'queue.connections.sqs');

            $this->assertSame('cached-key', $sqs->getCredentials()->wait()->getAccessKeyId());
        }
    }

    public function testSourcesWithObjectsAreNotSharedAcrossProcesses()
    {
        $repository = $this->arrayCache();
        $config = [
            'region' => 'us-east-1',
            'credentials' => ['provider' => 'ecs', 'client' => $this->credentialClient()],
            'credential_cache' => ['enabled' => true],
        ];

        foreach (range(1, 3) as $i) {
            $this->factory()->make(SqsClient::class, $config, 'queue.connections.sqs')->getCredentials()->wait();
        }

        $this->assertSame(3, $this->fetches);
        $this->assertNull($this->factory()->credentialsCacheKey(SqsClient::class, $config, 'queue.connections.sqs'));
    }

    public function testTheCrossProcessCacheKeyIsPerIdentityRatherThanPerConnection()
    {
        $store = $this->recordingCache();

        $this->resolveCredentials(['credentials' => 'ecs', 'prefix' => 'foo'], 'queue.connections.foo');
        $this->resolveCredentials(['credentials' => 'ecs', 'prefix' => 'bar'], 'queue.connections.bar');
        $this->resolveCredentials(['credentials' => 'instance'], 'queue.connections.baz');

        $this->assertCount(2, array_unique($store->keys));
        $this->assertSame($store->keys[0], $store->keys[1]);
        $this->assertStringStartsWith('aws:credentials:', $store->keys[0]);
    }

    public function testTheCrossProcessCacheKeyIsScopedToTheIdentityEnvironmentAndSource()
    {
        $factory = $this->factory();
        $key = fn ($config) => $factory->credentialsCacheKey(SqsClient::class, $config, 'queue.connections.sqs');

        $default = $key(['region' => 'us-east-2', 'prefix' => 'foo']);

        $this->assertSame($default, $key(['region' => 'us-east-2', 'prefix' => 'bar']));
        $this->assertNotSame($default, $key(['region' => 'us-west-2']));
        $this->assertNotSame($default, $key(['region' => 'us-east-2', 'credentials' => 'ecs']));
        $this->assertNull($key(['region' => 'us-east-2', 'key' => 'foo', 'secret' => 'bar']));

        // Every environment variable that decides the identity changes the key...
        foreach (['AWS_CONTAINER_CREDENTIALS_RELATIVE_URI', 'AWS_CONTAINER_CREDENTIALS_FULL_URI', 'AWS_CONTAINER_AUTHORIZATION_TOKEN_FILE', 'AWS_ROLE_ARN', 'AWS_WEB_IDENTITY_TOKEN_FILE', 'AWS_PROFILE'] as $name) {
            $previous = getenv($name);

            putenv("{$name}=other");

            try {
                $this->assertNotSame($default, $key(['region' => 'us-east-2', 'prefix' => 'foo']), $name);
            } finally {
                putenv($previous === false ? $name : "{$name}={$previous}");
            }
        }
    }

    public function testGlobalOptionsAreAppliedBetweenTheDefaultsAndTheConfiguration()
    {
        $factory = $this->factory()->globalOptions(['region' => 'eu-west-1', 'retries' => 5, 'endpoint' => 'http://localstack:4566']);

        $config = $this->config(['region' => 'us-east-1'], $factory, defaults: ['retries' => 3, 'service' => 'email']);

        $this->assertSame('us-east-1', $config['region']);
        $this->assertSame(5, $config['retries']);
        $this->assertSame('email', $config['service']);
        $this->assertSame('http://localstack:4566', $config['endpoint']);
        $this->assertArrayNotHasKey('version', $config);
    }

    public function testHttpOptionsAreMergedWithTheGlobalOptions()
    {
        $defaults = ['http' => ['timeout' => 60, 'connect_timeout' => 60]];

        $this->assertSame(['timeout' => 60, 'connect_timeout' => 60], $this->config([], defaults: $defaults)['http']);

        // The connection's options replace the site defaults, as they always have...
        $this->assertSame(['verify' => false], $this->config(['http' => ['verify' => false]], defaults: $defaults)['http']);

        $factory = $this->factory()->globalOptions(['http' => ['connect_timeout' => 2, 'proxy' => 'http://proxy']]);

        $this->assertSame(
            ['timeout' => 60, 'connect_timeout' => 2, 'proxy' => 'http://proxy'],
            $this->config([], $factory, defaults: $defaults)['http'],
        );

        $this->assertSame(
            ['connect_timeout' => 2, 'proxy' => 'http://proxy', 'verify' => false],
            $this->config(['http' => ['verify' => false]], $factory, defaults: $defaults)['http'],
        );
    }

    public function testConnectionOptionsWithoutAValueFallBackToTheGlobalOptions()
    {
        $factory = $this->factory()->globalOptions(['endpoint' => 'http://localstack:4566', 'region' => 'eu-west-1']);

        $config = $this->config(['endpoint' => null, 'region' => null], $factory);

        $this->assertSame('http://localstack:4566', $config['endpoint']);
        $this->assertSame('eu-west-1', $config['region']);
    }

    public function testConnectionCredentialsTakePrecedenceOverTheGlobalCredentials()
    {
        $factory = $this->factory()->globalOptions(['credentials' => 'ecs', 'profile' => 'global', 'token' => 'global-token']);

        $static = $this->config(['key' => 'minio', 'secret' => 'minio-secret'], $factory);

        $this->assertSame(['key' => 'minio', 'secret' => 'minio-secret'], $static['credentials']);
        $this->assertArrayNotHasKey('profile', $static);

        $this->credentialsFile(['reports' => 'reports-key', 'global' => 'global-key']);

        $this->assertSame('reports-key', $this->config(['profile' => 'reports'], $factory)['credentials']()->wait()->getAccessKeyId());

        // Connections without credentials of their own still use the global ones...
        $this->assertSame('global-key', $this->config([], $factory)['credentials']()->wait()->getAccessKeyId());
    }

    public function testGlobalOptionsMayBeAClosure()
    {
        $factory = $this->factory()->globalOptions(function ($client, $config, $context) {
            $this->assertSame(SqsClient::class, $client);
            $this->assertSame('default', $config['queue']);
            $this->assertSame('queue.connections.sqs', $context);

            return ['retries' => 7];
        });

        $this->assertSame(7, $this->config(['queue' => 'default'], $factory)['retries']);
    }

    public function testGlobalCredentialsApplyToConnectionsWithoutCredentials()
    {
        $provider = fn () => Create::promiseFor(new Credentials('global', 'secret'));

        $factory = $this->factory()->globalOptions(['credentials' => $provider]);

        $this->assertSame($provider, $this->config([], $factory)['credentials']);
        $this->assertSame(['key' => 'foo', 'secret' => 'bar'], $this->config(['credentials' => null, 'key' => 'foo', 'secret' => 'bar'], $factory)['credentials']);
    }

    public function testClientsMayBeResolvedByContextOrClass()
    {
        $factory = $this->factory()
            ->resolveUsing(SqsClient::class, fn ($config) => new SqsClient([...$config, 'region' => 'class']))
            ->resolveUsing('queue.connections.reports', fn ($config) => new SqsClient([...$config, 'region' => 'context']));

        $this->assertSame('context', $factory->make(SqsClient::class, ['key' => 'foo', 'secret' => 'bar'], 'queue.connections.reports')->getRegion());
        $this->assertSame('class', $factory->make(SqsClient::class, ['key' => 'foo', 'secret' => 'bar'], 'queue.connections.sqs')->getRegion());
    }

    public function testResolversReceiveThePreparedConfiguration()
    {
        $factory = $this->factory()->resolveUsing(SqsClient::class, function ($config, $factory) use (&$received, &$receivedFactory) {
            [$received, $receivedFactory] = [$config, $factory];

            return new SqsClient($config);
        });

        $factory->make(SqsClient::class, ['region' => 'us-east-1', 'key' => 'foo', 'secret' => 'bar', 'token' => 'baz', 'credential_cache' => ['enabled' => true]], 'queue.connections.sqs');

        $this->assertSame(['key' => 'foo', 'secret' => 'bar', 'token' => 'baz'], $received['credentials']);
        $this->assertArrayNotHasKey('token', $received);
        $this->assertArrayNotHasKey('credential_cache', $received);
        $this->assertSame($factory, $receivedFactory);
    }

    public function testTheConfigurationIsPassedToTheClientWithoutTheTokenAndCacheOptions()
    {
        $factory = $this->factory()->resolveUsing(S3Client::class, function ($config) use (&$received) {
            $received = $config;

            return new S3Client($config);
        });

        $factory->make(S3Client::class, [
            'bucket' => 'reports',
            'region' => 'us-east-1',
            'ForcePathStyle' => true,
            's3_us_east_1_regional_endpoint' => 'regional',
            'key' => 'foo',
            'secret' => 'bar',
            'token' => 'baz',
            'credential_cache' => ['enabled' => false],
        ], 'filesystems.disks.s3');

        $this->assertSame('reports', $received['bucket']);
        $this->assertTrue($received['ForcePathStyle']);
        $this->assertSame('regional', $received['s3_us_east_1_regional_endpoint']);
        $this->assertArrayNotHasKey('token', $received);
        $this->assertArrayNotHasKey('credential_cache', $received);
    }

    public function testTheDefaultChainIsCachedWithoutARegionWhenTheCredentialCacheIsEnabled()
    {
        $this->assertIsCallable($this->config(['region' => null, 'credential_cache' => ['enabled' => true]])['credentials']);
    }

    public function testResolversMustReturnAnInstanceOfTheClient()
    {
        $factory = $this->factory()->resolveUsing('queue.connections.sqs', fn ($config) => new S3Client($config));

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('The client resolver for [queue.connections.sqs] must return an instance of [Aws\Sqs\SqsClient].');

        $factory->make(SqsClient::class, ['region' => 'us-east-1', 'key' => 'foo', 'secret' => 'bar'], 'queue.connections.sqs');
    }

    public function testTransportSharingIsPassedToTheSdk()
    {
        if (! class_exists(HttpTransportSharing::class)) {
            $this->markTestSkipped('The installed AWS SDK does not support transport sharing.');
        }

        $this->assertSame('persistent_prefer', $this->config(['transport_sharing' => 'persistent_prefer'])['transport_sharing']);
        $this->assertSame('handler_prefer', $this->config([], $this->factory()->globalOptions(['transport_sharing' => 'handler_prefer']))['transport_sharing']);
        $this->assertSame('persistent_require', $this->config(['transport_sharing' => 'persistent_require'])['transport_sharing']);
        $this->assertArrayNotHasKey('transport_sharing', $this->config(['transport_sharing' => null]));
        $this->assertArrayNotHasKey('transport_sharing', $this->config([]));
    }

    public function testPreferredTransportSharingIsDroppedWhenTheSdkDoesNotSupportIt()
    {
        if (class_exists(HttpTransportSharing::class)) {
            $this->markTestSkipped('The installed AWS SDK supports transport sharing.');
        }

        $this->assertArrayNotHasKey('transport_sharing', $this->config(['transport_sharing' => 'persistent_prefer']));
        $this->assertArrayNotHasKey('transport_sharing', $this->config(['transport_sharing' => 'none']));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The [persistent_require] transport sharing mode requires an aws/aws-sdk-php version');

        $this->config(['transport_sharing' => 'persistent_require']);
    }

    /**
     * Create a new client factory instance.
     */
    protected function factory(): ClientFactory
    {
        return new ClientFactory($this->container);
    }

    /**
     * Get the configuration the factory passes to the SDK for the given SQS connection config.
     */
    protected function config(array $config, ?ClientFactory $factory = null, array $defaults = []): array
    {
        ($factory ??= $this->factory())->resolveUsing('queue.connections.sqs', function ($config) use (&$received) {
            $received = $config;

            return new SqsClient(['region' => 'us-east-1', 'credentials' => false]);
        });

        $factory->make(SqsClient::class, $config + ['region' => 'us-east-1'], 'queue.connections.sqs', $defaults);

        return $received;
    }

    /**
     * Resolve the credentials of an SQS client with the given connection config and context.
     */
    protected function resolveCredentials(array $config, string $context): void
    {
        $this->factory()
            ->make(SqsClient::class, $config + ['region' => 'us-east-1', 'credential_cache' => ['enabled' => true]], $context)
            ->getCredentials()
            ->wait();
    }

    /**
     * Point the SDK at a temporary shared credentials file with the given profiles and access keys.
     */
    protected function credentialsFile(array $profiles): void
    {
        $path = sys_get_temp_dir().'/laravel-aws-credentials-'.getmypid();

        file_put_contents($path, implode("\n", array_map(
            fn ($profile, $key) => "[{$profile}]\naws_access_key_id = {$key}\naws_secret_access_key = {$key}-secret\n",
            array_keys($profiles), $profiles,
        )));

        putenv("AWS_SHARED_CREDENTIALS_FILE={$path}");
    }

    /**
     * Bind a cache manager whose stores all share one array repository.
     */
    protected function arrayCache(): Repository
    {
        $this->container->instance('cache', new class($repository = new Repository(new ArrayStore))
        {
            public function __construct(public $repository)
            {
            }

            public function store($name = null)
            {
                return $this->repository;
            }
        });

        return $repository;
    }

    /**
     * Bind a cache store that records the keys it is asked for and always holds fresh credentials.
     */
    protected function recordingCache(): ArrayStore
    {
        $store = new class extends ArrayStore
        {
            public $keys = [];

            public function get($key)
            {
                $this->keys[] = $key;

                return new Credentials('cached-key', 'cached-secret', expires: time() + 3600);
            }
        };

        $this->container->instance('cache', new class(new Repository($store))
        {
            public function __construct(public $repository)
            {
            }

            public function store($name = null)
            {
                return $this->repository;
            }
        });

        return $store;
    }

    /**
     * Get a fake container credentials endpoint client that counts its fetches.
     */
    protected function credentialClient(): callable
    {
        return function () {
            $this->fetches++;

            return Create::promiseFor(new Response(200, [], json_encode([
                'AccessKeyId' => 'ecs-key',
                'SecretAccessKey' => 'ecs-secret',
                'Token' => 'ecs-token',
                'Expiration' => gmdate('Y-m-d\TH:i:s\Z', time() + 3600),
            ])));
        };
    }
}
