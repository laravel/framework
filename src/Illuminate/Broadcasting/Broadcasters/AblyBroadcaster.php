<?php

namespace Illuminate\Broadcasting\Broadcasters;

use Ably\AblyRest;
use Ably\Exceptions\AblyException;
use Ably\Models\Message as AblyMessage;
use Illuminate\Broadcasting\BroadcastException;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * @author Matthew Hall (matthall28@gmail.com)
 * @author Taylor Otwell (taylor@laravel.com)
 */
class AblyBroadcaster extends Broadcaster
{
    /**
     * The AblyRest SDK instance.
     *
     * @var \Ably\AblyRest
     */
    protected $ably;

    /**
     * Create a new broadcaster instance.
     *
     * @param  \Ably\AblyRest  $ably
     * @return void
     */
    public function __construct(AblyRest $ably)
    {
        $this->ably = $ably;
    }

    /**
     * Authenticate the incoming request for a given channel.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return mixed
     *
     * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
     */
    public function auth($request)
    {
        $channelName = $this->normalizeChannelName($request->channel_name);

        if (empty($request->channel_name) ||
            ($this->isGuardedChannel($request->channel_name) &&
                ! $this->retrieveUser($request, $channelName))) {
            throw new AccessDeniedHttpException;
        }

        return parent::verifyUserCanAccessChannel(
            $request, $channelName
        );
    }

    /**
     * Return the valid authentication response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  mixed  $result
     * @return mixed
     */
    public function validAuthenticationResponse($request, $result)
    {
        if (str_starts_with($request->channel_name, 'private')) {
            $signature = $this->generateAblySignature(
                $request->channel_name, $request->socket_id
            );

            return ['auth' => $this->getPublicToken().':'.$signature];
        }

        $channelName = $this->normalizeChannelName($request->channel_name);

        $user = $this->retrieveUser($request, $channelName);

        $broadcastIdentifier = method_exists($user, 'getAuthIdentifierForBroadcasting')
            ? $user->getAuthIdentifierForBroadcasting()
            : $user->getAuthIdentifier();

        $signature = $this->generateAblySignature(
            $request->channel_name,
            $request->socket_id,
            $userData = array_filter([
                'user_id' => (string) $broadcastIdentifier,
                'user_info' => $result,
            ])
        );

        return [
            'auth' => $this->getPublicToken().':'.$signature,
            'channel_data' => json_encode($userData),
        ];
    }

    /**
     * Generate the signature needed for Ably authentication headers.
     *
     * @param  string  $channelName
     * @param  string  $socketId
     * @param  array|null  $userData
     * @return string
     */
    public function generateAblySignature($channelName, $socketId, $userData = null)
    {
        return hash_hmac(
            'sha256',
            sprintf('%s:%s%s', $socketId, $channelName, $userData ? ':'.json_encode($userData) : ''),
            $this->getPrivateToken(),
        );
    }

    /**
     * Broadcast the given event.
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
        try {
            foreach ($this->formatChannels($channels) as $channel) {
                $this->ably->channels->get($channel)->publish(
                    $this->buildAblyMessage($event, $payload)
                );
            }
        } catch (AblyException $e) {
            throw new BroadcastException(
                sprintf('Ably error: %s', $e->getMessage())
            );
        }
    }

    /**
     * Build an Ably message object for broadcasting.
     *
     * @param  string  $event
     * @param  array  $payload
     * @return \Ably\Models\Message
     */
    protected function buildAblyMessage($event, array $payload = [])
    {
        return tap(new AblyMessage, function ($message) use ($event, $payload) {
            $message->name = $event;
            $message->data = $payload;
            $message->connectionKey = data_get($payload, 'socket');
        });
    }

    /**
     * Return true if the channel is protected by authentication.
     *
     * @param  string  $channel
     * @return bool
     */
    public function isGuardedChannel($channel)
    {
        return Str::startsWith($channel, ['private-', 'presence-']);
    }

    /**
     * Remove prefix from channel name.
     *
     * @param  string  $channel
     * @return string
     */
    public function normalizeChannelName($channel)
    {
        if ($this->isGuardedChannel($channel)) {
            return str_starts_with($channel, 'private-')
                ? Str::replaceFirst('private-', '', $channel)
                : Str::replaceFirst('presence-', '', $channel);
        }

        return $channel;
    }

    /**
     * Format the channel array into an array of strings.
     *
     * @param  array  $channels
     * @return array
     */
    protected function formatChannels(array $channels)
    {
        return array_map(function ($channel) {
            $channel = (string) $channel;

            if (Str::startsWith($channel, ['private-', 'presence-'])) {
                return str_starts_with($channel, 'private-')
                    ? Str::replaceFirst('private-', 'private:', $channel)
                    : Str::replaceFirst('presence-', 'presence:', $channel);
            }

            return 'public:'.$channel;
        }, $channels);
    }

    /**
     * Get the public token value from the Ably key.
     *
     * @return mixed
     */
    protected function getPublicToken()
    {
        return Str::before($this->ably->options->key, ':');
    }

    /**
     * Get the private token value from the Ably key.
     *
     * @return mixed
     */
    protected function getPrivateToken()
    {
        return Str::after($this->ably->options->key, ':');
    }

    /**
     * Get the underlying Ably SDK instance.
     *
     * @return \Ably\AblyRest
     */
    public function getAbly()
    {
        return $this->ably;
    }

    static function generateJwt($headers, $payload, $secret = 'secret')
    {
        $encodedHeaders = self::base64urlEncode(json_encode($headers));
        $encodedPayload = self::base64urlEncode(json_encode($payload));

        $signature = hash_hmac('SHA256', "$encodedHeaders.$encodedPayload", $secret, true);
        $encodedSignature = self::base64urlEncode($signature);

        return "$encodedHeaders.$encodedPayload.$encodedSignature";
    }

    static function isJwtValid($jwt, $timeFn, $secret = 'secret')
    {
        // split the jwt
        $tokenParts = explode('.', $jwt);
        $header = $tokenParts[0];
        $payload = $tokenParts[1];
        $tokenSignature = $tokenParts[2];

        // check the expiration time - note this will cause an error if there is no 'exp' claim in the jwt
        $expiration = json_decode(base64_decode($payload))->exp;
        $isTokenExpired = $expiration <= $timeFn();

        // build a signature based on the header and payload using the secret
        $signature = hash_hmac('SHA256', $header . "." . $payload, $secret, true);
        $isSignatureValid = self::base64urlEncode($signature) === $tokenSignature;

        return $isSignatureValid && !$isTokenExpired;
    }

    static function parseJwt($jwt)
    {
        $tokenParts = explode('.', $jwt);
        $header = json_decode(base64_decode($tokenParts[0]));
        $payload = json_decode(base64_decode($tokenParts[1]));
        return array('header' => $header, 'payload' => $payload);
    }

    static function base64urlEncode($str)
    {
        return rtrim(strtr(base64_encode($str), '+/', '-_'), '=');
    }
}
