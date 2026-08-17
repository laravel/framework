<?php

namespace Illuminate\Broadcasting\Broadcasters;

use Illuminate\Broadcasting\BroadcastException;
use Illuminate\Broadcasting\MercureChannelEncrypter;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;
use JsonException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Mercure\Authorization;
use Symfony\Component\Mercure\Exception\ExceptionInterface as MercureExceptionInterface;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\HubRegistry;
use Symfony\Component\Mercure\Jwt\Grant;
use Symfony\Component\Mercure\Update;

/**
 * @author Kévin Dunglas <kevin@dunglas.dev>
 */
class MercureBroadcaster extends Broadcaster
{
    use UsePusherChannelConventions;

    /**
     * Create a new broadcaster instance.
     *
     * The hub's token factory mints the subscriber cookie token and must be
     * non-null, already carrying the static RFC 9068 claims; the hub's token
     * provider mints the (longer-lived) publish token. The encrypter enables
     * end-to-end encrypted channels: without one, using such a channel
     * throws. Presence channels are never encrypted, as their member
     * payloads flow through the hub's subscription API.
     *
     * The topic prefix namespaces every hub topic: it keeps Laravel topics
     * from colliding with other publishers sharing the hub, and lets two
     * applications sharing one hub (and one JWT secret) stay apart. The
     * default is a "laravel.alt" URL: ".alt" is reserved outside the DNS
     * (RFC 9476), so the IRI is guaranteed non-resolvable and unsquattable.
     *
     * @param  \Symfony\Component\Mercure\HubInterface  $hub
     * @param  int  $expiration
     * @param  \Illuminate\Broadcasting\MercureChannelEncrypter|null  $encrypter
     * @param  string  $topicPrefix
     * @param  bool  $clientEvents
     */
    public function __construct(
        protected HubInterface $hub,
        protected int $expiration = 300,
        protected ?MercureChannelEncrypter $encrypter = null,
        protected string $topicPrefix = 'https://laravel.alt/echo/',
        protected bool $clientEvents = true,
    ) {
        $this->exemptCookieFromEncryption();
    }

    /**
     * Authenticate the incoming request for a given channel.
     *
     * Mercure multiplexes every joined topic over one EventSource guarded by
     * one authorization cookie, so this reads a "channel_names" array and
     * mints a single token covering every currently-joined channel, public
     * ones included (a hub without the "anonymous" directive rejects
     * token-less subscribers). The batch is all-or-nothing: one denied
     * channel rejects the whole request.
     *
     * Each authorized end-to-end encrypted channel's response entry carries
     * the JSON Web Key decrypting its updates, the out-of-band key exchange
     * recommended by the Mercure specification: the hub never sees the keys.
     *
     * Every guarded channel also gets a whisper topic the subscriber may
     * publish to, so clients can exchange whispers directly through the hub.
     * Confining client publish rights to those topics keeps the channel
     * topics server-only: a whisper grant can never forge a server event.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     *
     * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
     * @throws \Illuminate\Broadcasting\BroadcastException
     */
    public function auth($request)
    {
        $channelNames = (array) $request->input('channel_names', []);

        // The cap bounds the channel-callback work a single request can
        // trigger and keeps the cookie (which also carries the whisper
        // grants) under browser size limits.
        if ($channelNames === [] ||
            count($channelNames) > 100 ||
            $channelNames !== array_filter($channelNames, 'is_string')) {
            throw new AccessDeniedHttpException;
        }

        $channelNames = array_unique($channelNames);

        $responseChannels = [];
        $privateTopics = [];
        $presenceGrants = [];
        $whisperTopics = [];
        $user = null;

        foreach ($channelNames as $channelName) {
            $responseChannel = ['name' => $channelName];

            if (! $this->isGuardedChannel($channelName)) {
                // Public: delivery is gated by the Update's "private" flag,
                // not by a grant here.
                $responseChannels[] = $responseChannel;

                continue;
            }

            $normalizedChannelName = $this->normalizeChannelName($channelName);

            if (! $channelUser = $this->retrieveUser($request, $normalizedChannelName)) {
                throw new AccessDeniedHttpException;
            }

            $user ??= $channelUser;

            $result = $this->verifyUserCanAccessChannel($request, $normalizedChannelName);

            if (str_starts_with($channelName, 'private-encrypted-')) {
                if ($this->encrypter === null) {
                    throw new BroadcastException(sprintf('Mercure broadcasting requires an "encryption_key" configuration value to authorize the end-to-end encrypted channel [%s].', $channelName));
                }

                $responseChannel['jwk'] = $this->encrypter->channelJwk($channelName);
                $privateTopics[] = $this->channelTopic($channelName);
            } elseif (str_starts_with($channelName, 'presence-')) {
                // A payload is scoped to its own authorization_details
                // entry, so each presence channel gets its own grant.
                $presenceGrants[] = new Grant([Grant::ACTION_SUBSCRIBE], [
                    'exact' => [$this->channelTopic($channelName)],
                    'urlpattern' => [$this->subscriptionPattern($channelName)],
                ], $result);
            } else {
                $privateTopics[] = $this->channelTopic($channelName);
            }

            $whisperTopics[] = $this->whisperTopic($channelName);

            $responseChannels[] = $responseChannel;
        }

        $grants = $presenceGrants;

        if ($privateTopics !== []) {
            array_unshift($grants, new Grant([Grant::ACTION_SUBSCRIBE], $privateTopics));
        }

        // Whispers are published privately, so their topics need "subscribe"
        // on top of "publish" to be both sendable and receivable.
        if ($this->clientEvents && $whisperTopics !== []) {
            $grants[] = new Grant([Grant::ACTION_SUBSCRIBE, Grant::ACTION_PUBLISH], $whisperTopics);
        }

        // "expires_in" lets the connector refresh the cookie before it
        // expires, since it can't read the httpOnly cookie itself. The topic
        // prefix tells it how to map channel names to hub topics.
        return (new JsonResponse([
            'channel_names' => $responseChannels,
            'expires_in' => $this->expiration,
            'topic_prefix' => $this->topicPrefix,
            'client_events' => $this->clientEvents,
        ]))->cookie($this->makeAuthorizationCookie($request, $grants, $user));
    }

