<?php

namespace Illuminate\Tests\Broadcasting;

use Illuminate\Broadcasting\Broadcasters\MercureBroadcaster;
use Illuminate\Broadcasting\BroadcastException;
use Illuminate\Broadcasting\MercureChannelEncrypter;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Http\Request;
use Jose\Component\Core\AlgorithmManager;
use Jose\Component\Core\JWK;
use Jose\Component\Encryption\Algorithm\ContentEncryption\A256GCM;
use Jose\Component\Encryption\Algorithm\KeyEncryption\Dir;
use Jose\Component\Encryption\JWEDecrypter;
use Jose\Component\Encryption\Serializer\CompactSerializer;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Mercure\Exception\InvalidArgumentException as MercureInvalidArgumentException;
use Symfony\Component\Mercure\Exception\RuntimeException as MercureRuntimeException;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Jwt\DefaultClaimsTokenFactory;
use Symfony\Component\Mercure\Jwt\WebTokenFactory;
use Symfony\Component\Mercure\Update;

class MercureBroadcasterTest extends TestCase
{
    /**
     * @var \Illuminate\Broadcasting\Broadcasters\MercureBroadcaster
     */
    public $broadcaster;

    public $hub;

    protected function setUp(): void
    {
        parent::setUp();

        $this->hub = m::mock(HubInterface::class);
        $this->hub->shouldReceive('getPublicUrl')->andReturn('https://localhost/.well-known/mercure');
        $this->hub->shouldReceive('getCookieName')->andReturn('__Secure-mercure_access_token');
        $this->hub->shouldReceive('getFactory')->andReturn($this->tokenFactory());

        $this->broadcaster = new MercureBroadcaster($this->hub);
    }

    protected function tokenFactory()
    {
        return new DefaultClaimsTokenFactory(
            WebTokenFactory::fromSecret(
                'this-is-a-very-long-secret-used-for-hmac-sha256-signing!!',
                'HS256',
                300,
            ),
            [
                'iss' => 'https://app.example.com',
                'aud' => 'https://localhost/.well-known/mercure',
                'client_id' => 'test-app',
                'sub' => 'anonymous',
            ],
        );
    }

    protected function tearDown(): void
    {
        m::close();
        EncryptCookies::flushState();

        parent::tearDown();
    }

    public function testConstructingRegistersTheHubsCookieNameAsNeverEncrypted()
    {
        $neverEncrypt = (new ReflectionClass(EncryptCookies::class))
            ->getProperty('neverEncrypt')
            ->getValue();

        $this->assertContains('__Secure-mercure_access_token', $neverEncrypt);
    }

    public function testSettingAHubRegistersItsCookieNameAsNeverEncrypted()
    {
        $hub = m::mock(HubInterface::class);
        $hub->shouldReceive('getCookieName')->andReturn('custom_mercure_cookie');

        $this->broadcaster->setHub($hub);

        $neverEncrypt = (new ReflectionClass(EncryptCookies::class))
            ->getProperty('neverEncrypt')
            ->getValue();

        $this->assertContains('custom_mercure_cookie', $neverEncrypt);
    }

    public function testAuthThrowsAccessDeniedWhenNoChannelIsRequested()
    {
        $this->expectException(AccessDeniedHttpException::class);

        $this->broadcaster->auth($this->requestFor([], 1));
    }

    public function testAuthThrowsAccessDeniedForNonStringChannelNames()
    {
        $this->expectException(AccessDeniedHttpException::class);

        $this->broadcaster->auth($this->requestFor([['nested']], 1));
    }

    public function testAuthThrowsAccessDeniedForOversizedChannelBatches()
    {
        $this->expectException(AccessDeniedHttpException::class);

        $this->broadcaster->auth($this->requestFor(array_map(fn ($i) => 'news.'.$i, range(1, 101)), 1));
    }

    public function testAuthDeduplicatesRequestedChannels()
    {
        $this->broadcaster->channel('room.1', fn () => true);

        $response = $this->broadcaster->auth($this->requestFor(['private-room.1', 'private-room.1'], 42));

        $this->assertSame([['name' => 'private-room.1']], $response->getData(true)['channel_names']);

        $claims = $this->decodeJwtClaims($this->cookieValue($response));

        $this->assertSame([['match' => 'https://laravel.alt/echo/channel/private-room.1']], $claims['authorization_details'][0]['topics']);
        $this->assertSame([['match' => 'https://laravel.alt/echo/whisper/private-room.1']], $claims['authorization_details'][1]['topics']);
    }

