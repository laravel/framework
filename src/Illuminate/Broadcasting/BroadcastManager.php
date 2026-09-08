<?php

namespace Illuminate\Broadcasting;

use Ably\AblyRest;
use Closure;
use GuzzleHttp\Client as GuzzleClient;
use Illuminate\Broadcasting\Broadcasters\AblyBroadcaster;
use Illuminate\Broadcasting\Broadcasters\LogBroadcaster;
use Illuminate\Broadcasting\Broadcasters\MercureBroadcaster;
use Illuminate\Broadcasting\Broadcasters\NullBroadcaster;
use Illuminate\Broadcasting\Broadcasters\PusherBroadcaster;
use Illuminate\Broadcasting\Broadcasters\RedisBroadcaster;
use Illuminate\Bus\UniqueLock;
use Illuminate\Contracts\Broadcasting\Factory as FactoryContract;
use Illuminate\Contracts\Broadcasting\ShouldBeUnique;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Contracts\Broadcasting\ShouldRescue;
use Illuminate\Contracts\Bus\Dispatcher as BusDispatcherContract;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Contracts\Foundation\CachesRoutes;
use Illuminate\Queue\Attributes\Connection as ConnectionAttribute;
use Illuminate\Queue\Attributes\Queue as QueueAttribute;
use Illuminate\Queue\Attributes\ReadsQueueAttributes;
use Illuminate\Support\Queue\Concerns\ResolvesQueueRoutes;
use Illuminate\Support\RebindsCallbacksToSelf;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Pusher\Pusher;
use ReflectionException;
use RuntimeException;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\Mercure\FrankenPhpHub;
use Symfony\Component\Mercure\Hub;
use Symfony\Component\Mercure\Jwt\DefaultClaimsTokenFactory;
use Symfony\Component\Mercure\Jwt\FactoryTokenProvider;
use Symfony\Component\Mercure\Jwt\Grant;
use Symfony\Component\Mercure\Jwt\WebTokenFactory;
use Symfony\Component\Mercure\ProtocolVersion;
use Throwable;

use function Illuminate\Support\enum_value;

/**
 * @mixin \Illuminate\Contracts\Broadcasting\Broadcaster
 */
class BroadcastManager implements FactoryContract
{
    use ReadsQueueAttributes, RebindsCallbacksToSelf, ResolvesQueueRoutes;

    /**
     * The application instance.
     *
     * @var \Illuminate\Contracts\Container\Container
     */
    protected $app;

    /**
     * The array of resolved broadcast drivers.
     *
     * @var array
     */
    protected $drivers = [];

    /**
     * The registered custom driver creators.
     *
     * @var array
     */
    protected $customCreators = [];

    /**
     * Create a new manager instance.
     *
     * @param  \Illuminate\Contracts\Container\Container  $app
     */
    public function __construct($app)
    {
        $this->app = $app;
    }

