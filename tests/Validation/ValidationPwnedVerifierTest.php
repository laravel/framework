<?php

namespace Illuminate\Tests\Validation;

use Mockery as m;
use GuzzleHttp\ClientInterface;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\ResponseInterface;
use Illuminate\Validation\PwnedVerifier;
use GuzzleHttp\Exception\TransferException;

class ValidationPwnedVerifierTest extends TestCase
{
    private const PWNED_PASS = 'password';
    private const PWNED_PASS_HASH_PREFIX = '5BAA6';
    private const NOT_PWNED_PASS = 'ayfqM7YnNvkyNaF';
    private const NOT_PWNED_PASS_HASH_PREFIX = '4D5EA';

    private const HASH_LIST = [
        '2471B4FBA2AC638B7E5F6F758F2D726D3BB:0', // not pwned
        'D66A63D4BF1747940578EC3D0103530E21D:13',
        '1E4C9B93F3F0682250B6CF8331B7EE68FD8:20', // pwned
        'A43BB67901CC0CB207E6BCF13A606611CF7:4',
        '4D75252D9788CE20300D00023FA1CCD4D19:5',
    ];

    protected function tearDown(): void
    {
        m::close();
    }

    public function testItIsPwned()
    {
        $verifier = new PwnedVerifier($httpClient = m::mock(ClientInterface::class));

        $httpClient->shouldReceive('request')
                   ->once()
                   ->with('GET', sprintf(PwnedVerifier::PWNED_API, self::PWNED_PASS_HASH_PREFIX))
                   ->andReturn($response = m::mock(ResponseInterface::class));

        $response->shouldReceive('getBody')
                 ->once()
                 ->andReturn($body = m::mock(StreamInterface::class));

        $body->shouldReceive('getContents')
             ->once()
             ->andReturn($contents = implode("\r\n", self::HASH_LIST));

        $this->assertEquals(true, $verifier->isPwned(self::PWNED_PASS));
    }

    public function testItIsNotPwned()
    {
        $verifier = new PwnedVerifier($httpClient = m::mock(ClientInterface::class));

        $httpClient->shouldReceive('request')
            ->once()
            ->with('GET', sprintf(PwnedVerifier::PWNED_API, self::NOT_PWNED_PASS_HASH_PREFIX))
            ->andReturn($response = m::mock(ResponseInterface::class));

        $response->shouldReceive('getBody')
            ->once()
            ->andReturn($body = m::mock(StreamInterface::class));

        $body->shouldReceive('getContents')
            ->once()
            ->andReturn($contents = implode("\r\n", self::HASH_LIST));

        $this->assertEquals(false, $verifier->isPwned(self::NOT_PWNED_PASS));
    }

    public function testItIsNotPwnedUsingThreshold()
    {
        $verifier = new PwnedVerifier($httpClient = m::mock(ClientInterface::class));

        $httpClient->shouldReceive('request')
            ->once()
            ->with('GET', sprintf(PwnedVerifier::PWNED_API, self::PWNED_PASS_HASH_PREFIX))
            ->andReturn($response = m::mock(ResponseInterface::class));

        $response->shouldReceive('getBody')
            ->once()
            ->andReturn($body = m::mock(StreamInterface::class));

        $body->shouldReceive('getContents')
            ->once()
            ->andReturn($contents = implode("\r\n", self::HASH_LIST));

        $this->assertEquals(false, $verifier->isPwned(self::PWNED_PASS, 21));
    }

    public function testItThrowsAnError()
    {
        $verifier = new PwnedVerifier($httpClient = m::mock(ClientInterface::class));

        $httpClient->shouldReceive('request')
            ->once()
            ->with('GET', sprintf(PwnedVerifier::PWNED_API, self::PWNED_PASS_HASH_PREFIX))
            ->andThrows(TransferException::class);

        $this->expectException(TransferException::class);
        $verifier->isPwned('password');
    }

    public function testItDoesNotThrowAnError()
    {
        $verifier = new PwnedVerifier($httpClient = m::mock(ClientInterface::class));

        $httpClient->shouldReceive('request')
                   ->once()
                   ->with('GET', sprintf(PwnedVerifier::PWNED_API, self::PWNED_PASS_HASH_PREFIX))
                   ->andThrows(TransferException::class);

        $this->assertEquals(false, $verifier->isPwned('password', 1, true));
    }
}