    public function testAuthStillMintsATokenWithNoGrantsWhenEveryChannelIsPublic()
    {
        $response = $this->broadcaster->auth($this->requestFor(['news'], 1));

        $this->assertSame([['name' => 'news']], $response->getData(true)['channel_names']);
        $this->assertSame(300, $response->getData(true)['expires_in']);
        $this->assertSame('https://laravel.alt/echo/', $response->getData(true)['topic_prefix']);
        $this->assertTrue($response->getData(true)['client_events']);

        $claims = $this->decodeJwtClaims($this->cookieValue($response));

        $this->assertArrayNotHasKey('authorization_details', $claims);
    }

    public function testAuthThrowsAccessDeniedWhenUserIsMissingForAGuardedChannel()
    {
        $this->expectException(AccessDeniedHttpException::class);

        $this->broadcaster->auth($this->requestFor(['private-room.1'], null));
    }

    public function testAuthThrowsAccessDeniedWhenTheChannelCallbackReturnsFalse()
    {
        $this->expectException(AccessDeniedHttpException::class);

        $this->broadcaster->channel('room.1', fn () => false);

        $this->broadcaster->auth($this->requestFor(['private-room.1'], 42));
    }

    public function testAuthExcludesPublicChannelsAndGrantsOnlyGuardedOnes()
    {
        $this->broadcaster->channel('room.1', fn () => true);

        $response = $this->broadcaster->auth($this->requestFor(['news', 'private-room.1'], 42));

        $this->assertSame([['name' => 'news'], ['name' => 'private-room.1']], $response->getData(true)['channel_names']);

        $claims = $this->decodeJwtClaims($this->cookieValue($response));

        $this->assertSame([
            [
                'type' => 'https://mercure.rocks/authorization-detail',
                'actions' => ['subscribe'],
                'topics' => [['match' => 'https://laravel.alt/echo/channel/private-room.1']],
            ],
            [
                'type' => 'https://mercure.rocks/authorization-detail',
                'actions' => ['subscribe', 'publish'],
                'topics' => [['match' => 'https://laravel.alt/echo/whisper/private-room.1']],
            ],
        ], $claims['authorization_details']);
        $this->assertSame('42', $claims['sub']);
        $this->assertSame('https://app.example.com', $claims['iss']);
    }

    public function testAuthGrantsPresenceChannelWithPayloadAndSubscriptionEventsPattern()
    {
        $this->broadcaster->channel('room.1', fn ($user) => ['id' => $user->getAuthIdentifier(), 'name' => 'alice']);

        $response = $this->broadcaster->auth($this->requestFor(['presence-room.1'], 42));

        $claims = $this->decodeJwtClaims($this->cookieValue($response));
        $detail = $claims['authorization_details'][0];

        $this->assertSame(['id' => 42, 'name' => 'alice'], $detail['payload']);
        $this->assertSame([
            ['match' => 'https://laravel.alt/echo/channel/presence-room.1'],
            [
                'match' => '/.well-known/mercure/subscriptions/:match_type/https%3A%2F%2Flaravel.alt%2Fecho%2Fchannel%2Fpresence-room.1{/:subscriber}?',
                'match_type' => 'urlpattern',
            ],
        ], $detail['topics']);
    }

    public function testAuthSetsTheCookieProvidedByTheHub()
    {
        $this->broadcaster->channel('room.1', fn () => true);

        $response = $this->broadcaster->auth($this->requestFor(['private-room.1'], 42));

        $cookie = $response->headers->getCookies()[0];

        $this->assertSame('__Secure-mercure_access_token', $cookie->getName());
        $this->assertSame('/.well-known/mercure', $cookie->getPath());
        $this->assertTrue($cookie->isHttpOnly());
        $this->assertSame('strict', $cookie->getSameSite());
    }