    /**
     * Register the routes for handling broadcast channel authentication and sockets.
     *
     * @param  array|null  $attributes
     * @return void
     */
    public function routes(?array $attributes = null)
    {
        if ($this->app instanceof CachesRoutes && $this->app->routesAreCached()) {
            return;
        }

        $attributes = $attributes ?: ['middleware' => ['web']];

        $this->app['router']->group($attributes, function ($router) {
            $router->match(
                ['get', 'post'], '/broadcasting/auth',
                '\\'.BroadcastController::class.'@authenticate'
            )->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class]);
        });
    }

    /**
     * Register the routes for handling broadcast user authentication.
     *
     * @param  array|null  $attributes
     * @return void
     */
    public function userRoutes(?array $attributes = null)
    {
        if ($this->app instanceof CachesRoutes && $this->app->routesAreCached()) {
            return;
        }

        $attributes = $attributes ?: ['middleware' => ['web']];

        $this->app['router']->group($attributes, function ($router) {
            $router->match(
                ['get', 'post'], '/broadcasting/user-auth',
                '\\'.BroadcastController::class.'@authenticateUser'
            )->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class]);
        });
    }

    /**
     * Register the routes for handling broadcast authentication and sockets.
     *
     * Alias of "routes" method.
     *
     * @param  array|null  $attributes
     * @return void
     */
    public function channelRoutes(?array $attributes = null)
    {
        $this->routes($attributes);
    }

    /**
     * Get the socket ID for the given request.
     *
     * @param  \Illuminate\Http\Request|null  $request
     * @return string|null
     */
    public function socket($request = null)
    {
        if (! $request && ! $this->app->bound('request')) {
            return;
        }

        $request = $request ?: $this->app['request'];

        return $request->header('X-Socket-ID');
    }

    /**
     * Begin sending an anonymous broadcast to the given channels.
     */
    public function on(Channel|string|array $channels): AnonymousEvent
    {
        return new AnonymousEvent($channels);
    }

    /**
     * Begin sending an anonymous broadcast to the given private channels.
     */
    public function private(string $channel): AnonymousEvent
    {
        return $this->on(new PrivateChannel($channel));
    }

    /**
     * Begin sending an anonymous broadcast to the given presence channels.
     */
    public function presence(string $channel): AnonymousEvent
    {
        return $this->on(new PresenceChannel($channel));
    }

    /**
     * Begin broadcasting an event.
     *
     * @param  mixed  $event
     * @return \Illuminate\Broadcasting\PendingBroadcast
     */
    public function event($event = null)
    {
        return new PendingBroadcast($this->app->make('events'), $event);
    }

    /**
     * Queue the given event for broadcast.
     *
     * @param  mixed  $event
     * @return void
     */
    public function queue($event)
    {
        if ($event instanceof ShouldBroadcastNow ||
            (is_object($event) &&
             method_exists($event, 'shouldBroadcastNow') &&
             $event->shouldBroadcastNow())) {
            $dispatch = fn () => $this->app->make(BusDispatcherContract::class)
                ->dispatchNow(new BroadcastEvent(clone $event));

            return $event instanceof ShouldRescue
                ? $this->rescue($dispatch)
                : $dispatch();
        }

        $queue = match (true) {
            method_exists($event, 'broadcastQueue') => $event->broadcastQueue(),
            isset($event->broadcastQueue) => $event->broadcastQueue,
            isset($event->queue) => $event->queue,
            default => null,
        };

        if (is_null($queue)) {
            $queue = $this->getAttributeValue($event, QueueAttribute::class, 'queue')
                ?? $this->resolveQueueFromQueueRoute($event)
                ?? null;
        }

        $broadcastEvent = new BroadcastEvent(clone $event);

        if ($event instanceof ShouldBeUnique) {
            $broadcastEvent = new UniqueBroadcastEvent(clone $event);

            if ($this->mustBeUniqueAndCannotAcquireLock($broadcastEvent)) {
                return;
            }
        }

        $push = fn () => $this->app->make('queue')
            ->connection(
                $event->connection
                    ?? $this->getAttributeValue($event, ConnectionAttribute::class, 'connection')
                    ?? $this->resolveConnectionFromQueueRoute($event)
                    ?? null
            )
            ->pushOn($queue, $broadcastEvent);

        $event instanceof ShouldRescue
            ? $this->rescue($push)
            : $push();
    }

    /**
     * Determine if the broadcastable event must be unique and determine if we can acquire the necessary lock.
     *
     * @param  mixed  $event
     * @return bool
     */
    protected function mustBeUniqueAndCannotAcquireLock($event)
    {
        return ! (new UniqueLock(
            method_exists($event, 'uniqueVia')
                ? $event->uniqueVia()
                : $this->app->make(Cache::class)
        ))->acquire($event);
    }

    /**
     * Get a broadcaster instance by name.
     *
     * @param  \UnitEnum|string|null  $name
     * @return mixed
     */
    public function connection($name = null)
    {
        return $this->driver($name);
    }

    /**
     * Get a driver instance.
     *
     * @param  \UnitEnum|string|null  $name
     * @return mixed
     */
    public function driver($name = null)
    {
        $name = enum_value($name) ?: $this->getDefaultDriver();

        return $this->drivers[$name] = $this->get($name);
    }

    /**
     * Attempt to get the connection from the local cache.
     *
     * @param  string  $name
     * @return \Illuminate\Contracts\Broadcasting\Broadcaster
     */
    protected function get($name)
    {
        return $this->drivers[$name] ?? $this->resolve($name);
    }

    /**
     * Resolve the given broadcaster.
     *
     * @param  string  $name
     * @return \Illuminate\Contracts\Broadcasting\Broadcaster
     *
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     */
    protected function resolve($name)
    {
        $config = $this->getConfig($name);

        if (is_null($config)) {
            throw new InvalidArgumentException("Broadcast connection [{$name}] is not defined.");
        }

        if (isset($this->customCreators[$config['driver']])) {
            return $this->callCustomCreator($config);
        }

        $driverMethod = 'create'.ucfirst($config['driver']).'Driver';

        if (! method_exists($this, $driverMethod)) {
            throw new InvalidArgumentException("Driver [{$config['driver']}] is not supported.");
        }

        try {
            return $this->{$driverMethod}($config);
        } catch (Throwable $e) {
            throw new RuntimeException("Failed to create broadcaster for connection \"{$name}\" with error: {$e->getMessage()}.", 0, $e);
        }
    }

    /**
     * Call a custom driver creator.
     *
     * @param  array  $config
     * @return mixed
     */
    protected function callCustomCreator(array $config)
    {
        return $this->customCreators[$config['driver']]($this->app, $config);
    }

    /**
     * Create an instance of the driver.
     *
     * @param  array  $config
     * @return \Illuminate\Contracts\Broadcasting\Broadcaster
     */
    protected function createReverbDriver(array $config)
    {
        return $this->createPusherDriver($config);
    }

    /**
     * Create an instance of the driver.
     *
     * @param  array  $config
     * @return \Illuminate\Contracts\Broadcasting\Broadcaster
     */
    protected function createPusherDriver(array $config)
    {
        return new PusherBroadcaster($this->pusher($config), $config['jsonp'] ?? false);
    }

    /**
     * Get a Pusher instance for the given configuration.
     *
     * @param  array  $config
     * @return \Pusher\Pusher
     */
    public function pusher(array $config)
    {
        $guzzleClient = new GuzzleClient(
            array_merge(
                [
                    'connect_timeout' => 10,
                    'crypto_method' => STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT,
                    'timeout' => 30,
                ],
                $config['client_options'] ?? [],
            ),
        );

        $pusher = new Pusher(
            $config['key'],
            $config['secret'],
            $config['app_id'],
            $config['options'] ?? [],
            $guzzleClient,
        );

        if ($config['log'] ?? false) {
            $pusher->setLogger($this->app->make(LoggerInterface::class));
        }

        return $pusher;
    }

    /**
     * Create an instance of the driver.
     *
     * @param  array  $config
     * @return \Illuminate\Contracts\Broadcasting\Broadcaster
     */
    protected function createAblyDriver(array $config)
    {
        return new AblyBroadcaster($this->ably($config));
    }

    /**
     * Get an Ably instance for the given configuration.
     *
     * @param  array  $config
     * @return \Ably\AblyRest
     */
    public function ably(array $config)
    {
        return new AblyRest($config);
    }

    /**
     * Create an instance of the driver.
     *
     * @param  array  $config
     * @return \Illuminate\Contracts\Broadcasting\Broadcaster
     */
    protected function createRedisDriver(array $config)
    {
        return new RedisBroadcaster(
            $this->app->make('redis'), $config['connection'] ?? null,
            $this->app['config']->get('database.redis.options.prefix', '')
        );
    }

    /**
     * Create an instance of the driver.
     *
     * @param  array  $config
     * @return \Illuminate\Contracts\Broadcasting\Broadcaster
     */
    protected function createLogDriver(array $config)
    {
        return new LogBroadcaster(
            $this->app->make(LoggerInterface::class)
        );
    }

    /**
     * Create an instance of the driver.
     *
     * @param  array  $config
     * @return \Illuminate\Contracts\Broadcasting\Broadcaster
     */
    protected function createMercureDriver(array $config)
    {
        $expiration = $this->mercureSubscribeExpiration($config);

        if ($expiration <= 0) {
            throw new InvalidArgumentException('The Mercure "subscribe_expiration" configuration value must be a positive number of minutes.');
        }

        $hub = $this->mercure($config);

        if ($hub->getFactory() === null) {
            throw new InvalidArgumentException('The Mercure broadcasting connection requires a "secret" (or "subscribe_secret") configuration value.');
        }

        return new MercureBroadcaster(
            $hub,
            $expiration,
            $this->mercureChannelEncrypter($config),
            (string) (($config['topic_prefix'] ?? null) ?: 'https://laravel.alt/echo/'),
            (bool) ($config['client_events'] ?? true),
        );
    }

    /**
     * Get a Mercure hub instance for the given configuration.
     *
     * The hub's token factory is the subscriber one, feeding the Mercure
     * Authorization helper; the publish token is minted by the hub's
     * token provider.
     *
     * @param  array  $config
     * @return \Symfony\Component\Mercure\HubInterface
     */
    public function mercure(array $config)
    {
        if (empty($config['url'])) {
            return $this->frankenPhpMercure($config);
        }

        // 0 (the default) delegates the lifetime to the hub's token factory:
        // "session.cookie_lifetime", or an hour when that setting is 0. A
        // set value must survive the truncation to whole seconds, so a
        // sub-second lifetime can't silently become the default one.
        $publishExpiration = (int) (($config['publish_expiration'] ?? 0) * 60);

        if ($publishExpiration < 0 || ($publishExpiration === 0 && ! empty($config['publish_expiration']))) {
            throw new InvalidArgumentException('The Mercure "publish_expiration" configuration value must be a positive number of minutes, or 0 to use the default lifetime.');
        }

        $publishTokenFactory = new DefaultClaimsTokenFactory(
            WebTokenFactory::fromSecret(
                $this->mercureSecret($config, 'publish'),
                $config['publish_algorithm'] ?? $config['algorithm'] ?? 'HS256',
                $publishExpiration,
                $config['publish_passphrase'] ?? $config['passphrase'] ?? '',
            ),
            $this->mercurePublishClaims($config),
        );

        return new Hub(
            $config['url'],
            new CachingTokenProvider(new FactoryTokenProvider($publishTokenFactory, [new Grant([Grant::ACTION_PUBLISH], ['*'])])),
            $this->mercureSubscribeFactory($config),
            ($config['public_url'] ?? null) ?: null,
            empty($config['client_options']) ? null : HttpClient::create($config['client_options']),
            ($config['cookie_name'] ?? null) ?: null,
            ProtocolVersion::V1,
        );
    }

    /**
     * Get a Mercure hub instance backed by FrankenPHP's built-in hub.
     *
     * FrankenPHP publishes in-process, so no publisher JWT, token provider
     * or HTTP client is involved; only the subscriber token factory is.
     *
     * @param  array  $config
     * @return \Symfony\Component\Mercure\HubInterface
     */
    protected function frankenPhpMercure(array $config)
    {
        if (! function_exists('mercure_publish')) {
            throw new InvalidArgumentException('The Mercure broadcasting connection requires a "url" configuration value, unless the application is served by FrankenPHP with its built-in Mercure hub enabled.');
        }

        return new FrankenPhpHub(
            ($config['public_url'] ?? null) ?: '/.well-known/mercure',
            $this->mercureSubscribeFactory($config),
            ($config['cookie_name'] ?? null) ?: null,
            ProtocolVersion::V1,
        );
    }

    /**
     * Get the subscriber token (and cookie) lifetime in whole seconds.
     *
     * Shared by the driver and the subscriber factory so the cookie and the
     * token it carries always expire together. Cast to int up front: a
     * fractional value would otherwise truncate silently downstream, and a
     * 0-second lifetime is treated by the hub as one hour.
     *
     * @param  array  $config
     * @return int
     */
    protected function mercureSubscribeExpiration(array $config)
    {
        return (int) (($config['subscribe_expiration'] ?? 5) * 60);
    }

    /**
     * Get the subscriber token factory for the given Mercure configuration,
     * or null when no subscriber secret is configured. The broadcast driver
     * itself rejects a factory-less hub; publish-only hubs are only
     * reachable through the public mercure() helper.
     *
     * @param  array  $config
     * @return \Symfony\Component\Mercure\Jwt\TokenFactoryInterface|null
     */
    protected function mercureSubscribeFactory(array $config)
    {
        if (empty($config['subscribe_secret']) && empty($config['secret'])) {
            return null;
        }

        $claims = $this->mercureClaims($config);
        // RFC 9068 requires "sub" unconditionally, even for a guest joining
        // public-only channels; an authenticated user's identity overrides
        // this default at call time (see MercureBroadcaster::makeAuthorizationCookie()).
        $claims['sub'] = ($claims['sub'] ?? null) ?: 'anonymous';

        return new DefaultClaimsTokenFactory(
            WebTokenFactory::fromSecret(
                $this->mercureSecret($config, 'subscribe'),
                $config['subscribe_algorithm'] ?? $config['algorithm'] ?? 'HS256',
                $this->mercureSubscribeExpiration($config),
                $config['subscribe_passphrase'] ?? $config['passphrase'] ?? '',
            ),
            $claims,
        );
    }

    /**
     * Get the end-to-end channel encrypter for the given Mercure
     * configuration, or null when no "encryption_key" is configured
     * (end-to-end encrypted channels then throw when used).
     *
     * Built eagerly so a missing "web-token/jwt-library" package or a
     * malformed key surfaces at driver resolution rather than mid-broadcast.
     *
     * @param  array  $config
     * @return \Illuminate\Broadcasting\MercureChannelEncrypter|null
     */
    protected function mercureChannelEncrypter(array $config)
    {
        if (empty($config['encryption_key'])) {
            return null;
        }

        $encodedKey = $config['encryption_key'];

        // Support the framework's key convention (what "key:generate
        // --show" produces), so an APP_KEY-style value works as-is.
        if (str_starts_with($encodedKey, 'base64:')) {
            $encodedKey = substr($encodedKey, 7);
        }

        $key = base64_decode($encodedKey, true);

        if ($key === false || strlen($key) !== 32) {
            throw new InvalidArgumentException('The Mercure "encryption_key" configuration value must be a base64-encoded 32-byte key. You may generate one with: php -r "echo base64_encode(random_bytes(32));"');
        }

        return new MercureChannelEncrypter($key);
    }

    /**
     * Get the additional JWT claims for the publisher side of the given
     * Mercure configuration.
     *
     * The publish token has no request/user to draw a "sub" from, so it
     * defaults to the app's own "client_id" identity.
     *
     * @param  array  $config
     * @return array
     */
    protected function mercurePublishClaims(array $config)
    {
        $claims = $this->mercureClaims($config);

        $claims['sub'] = ($claims['sub'] ?? null) ?: (($claims['client_id'] ?? null) ?: $config['url']);

        return $claims;
    }

    /**
     * Get the JWT secret for the given side ("subscribe" or "publish") of
     * the given Mercure configuration.
     *
     * @param  array  $config
     * @param  string  $side
     * @return string
     */
    protected function mercureSecret(array $config, string $side)
    {
        $secret = ($config[$side.'_secret'] ?? null) ?: ($config['secret'] ?? null);

        if (empty($secret)) {
            throw new InvalidArgumentException(sprintf(
                'The Mercure broadcasting connection requires a "secret" (or "%s_secret") configuration value.', $side
            ));
        }

        $algorithm = $config[$side.'_algorithm'] ?? $config['algorithm'] ?? 'HS256';

        // Enforced downstream with a bare "Invalid key length." at the first
        // token mint; failing here instead points at the configuration.
        $minimumLength = ['HS256' => 32, 'HS384' => 48, 'HS512' => 64][$algorithm] ?? 0;

        if (strlen($secret) < $minimumLength) {
            throw new InvalidArgumentException(sprintf(
                'The Mercure "secret" (or "%s_secret") configuration value must be at least %d bytes long to sign %s tokens.',
                $side, $minimumLength, $algorithm
            ));
        }

        return $secret;
    }

    /**
     * Get the additional JWT claims for the given Mercure configuration.
     *
     * RFC 9068 access tokens require "iss", "aud", and "client_id", so each
     * defaults to a sensible identifier when not set explicitly (an empty
     * string, e.g. from an unset .env value, counts as not set).
     *
     * @param  array  $config
     * @return array
     */
    protected function mercureClaims(array $config)
    {
        $claims = $config['claims'] ?? [];
        $appUrl = $this->app['config']['app.url'] ?? null;

        // A FrankenPHP hub has no "url": it falls back to the same public
        // endpoint the browser subscribes to.
        $url = ($config['url'] ?? null) ?: (($config['public_url'] ?? null) ?: '/.well-known/mercure');

        if (empty($config['url']) && empty($config['public_url']) && $appUrl) {
            // The default endpoint is root-relative, but the hub validates
            // an absolute audience. Do not include the application's path.
            $origin = parse_url($appUrl);

            if (isset($origin['scheme'], $origin['host'])) {
                $url = $origin['scheme'].'://'.$origin['host']
                    .(isset($origin['port']) ? ':'.$origin['port'] : '').$url;
            }
        }

        $claims['aud'] = ($claims['aud'] ?? null) ?: (($config['public_url'] ?? null) ?: $url);
        $claims['iss'] = ($claims['iss'] ?? null) ?: ($appUrl ?: $url);
        $claims['client_id'] = ($claims['client_id'] ?? null) ?: $claims['iss'];

        return $claims;
    }

    /**
     * Create an instance of the driver.
     *
     * @param  array  $config
     * @return \Illuminate\Contracts\Broadcasting\Broadcaster
     */
    protected function createNullDriver(array $config)
    {
        return new NullBroadcaster;
    }

    /**
     * Get the connection configuration.
     *
     * @param  string  $name
     * @return array
     */
    protected function getConfig($name)
    {
        if (! is_null($name) && $name !== 'null') {
            return $this->app['config']["broadcasting.connections.{$name}"];
        }

        return ['driver' => 'null'];
    }

    /**
     * Get the default driver name.
     *
     * @return string
     */
    public function getDefaultDriver()
    {
        return $this->app['config']['broadcasting.default'] ?? 'null';
    }

    /**
     * Set the default driver name.
     *
     * @param  \UnitEnum|string  $name
     * @return void
     */
    public function setDefaultDriver($name)
    {
        $this->app['config']['broadcasting.default'] = enum_value($name);
    }

    /**
     * Disconnect the given driver / connection and remove it from local cache.
     *
     * @param  \UnitEnum|string|null  $name
     * @return void
     */
    public function purge($name = null)
    {
        $name = enum_value($name) ?? $this->getDefaultDriver();

        unset($this->drivers[$name]);
    }

    /**
     * Register a custom driver creator Closure.
     *
     * @param  string  $driver
     * @param  \Closure  $callback
     *
     * @param-closure-this  $this  $callback
     *
     * @return $this
     */
    public function extend($driver, Closure $callback)
    {
        try {
            $callback = $this->bindCallbackToSelf($callback) ?? throw new RuntimeException('Unable to bind custom driver callback');
        } catch (ReflectionException $e) {
            throw new RuntimeException('Unable to bind custom driver callback', previous: $e);
        }

        $this->customCreators[$driver] = $callback;

        return $this;
    }

    /**
     * Execute the given callback using "rescue" if possible.
     *
     * @param  \Closure  $callback
     * @return mixed
     */
    protected function rescue(Closure $callback)
    {
        if (function_exists('rescue')) {
            return rescue($callback);
        }

        return $callback();
    }

    /**
     * Get the application instance used by the manager.
     *
     * @return \Illuminate\Contracts\Foundation\Application
     */
    public function getApplication()
    {
        return $this->app;
    }

    /**
     * Set the application instance used by the manager.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @return $this
     */
    public function setApplication($app)
    {
        $this->app = $app;

        return $this;
    }

    /**
     * Forget all of the resolved driver instances.
     *
     * @return $this
     */
    public function forgetDrivers()
    {
        $this->drivers = [];

        return $this;
    }

    /**
     * Dynamically call the default driver instance.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->driver()->$method(...$parameters);
    }
}
