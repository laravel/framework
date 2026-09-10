<?php

namespace Illuminate\Broadcasting\Mercure;

use Illuminate\Broadcasting\Broadcasters\MercureBroadcaster;
use InvalidArgumentException;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\Mercure\FrankenPhpHub;
use Symfony\Component\Mercure\Hub;
use Symfony\Component\Mercure\Jwt\DefaultClaimsTokenFactory;
use Symfony\Component\Mercure\Jwt\FactoryTokenProvider;
use Symfony\Component\Mercure\Jwt\Grant;
use Symfony\Component\Mercure\Jwt\WebTokenFactory;
use Symfony\Component\Mercure\ProtocolVersion;

trait CreatesMercureDrivers
{
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

        if (str_starts_with($hub->getCookieName(), '__') &&
            parse_url($hub->getPublicUrl(), PHP_URL_SCHEME) === 'http') {
            throw new InvalidArgumentException(sprintf('The Mercure "%s" cookie requires an "https" hub "public_url". Use HTTPS, or configure a "cookie_name" without the "__Secure-" or "__Host-" prefix for plain-HTTP development.', $hub->getCookieName()));
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
     * @param  array  $config
     * @return \Symfony\Component\Mercure\HubInterface
     */
    public function mercure(array $config)
    {
        if (empty($config['url'])) {
            return $this->frankenPhpMercure($config);
        }

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
     * @param  array  $config
     * @return int
     */
    protected function mercureSubscribeExpiration(array $config)
    {
        return (int) (($config['subscribe_expiration'] ?? 5) * 60);
    }

    /**
     * Get the subscriber token factory for the given Mercure configuration.
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
     * Get the end-to-end channel encrypter for the given Mercure configuration.
     *
     * @param  array  $config
     * @return \Illuminate\Broadcasting\Mercure\ChannelEncrypter|null
     */
    protected function mercureChannelEncrypter(array $config)
    {
        if (empty($config['encryption_key'])) {
            return null;
        }

        $encodedKey = $config['encryption_key'];

        if (str_starts_with($encodedKey, 'base64:')) {
            $encodedKey = substr($encodedKey, 7);
        }

        $key = base64_decode($encodedKey, true);

        if ($key === false || strlen($key) !== 32) {
            throw new InvalidArgumentException('The Mercure "encryption_key" configuration value must be a base64-encoded 32-byte key. You may generate one with: php -r "echo base64_encode(random_bytes(32));"');
        }

        return new ChannelEncrypter($key);
    }

    /**
     * Get the additional JWT claims for the publisher side of the given Mercure configuration.
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
     * Get the JWT secret for the given side ("subscribe" or "publish") of the given Mercure configuration.
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
     * @param  array  $config
     * @return array
     */
    protected function mercureClaims(array $config)
    {
        $claims = $config['claims'] ?? [];
        $appUrl = $this->app['config']['app.url'] ?? null;
        $url = ($config['url'] ?? null) ?: (($config['public_url'] ?? null) ?: '/.well-known/mercure');

        if (empty($config['url']) && empty($config['public_url']) && $appUrl) {
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
}