    public function testBroadcastPublishesOneUnprivatedUpdateWhenEveryChannelIsPublic()
    {
        $this->hub->shouldReceive('publish')->once()->with(m::on(function (Update $update) {
            $data = json_decode($update->getData(), true);

            return $update->getTopics() === ['https://laravel.alt/echo/channel/news', 'https://laravel.alt/echo/channel/weather']
                && ! $update->isPrivate()
                && $data['channels'] === ['news', 'weather'];
        }));

        $this->broadcaster->broadcast(['news', 'weather'], 'Tick', ['time' => 'now']);
    }

    public function testBroadcastPublishesOnePrivateUpdatePerGuardedChannel()
    {
        foreach (['private-room.1', 'presence-room.2'] as $channel) {
            $this->hub->shouldReceive('publish')->once()->with(m::on(function (Update $update) use ($channel) {
                $data = json_decode($update->getData(), true);

                return $update->getTopics() === ['https://laravel.alt/echo/channel/'.$channel]
                    && $update->isPrivate()
                    && $data['channels'] === [$channel];
            }));
        }

        $this->broadcaster->broadcast(['private-room.1', 'presence-room.2'], 'MessageSent', []);
    }

    public function testBroadcastStripsTheSocketKeyFromThePayloadAndEmbedsItInTheEnvelope()
    {
        $this->hub->shouldReceive('publish')->once()->with(m::on(function (Update $update) {
            $data = json_decode($update->getData(), true);

            return $data['socket'] === 'abcd.1234' && $data['payload'] === ['text' => 'hi'];
        }));

        $this->broadcaster->broadcast(['news'], 'MessageSent', ['text' => 'hi', 'socket' => 'abcd.1234']);
    }

    public function testBroadcastOmitsTheSocketKeyFromTheEnvelopeWhenAbsent()
    {
        $this->hub->shouldReceive('publish')->once()->with(m::on(function (Update $update) {
            return ! array_key_exists('socket', json_decode($update->getData(), true));
        }));

        $this->broadcaster->broadcast(['news'], 'Tick', ['time' => 'now']);
    }

    public function testBroadcastSplitsAMixedBatchIntoTwoUpdates()
    {
        $this->hub->shouldReceive('publish')->once()->with(m::on(function (Update $update) {
            return $update->getTopics() === ['https://laravel.alt/echo/channel/news'] && ! $update->isPrivate();
        }));

        $this->hub->shouldReceive('publish')->once()->with(m::on(function (Update $update) {
            return $update->getTopics() === ['https://laravel.alt/echo/channel/private-room.1'] && $update->isPrivate();
        }));

        $this->broadcaster->broadcast(['news', 'private-room.1'], 'MessageSent', ['text' => 'hi']);
    }

    public function testTopicsEncodeChannelNamesIntoASinglePathSegment()
    {
        $this->hub->shouldReceive('publish')->once()->with(m::on(function (Update $update) {
            return $update->getTopics() === ['https://laravel.alt/echo/channel/order%2F1%20%2A%27%28%29%21'];
        }));

        $this->broadcaster->broadcast(['order/1 *\'()!'], 'Tick');
    }

    public function testBroadcastWrapsHubExceptionsIntoABroadcastException()
    {
        $this->expectException(BroadcastException::class);

        $this->hub->shouldReceive('publish')->andThrow(
            new MercureRuntimeException('unreachable')
        );

        $this->broadcaster->broadcast(['news'], 'Tick');
    }

    public function testBroadcastSurfacesTheUnderlyingCauseAndChainsTheHubException()
    {
        $hubException = new MercureRuntimeException(
            'Failed to send an update.', 0, new RuntimeException('HTTP/2 401 from the hub')
        );

        $this->hub->shouldReceive('publish')->andThrow($hubException);

        try {
            $this->broadcaster->broadcast(['news'], 'Tick');
            $this->fail('A BroadcastException should have been thrown.');
        } catch (BroadcastException $e) {
            $this->assertSame('Mercure error: HTTP/2 401 from the hub.', $e->getMessage());
            $this->assertSame($hubException, $e->getPrevious());
        }
    }

