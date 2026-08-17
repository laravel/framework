<?php

namespace Illuminate\Tests\Integration\Broadcasting;

use Illuminate\Broadcasting\Broadcasters\MercureBroadcaster;
use Illuminate\Broadcasting\BroadcastManager;
use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Mercure\FrankenPhpHub;
use Symfony\Component\Mercure\ProtocolVersion;

// The "mercure_publish()" stub these tests need is process-wide, and its mere
// presence changes how a "url"-less Mercure connection resolves.
#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
class MercureFrankenPhpHubTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        require_once __DIR__.'/fixtures/mercure_publish.php';

        $GLOBALS['mercure_published'] = [];
    }

    public function testAMissingUrlResolvesToTheBuiltInHub()
    {
        $hub = (new BroadcastManager($this->getApp([])))->mercure(['secret' => str_repeat('s', 32)]);

        $this->assertInstanceOf(FrankenPhpHub::class, $hub);
        $this->assertSame('/.well-known/mercure', $hub->getPublicUrl());
        $this->assertSame(ProtocolVersion::V1, $hub->getProtocolVersion());
        $this->assertSame('__Secure-mercure_access_token', $hub->getCookieName());
        $this->assertNotNull($hub->getFactory());
    }

    public function testTheBuiltInHubHonorsThePublicUrlAndCookieName()
    {
        $hub = (new BroadcastManager($this->getApp([])))->mercure([
            'secret' => str_repeat('s', 32),
            'public_url' => 'http://localhost/.well-known/mercure',
            'cookie_name' => 'mercureAuthorization',
        ]);

        $this->assertSame('http://localhost/.well-known/mercure', $hub->getPublicUrl());
        $this->assertSame('mercureAuthorization', $hub->getCookieName());
    }

    public function testTheBuiltInHubNeedsNoPublishSecret()
    {
        $manager = new BroadcastManager($this->getApp([
            'broadcasting' => ['connections' => ['mercure' => [
                'driver' => 'mercure',
                'subscribe_secret' => str_repeat('a', 32),
            ]]],
        ]));

        $broadcaster = $manager->connection('mercure');

        $this->assertInstanceOf(MercureBroadcaster::class, $broadcaster);
        $this->assertInstanceOf(FrankenPhpHub::class, $broadcaster->getHub());
    }

    public function testTheBuiltInHubDefaultsTheRfc9068Claims()
    {
        $manager = new BroadcastManager($this->getApp(['app' => ['url' => 'https://app.test']]));

        $hub = $manager->mercure(['secret' => str_repeat('s', 32)]);

        $claims = $this->decodeJwtClaims($hub->getFactory()->create());

        $this->assertSame('https://app.test', $claims['iss']);
        $this->assertSame('https://app.test', $claims['client_id']);
        $this->assertSame('/.well-known/mercure', $claims['aud']);
        $this->assertSame('anonymous', $claims['sub']);
    }

    public function testTheBuiltInHubAudienceFallsBackToThePublicUrl()
    {
        $manager = new BroadcastManager($this->getApp(['app' => ['url' => 'https://app.test']]));

        $hub = $manager->mercure([
            'secret' => str_repeat('s', 32),
            'public_url' => 'https://app.test/.well-known/mercure',
        ]);

        $claims = $this->decodeJwtClaims($hub->getFactory()->create());

        $this->assertSame('https://app.test/.well-known/mercure', $claims['aud']);
    }

    public function testBroadcastingPublishesThroughTheBuiltInHub()
    {
        $manager = new BroadcastManager($this->getApp([
            'broadcasting' => ['connections' => ['mercure' => [
                'driver' => 'mercure',
                'secret' => str_repeat('s', 32),
            ]]],
        ]));

        $manager->connection('mercure')->broadcast(['orders', 'private-orders.1'], 'OrderShipped', ['id' => 1]);

        $this->assertCount(2, $GLOBALS['mercure_published']);

        $this->assertSame(['https://laravel.alt/echo/channel/orders'], $GLOBALS['mercure_published'][0]['topics']);
        $this->assertFalse($GLOBALS['mercure_published'][0]['private']);

        $this->assertSame(['https://laravel.alt/echo/channel/private-orders.1'], $GLOBALS['mercure_published'][1]['topics']);
        $this->assertTrue($GLOBALS['mercure_published'][1]['private']);

        $this->assertSame([
            'channels' => ['orders'],
            'event' => 'OrderShipped',
            'payload' => ['id' => 1],
        ], json_decode($GLOBALS['mercure_published'][0]['data'], true));
    }

    protected function decodeJwtClaims(string $jwt): array
    {
        $payload = explode('.', $jwt)[1];

        return json_decode(base64_decode(strtr($payload, '-_', '+/')), true);
    }

    protected function getApp(array $userConfig)
    {
        $app = new Container;
        $app->singleton('config', fn () => new Repository($userConfig));

        return $app;
    }
}