    /**
     * Return the valid authentication response.
     *
     * A pass-through: auth() does the real work once, after collecting every
     * requested channel's raw authorization result.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  mixed  $result
     * @return mixed
     */
    public function validAuthenticationResponse($request, $result)
    {
        return $result;
    }

    /**
     * Broadcast the given event.
     *
     * The hub delivers a private update to any subscriber authorized for one
     * of its topics, so every guarded channel gets its own update: a shared
     * one would leak the sibling channel names to every recipient. Encrypted
     * channels additionally hide the event name, payload, and socket ID from
     * the hub inside a JWE; the routing envelope stays plaintext so a
     * multiplexing subscriber can select the decryption key.
     *
     * @param  array  $channels
     * @param  string  $event
     * @param  array  $payload
     * @return void
     *
     * @throws \Illuminate\Broadcasting\BroadcastException
     */
    public function broadcast(array $channels, $event, array $payload = [])
    {
        $channels = $this->formatChannels($channels);

        if ($channels === []) {
            return;
        }

        $socket = Arr::pull($payload, 'socket');

        $publicChannels = [];
        $guardedChannels = [];
        $encryptedChannels = [];

        foreach ($channels as $channel) {
            if (str_starts_with($channel, 'private-encrypted-')) {
                if ($this->encrypter === null) {
                    throw new BroadcastException(sprintf('Mercure broadcasting requires an "encryption_key" configuration value to broadcast on the end-to-end encrypted channel [%s].', $channel));
                }

                $encryptedChannels[] = $channel;
            } elseif ($this->isGuardedChannel($channel)) {
                $guardedChannels[] = $channel;
            } else {
                $publicChannels[] = $channel;
            }
        }

        try {
            if ($publicChannels !== []) {
                $this->hub->publish(new Update(
                    array_map($this->channelTopic(...), $publicChannels),
                    $this->updateData($publicChannels, $event, $payload, $socket),
                    false,
                ));
            }

            foreach ($guardedChannels as $channel) {
                $this->hub->publish(new Update(
                    [$this->channelTopic($channel)],
                    $this->updateData([$channel], $event, $payload, $socket),
                    true,
                ));
            }

            // The plaintext is channel-independent; only the JWE differs. It
            // carries no "channels" key: the routing envelope stays outside
            // the ciphertext (see below).
            $plaintext = $encryptedChannels === [] ? null : $this->updateData(null, $event, $payload, $socket);

            foreach ($encryptedChannels as $channel) {
                $this->hub->publish(new Update(
                    [$this->channelTopic($channel)],
                    json_encode([
                        'channels' => [$channel],
                        'data' => $this->encrypter->encrypt($plaintext, $channel),
                    ], JSON_THROW_ON_ERROR),
                    true,
                ));
            }
        } catch (JsonException $e) {
            throw new BroadcastException(sprintf('Mercure error: %s.', $e->getMessage()), 0, $e);
        } catch (MercureExceptionInterface $e) {
            // The actionable cause (401, DNS, timeout) lives on the
            // previous exception; Hub::publish()'s own message is generic.
            throw new BroadcastException(sprintf('Mercure error: %s.', $e->getPrevious()?->getMessage() ?? $e->getMessage()), 0, $e);
        }
    }