    public function testBroadcastWrapsBuiltInHubRuntimeExceptionsIntoABroadcastException()
    {
        $hubException = new RuntimeException('No Mercure hub configured');

        $this->hub->shouldReceive('publish')->andThrow($hubException);

        try {
            $this->broadcaster->broadcast(['news'], 'Tick');
            $this->fail('A BroadcastException should have been thrown.');
        } catch (BroadcastException $e) {
            $this->assertSame('Mercure error: No Mercure hub configured.', $e->getMessage());
            $this->assertSame($hubException, $e->getPrevious());
        }
    }

    public function testAuthMintsAnAnonymousTokenForAGuestOnPublicOnlyChannels()
    {
        $response = $this->broadcaster->auth($this->requestFor(['news'], null));

        $claims = $this->decodeJwtClaims($this->cookieValue($response));

        $this->assertSame('anonymous', $claims['sub']);
        $this->assertArrayNotHasKey('authorization_details', $claims);
    }

    public function testAuthGrantsEachPresenceChannelItsOwnPayload()
    {
        $this->broadcaster->channel('room.{room}', fn ($user, $room) => ['id' => $user->getAuthIdentifier(), 'room' => $room]);
        $this->broadcaster->channel('inbox.{id}', fn () => true);

        $response = $this->broadcaster->auth(
            $this->requestFor(['presence-room.1', 'presence-room.2', 'private-inbox.42'], 42)
        );

        $claims = $this->decodeJwtClaims($this->cookieValue($response));
        $details = $claims['authorization_details'];

        $this->assertCount(4, $details);

        $this->assertSame([['match' => 'https://laravel.alt/echo/channel/private-inbox.42']], $details[0]['topics']);
        $this->assertArrayNotHasKey('payload', $details[0]);

        $this->assertSame([
            ['match' => 'https://laravel.alt/echo/channel/presence-room.1'],
            ['match' => '/.well-known/mercure/subscriptions/:match_type/https%3A%2F%2Flaravel.alt%2Fecho%2Fchannel%2Fpresence-room.1{/:subscriber}?', 'match_type' => 'urlpattern'],
        ], $details[1]['topics']);
        $this->assertSame(['id' => 42, 'room' => '1'], $details[1]['payload']);

        $this->assertSame([
            ['match' => 'https://laravel.alt/echo/channel/presence-room.2'],
            ['match' => '/.well-known/mercure/subscriptions/:match_type/https%3A%2F%2Flaravel.alt%2Fecho%2Fchannel%2Fpresence-room.2{/:subscriber}?', 'match_type' => 'urlpattern'],
        ], $details[2]['topics']);
        $this->assertSame(['id' => 42, 'room' => '2'], $details[2]['payload']);

        $this->assertSame(['subscribe', 'publish'], $details[3]['actions']);
        $this->assertSame([
            ['match' => 'https://laravel.alt/echo/whisper/presence-room.1'],
            ['match' => 'https://laravel.alt/echo/whisper/presence-room.2'],
            ['match' => 'https://laravel.alt/echo/whisper/private-inbox.42'],
        ], $details[3]['topics']);
        $this->assertArrayNotHasKey('payload', $details[3]);
    }

    public function testAuthDeniesAGuestBeforeRevealingEncryptionConfigurationState()
    {
        $this->expectException(AccessDeniedHttpException::class);

        $this->broadcaster->auth($this->requestFor(['private-encrypted-room.1'], null));
    }

    public function testCookieSubMatchesTheChannelGuardResolvedUser()
    {
        $this->broadcaster->channel('room.1', fn () => true, ['guards' => ['admin']]);

        $request = Request::create('/broadcasting/auth', 'POST', ['channel_names' => ['private-room.1']]);
        $request->setUserResolver(fn ($guard = null) => $guard === 'admin' ? new GenericBroadcastingTestUser(7) : null);

        $response = $this->broadcaster->auth($request);

        $claims = $this->decodeJwtClaims($this->cookieValue($response));

        $this->assertSame('7', $claims['sub']);
    }

    public function testAuthRejectsEncryptedChannelsWithoutAnEncryptionKey()
    {
        $this->broadcaster->channel('room.1', fn () => true);

        try {
            $this->broadcaster->auth($this->requestFor(['private-encrypted-room.1'], 42));
            $this->fail('A BroadcastException should have been thrown.');
        } catch (BroadcastException $e) {
            $this->assertStringContainsString('encryption_key', $e->getMessage());
        }
    }

