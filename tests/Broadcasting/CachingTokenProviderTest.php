<?php

namespace Illuminate\Tests\Broadcasting;

use Illuminate\Broadcasting\Mercure\CachingTokenProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mercure\Jwt\TokenProviderInterface;

class CachingTokenProviderTest extends TestCase
{
    public function testItMintsOnceWhileTheTokenIsFresh()
    {
        $provider = new CachingTokenProvider(new CountingTokenProvider(
            $this->tokenWithClaims(['exp' => time() + 3600])
        ));

        $first = $provider->getJwt();

        $this->assertSame($first, $provider->getJwt());
        $this->assertSame($first, $provider->getJwt());
    }

    public function testItReMintsOnceTheTokenNearsItsExpiry()
    {
        $inner = new CountingTokenProvider($this->tokenWithClaims(['exp' => time() + 10]));

        $provider = new CachingTokenProvider($inner, 30);

        $provider->getJwt();
        $provider->getJwt();

        $this->assertSame(2, $inner->calls);
    }

    public function testATokenWithoutExpIsCachedForever()
    {
        $inner = new CountingTokenProvider($this->tokenWithClaims(['sub' => 'app']));

        $provider = new CachingTokenProvider($inner);

        $provider->getJwt();
        $provider->getJwt();

        $this->assertSame(1, $inner->calls);
    }

    protected function tokenWithClaims(array $claims)
    {
        $encode = fn ($data) => rtrim(strtr(base64_encode(json_encode($data)), '+/', '-_'), '=');

        return $encode(['alg' => 'none']).'.'.$encode($claims).'.';
    }
}

class CountingTokenProvider implements TokenProviderInterface
{
    public $calls = 0;

    public function __construct(protected string $jwt)
    {
    }

    public function getJwt(): string
    {
        $this->calls++;

        return $this->jwt;
    }
}