    /**
     * Get the Mercure hub instance.
     *
     * @return \Symfony\Component\Mercure\HubInterface
     */
    public function getHub()
    {
        return $this->hub;
    }

    /**
     * Set the Mercure hub instance.
     *
     * @param  \Symfony\Component\Mercure\HubInterface  $hub
     * @return void
     */
    public function setHub(HubInterface $hub)
    {
        $this->hub = $hub;

        $this->exemptCookieFromEncryption();
    }

    /**
     * Exempt the hub's authorization cookie from cookie encryption.
     *
     * Encrypting the raw JWT would produce a value the hub can never verify.
     *
     * @return void
     */
    protected function exemptCookieFromEncryption()
    {
        if (class_exists(EncryptCookies::class)) {
            EncryptCookies::except($this->hub->getCookieName());
        }
    }

    /**
     * Encode the data of an update targeting the given channels.
     *
     * A null "channels" is omitted, yielding the channel-independent plaintext
     * an encrypted update seals inside its JWE.
     *
     * @param  array|null  $channels
     * @param  string  $event
     * @param  array  $payload
     * @param  string|null  $socket
     * @return string
     *
     * @throws \JsonException
     */
    protected function updateData($channels, $event, array $payload, $socket)
    {
        return json_encode(array_filter([
            'channels' => $channels,
            'event' => $event,
            'payload' => $payload,
            'socket' => $socket,
        ], fn ($value) => $value !== null), JSON_THROW_ON_ERROR);
    }

    /**
     * Build the subscriber authorization cookie carrying the given grants.
     *
     * The Authorization helper mints the token with the hub's factory,
     * resolves the cookie domain so a hub on a sibling subdomain still
     * receives the cookie, and injects an "exp" claim matching the cookie
     * lifetime. Only the per-request "sub" claim is contributed here; the
     * static RFC 9068 claims come from the hub's factory.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Symfony\Component\Mercure\Jwt\Grant[]  $grants
     * @param  mixed  $user
     * @return \Symfony\Component\HttpFoundation\Cookie
     */
    protected function makeAuthorizationCookie($request, array $grants, $user = null)
    {
        $claims = [];

        // The channel-guard-resolved user wins over the default guard's, so
        // "sub" matches the identity the grants were authorized for.
        if ($user ??= $request->user()) {
            $claims['sub'] = (string) (method_exists($user, 'getAuthIdentifierForBroadcasting')
                ? $user->getAuthIdentifierForBroadcasting()
                : $user->getAuthIdentifier());
        }

        return (new Authorization(new HubRegistry($this->hub), $this->expiration))
            ->createCookie($request, $grants, null, $claims);
    }

    /**
     * Build the hub topic of the given channel.
     *
     * Channel names are RFC 3986 encoded into a single path segment, so a
     * name can never escape its namespace, whatever characters it contains.
     *
     * @param  string  $channelName
     * @return string
     */
    protected function channelTopic($channelName)
    {
        return $this->topicPrefix.'channel/'.rawurlencode($channelName);
    }

    /**
     * Build the whisper topic of the given channel: the only topic its
     * subscribers may publish to, keeping the channel topic server-only.
     *
     * @param  string  $channelName
     * @return string
     */
    protected function whisperTopic($channelName)
    {
        return $this->topicPrefix.'whisper/'.rawurlencode($channelName);
    }

    /**
     * Build the subscription-API topic matcher for the given presence
     * channel, matching both the snapshot listing used to seed the member
     * list (.../subscriptions/{match_type}/{match}) and the per-subscriber
     * active:true/false events (.../{match_type}/{match}/{subscriber}).
     * "{/:subscriber}?" is an optional URLPattern group covering the
     * trailing segment and its slash.
     *
     * @param  string  $channelName
     * @return string
     *
     * @see https://mercure.rocks/docs/hub/concepts/active-subscriptions
     */
    protected function subscriptionPattern($channelName)
    {
        return sprintf(
            '%s/subscriptions/:match_type/%s{/:subscriber}?',
            parse_url($this->hub->getPublicUrl(), PHP_URL_PATH) ?: '/.well-known/mercure',
            rawurlencode($this->channelTopic($channelName))
        );
    }
}