    public function testBroadcastRejectsEncryptedChannelsWithoutAnEncryptionKey()
    {
        try {
            $this->broadcaster->broadcast(['private-encrypted-room.1'], 'MessageSent');
            $this->fail('A BroadcastException should have been thrown.');
        } catch (BroadcastException $e) {
            $this->assertStringContainsString('encryption_key', $e->getMessage());
        }
    }

    public function testAuthReturnsAJwkForEachEncryptedChannel()
    {
        $broadcaster = $this->encryptedBroadcaster();
        $broadcaster->channel('orders.{id}', fn () => true);
        $broadcaster->channel('room.1', fn () => true);

        $response = $broadcaster->auth($this->requestFor(['private-encrypted-orders.1', 'private-room.1'], 42));

        $channels = $response->getData(true)['channel_names'];

        $this->assertSame([
            'name' => 'private-encrypted-orders.1',
            'jwk' => [
                'kty' => 'oct',
                'k' => rtrim(strtr(base64_encode(hash_hkdf('sha256', $this->encryptionKey(), 32, 'private-encrypted-orders.1')), '+/', '-_'), '='),
                'alg' => 'A256GCM',
                'use' => 'enc',
            ],
        ], $channels[0]);
        $this->assertSame(['name' => 'private-room.1'], $channels[1]);
    }

    public function testAuthGrantsEncryptedChannelsInTheSharedPrivateGrant()
    {
        $broadcaster = $this->encryptedBroadcaster();
        $broadcaster->channel('orders.{id}', fn () => true);
        $broadcaster->channel('room.1', fn () => true);

        $response = $broadcaster->auth($this->requestFor(['private-room.1', 'private-encrypted-orders.1'], 42));

        $claims = $this->decodeJwtClaims($this->cookieValue($response));

        $this->assertSame([
            [
                'type' => 'https://mercure.rocks/authorization-detail',
                'actions' => ['subscribe'],
                'topics' => [
                    ['match' => 'https://laravel.alt/echo/channel/private-room.1'],
                    ['match' => 'https://laravel.alt/echo/channel/private-encrypted-orders.1'],
                ],
            ],
            [
                'type' => 'https://mercure.rocks/authorization-detail',
                'actions' => ['subscribe', 'publish'],
                'topics' => [
                    ['match' => 'https://laravel.alt/echo/whisper/private-room.1'],
                    ['match' => 'https://laravel.alt/echo/whisper/private-encrypted-orders.1'],
                ],
            ],
        ], $claims['authorization_details']);
    }

    public function testAuthDeniesAnEncryptedChannelLikeAnyGuardedOne()
    {
        $broadcaster = $this->encryptedBroadcaster();
        $broadcaster->channel('orders.{id}', fn () => false);

        $this->expectException(AccessDeniedHttpException::class);

        $broadcaster->auth($this->requestFor(['private-encrypted-orders.1'], 42));
    }

    public function testBroadcastPublishesOnePrivateUpdatePerEncryptedChannel()
    {
        $broadcaster = $this->encryptedBroadcaster();

        $this->hub->shouldReceive('publish')->once()->with(m::on(
            fn (Update $update) => $update->getTopics() === ['https://laravel.alt/echo/channel/news'] && ! $update->isPrivate()
        ));
        $this->hub->shouldReceive('publish')->once()->with(m::on(
            fn (Update $update) => $update->getTopics() === ['https://laravel.alt/echo/channel/private-room.1'] && $update->isPrivate()
        ));

        foreach (['private-encrypted-a', 'private-encrypted-b'] as $channel) {
            $this->hub->shouldReceive('publish')->once()->with(m::on(function (Update $update) use ($channel) {
                $data = json_decode($update->getData(), true);

                return $update->getTopics() === ['https://laravel.alt/echo/channel/'.$channel]
                    && $update->isPrivate()
                    && array_keys($data) === ['channels', 'data']
                    && $data['channels'] === [$channel]
                    && count(explode('.', $data['data'])) === 5;
            }));
        }

        $broadcaster->broadcast(['news', 'private-room.1', 'private-encrypted-a', 'private-encrypted-b'], 'MessageSent', ['text' => 'hi']);
    }

