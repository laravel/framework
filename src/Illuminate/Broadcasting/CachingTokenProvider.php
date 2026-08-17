<?php

namespace Illuminate\Broadcasting;

use Symfony\Component\Mercure\Jwt\TokenProviderInterface;

/**
 * @author Kévin Dunglas <kevin@dunglas.dev>
 */
class CachingTokenProvider implements TokenProviderInterface
{
    /**
     * The cached token.
     *
     * @var string|null
     */
    protected $jwt;

    /**
     * The Unix timestamp after which the cached token must be re-minted.
     *
     * @var int
     */
    protected $refreshAfter = 0;

    /**
     * Create a new caching token provider instance.
     *
     * Reuses the minted token until shortly before its "exp" claim (or
     * forever, when it carries none), avoiding a re-sign on every broadcast.
     *
     * @param  \Symfony\Component\Mercure\Jwt\TokenProviderInterface  $provider
     * @param  int  $clockSkew
     */
    public function __construct(
        protected TokenProviderInterface $provider,
        protected int $clockSkew = 30,
    ) {
    }

    /**
     * Get the JWT, minting a fresh one when the cached token nears expiry.
     */
    public function getJwt(): string
    {
        if ($this->jwt !== null && time() < $this->refreshAfter) {
            return $this->jwt;
        }

        $this->jwt = $this->provider->getJwt();
        $this->refreshAfter = ($this->expiresAt($this->jwt) ?? PHP_INT_MAX) - $this->clockSkew;

        return $this->jwt;
    }

    /**
     * Extract the "exp" claim from the given JWT, if any.
     *
     * @param  string  $jwt
     * @return int|null
     */
    protected function expiresAt($jwt)
    {
        $payload = explode('.', $jwt)[1] ?? '';

        $claims = json_decode(base64_decode(strtr($payload, '-_', '+/')) ?: 'null', true);

        return isset($claims['exp']) ? (int) $claims['exp'] : null;
    }
}