    public function testBroadcastEncryptedUpdateRoundTrips()
    {
        $broadcaster = $this->encryptedBroadcaster();

        $captured = null;
        $this->hub->shouldReceive('publish')->once()->with(m::on(function (Update $update) use (&$captured) {
            $captured = $update;

            return true;
        }));

        $broadcaster->broadcast(['private-encrypted-room.1'], 'MessageSent', ['text' => 'hi', 'socket' => 'abcd.1234']);

        $jwe = (new CompactSerializer)->unserialize(json_decode($captured->getData(), true)['data']);
        $decrypter = new JWEDecrypter(new AlgorithmManager([new Dir, new A256GCM]));

        $this->assertTrue($decrypter->decryptUsingKey($jwe, $this->channelJwk('private-encrypted-room.1'), 0));
        $this->assertSame(
            ['event' => 'MessageSent', 'payload' => ['text' => 'hi'], 'socket' => 'abcd.1234'],
            json_decode($jwe->getPayload(), true)
        );
        $this->assertSame(['alg' => 'dir', 'enc' => 'A256GCM'], $jwe->getSharedProtectedHeader());
    }

    public function testBroadcastEncryptedChannelsUseDistinctKeys()
    {
        $broadcaster = $this->encryptedBroadcaster();

        $captured = null;
        $this->hub->shouldReceive('publish')->once()->with(m::on(function (Update $update) use (&$captured) {
            $captured = $update;

            return true;
        }));

        $broadcaster->broadcast(['private-encrypted-a'], 'Tick');

        $jwe = (new CompactSerializer)->unserialize(json_decode($captured->getData(), true)['data']);
        $decrypter = new JWEDecrypter(new AlgorithmManager([new Dir, new A256GCM]));

        $this->assertFalse($decrypter->decryptUsingKey($jwe, $this->channelJwk('private-encrypted-b'), 0));
    }

    public function testCookieDomainIsOmittedWhenTheHubSharesTheAppHost()
    {
        $this->broadcaster->channel('room.1', fn () => true);

        $response = $this->broadcaster->auth($this->requestFor(['private-room.1'], 42));

        $this->assertNull($response->headers->getCookies()[0]->getDomain());
    }

    public function testCookieDomainCoversAHubOnASubdomainOfTheApp()
    {
        $broadcaster = $this->broadcasterForHub('https://hub.example.com/.well-known/mercure');
        $broadcaster->channel('room.1', fn () => true);

        $request = Request::create('https://example.com/broadcasting/auth', 'POST', ['channel_names' => ['private-room.1']]);
        $request->setUserResolver(fn () => new GenericBroadcastingTestUser(42));

        $response = $broadcaster->auth($request);

        $this->assertSame('example.com', $response->headers->getCookies()[0]->getDomain());
    }

    public function testCookieDomainCoversAHubAndAppOnSiblingSubdomains()
    {
        $broadcaster = $this->broadcasterForHub('https://hub.example.com/.well-known/mercure');
        $broadcaster->channel('room.1', fn () => true);

        $request = Request::create('https://app.example.com/broadcasting/auth', 'POST', ['channel_names' => ['private-room.1']]);
        $request->setUserResolver(fn () => new GenericBroadcastingTestUser(42));

        $response = $broadcaster->auth($request);

        $this->assertSame('.example.com', $response->headers->getCookies()[0]->getDomain());
    }

    public function testAuthRejectsASecurePrefixedCookieNameOnAPlainHttpHub()
    {
        $broadcaster = $this->broadcasterForHub('http://localhost/.well-known/mercure');

        $this->expectException(MercureInvalidArgumentException::class);

        $broadcaster->auth($this->requestFor(['news'], null));
    }

    public function testAuthRejectsAHubOnADifferentSecondLevelDomain()
    {
        $broadcaster = $this->broadcasterForHub('https://hub.other.com/.well-known/mercure');
        $broadcaster->channel('room.1', fn () => true);

        $request = Request::create('https://example.com/broadcasting/auth', 'POST', ['channel_names' => ['private-room.1']]);
        $request->setUserResolver(fn () => new GenericBroadcastingTestUser(42));

        $this->expectException(RuntimeException::class);

        $broadcaster->auth($request);
    }

    public function testAuthOmitsTheWhisperGrantWhenClientEventsAreDisabled()
    {
        $broadcaster = new MercureBroadcaster($this->hub, clientEvents: false);
        $broadcaster->channel('room.1', fn () => true);

        $response = $broadcaster->auth($this->requestFor(['private-room.1'], 42));

        $this->assertFalse($response->getData(true)['client_events']);

        $details = $this->decodeJwtClaims($this->cookieValue($response))['authorization_details'];

        $this->assertCount(1, $details);
        $this->assertSame(['subscribe'], $details[0]['actions']);
    }

    public function testAuthAndBroadcastHonorACustomTopicPrefix()
    {
        $broadcaster = new MercureBroadcaster($this->hub, topicPrefix: 'https://app.example.com/broadcasting/');
        $broadcaster->channel('room.1', fn () => true);

        $response = $broadcaster->auth($this->requestFor(['private-room.1'], 42));

        $this->assertSame('https://app.example.com/broadcasting/', $response->getData(true)['topic_prefix']);

        $details = $this->decodeJwtClaims($this->cookieValue($response))['authorization_details'];

        $this->assertSame([['match' => 'https://app.example.com/broadcasting/channel/private-room.1']], $details[0]['topics']);
        $this->assertSame([['match' => 'https://app.example.com/broadcasting/whisper/private-room.1']], $details[1]['topics']);

        $this->hub->shouldReceive('publish')->once()->with(m::on(
            fn (Update $update) => $update->getTopics() === ['https://app.example.com/broadcasting/channel/news']
        ));

        $broadcaster->broadcast(['news'], 'Tick');
    }

    protected function broadcasterForHub(string $publicUrl)
    {
        $hub = m::mock(HubInterface::class);
        $hub->shouldReceive('getPublicUrl')->andReturn($publicUrl);
        $hub->shouldReceive('getCookieName')->andReturn('__Secure-mercure_access_token');
        $hub->shouldReceive('getFactory')->andReturn($this->tokenFactory());

        return new MercureBroadcaster($hub);
    }

    protected function encryptionKey()
    {
        return hash('sha256', 'mercure-e2e-test-key', true);
    }

    protected function encryptedBroadcaster()
    {
        return new MercureBroadcaster($this->hub, 300, new MercureChannelEncrypter($this->encryptionKey()));
    }

    /**
     * Build the given channel's JWK independently of the encrypter, so the
     * tests prove the exact derivation contract the JS connector relies on.
     *
     * @param  string  $channel
     * @return \Jose\Component\Core\JWK
     */
    protected function channelJwk(string $channel)
    {
        return new JWK([
            'kty' => 'oct',
            'k' => rtrim(strtr(base64_encode(hash_hkdf('sha256', $this->encryptionKey(), 32, $channel)), '+/', '-_'), '='),
        ]);
    }

    /**
     * @param  array  $channelNames
     * @param  mixed  $userId
     * @return \Illuminate\Http\Request
     */
    protected function requestFor(array $channelNames, $userId)
    {
        $request = Request::create('/broadcasting/auth', 'POST', [
            'channel_names' => $channelNames,
        ]);

        $request->setUserResolver(fn () => is_null($userId) ? null : new GenericBroadcastingTestUser($userId));

        return $request;
    }

    /**
     * @param  \Illuminate\Http\JsonResponse  $response
     * @return string
     */
    protected function cookieValue($response)
    {
        return $response->headers->getCookies()[0]->getValue();
    }

    /**
     * @param  string  $jwt
     * @return array
     */
    protected function decodeJwtClaims($jwt)
    {
        [, $payload] = explode('.', $jwt);

        return json_decode(base64_decode(str_pad(
            strtr($payload, '-_', '+/'),
            strlen($payload) + (4 - strlen($payload) % 4) % 4,
            '='
        )), true);
    }
}

class GenericBroadcastingTestUser
{
    public function __construct(protected $id)
    {
    }

    public function getAuthIdentifier()
    {
        return $this->id;
    }
}
