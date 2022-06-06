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
     * @param \Ably\AblyRest $ably
     * @return void
     */
    public function __construct(AblyRest $ably)
    {
        $this->ably = $ably;
    }

    /**
     * Authenticate the incoming request for a given channel.
     *
     * @param \Illuminate\Http\Request $request
     * @return mixed
     *
     * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
     */
    public function auth($request)
    {
        $channelName = $request->channel_name;
        $token = $request->token;
        $connectionId = $request->socket_id;
        $normalizedChannelName = $this->normalizeChannelName($channelName);

        $user = $this->retrieveUser($request, $normalizedChannelName);
        if ($this->isGuardedChannel($channelName) && !$user) {
            throw new AccessDeniedHttpException("User not authenticated, ". $this->stringify($channelName, $connectionId));
        }

        $userId = method_exists($user, 'getAuthIdentifierForBroadcasting')
            ? $user->getAuthIdentifierForBroadcasting()
            : $user->getAuthIdentifier();
        try {
            $userData = parent::verifyUserCanAccessChannel($request, $normalizedChannelName);
        } catch (\Exception $e) {
            throw new AccessDeniedHttpException("Access denied, ". $this->stringify($channelName, $connectionId, $userId), $e);
        }

        try {
            $signedToken = $this->getSignedToken($channelName, $token, $userId);
        } catch (\Exception $_) { // excluding exception to avoid exposing private key
            throw new AccessDeniedHttpException("malformed token, ".$this->stringify($channelName, $connectionId, $userId));
        }

        $response = array('token' => $signedToken);
        if (is_array($userData)) {
            $response['info'] = $userData;
        }
        return $response;
    }

    protected function stringify($channelName, $connectionId, $userId = null) {
        $message = "channel-name:".$channelName." ably-connection-id:". $connectionId;
        if ($userId) {
            return "user-id:".$userId." ".$message;
        }
        return $message;
    }

    /**
     * Remove prefix from channel name.
     *
     * @param string $channel
     * @return string
     */
    public function normalizeChannelName($channel)
    {
        if ($channel) {
            if ($this->isPrivateChannel($channel)) {
                return Str::replaceFirst('private:', '', $channel);
            }
            if ($this->isPresenceChannel($channel)) {
                return Str::replaceFirst('presence:', '', $channel);
            }
            return Str::replaceFirst('public:', '', $channel);
        }
        return $channel;
    }

    public function isPrivateChannel($channel)
    {
        return Str::startsWith($channel, 'private:');
    }

    public function isPresenceChannel($channel)
    {
        return Str::startsWith($channel, 'presence:');
    }

    /**
     * Return true if the channel is protected by authentication.
     *
     * @param string $channel
     * @return bool
     */
    public function isGuardedChannel($channel)
    {
        return $this->isPrivateChannel($channel) || $this->isPresenceChannel($channel);
    }

    function getSignedToken($channelName, $token, $clientId)
    {
        $header = array(
            "typ" => "JWT",
            "alg" => "HS256",
            "kid" => $this->getPublicToken()
        );
        // Set capabilities for public channel as per https://ably.com/docs/core-features/authentication#capability-operations
        $channelClaims = array(
            'public:*' => ["subscribe", "history", "channel-metadata"]
        );
        $serverTimeFn = function () { return $this->ably->time() / 1000; }; // TODO - Update with server offset
        if ($token && $this->isJwtValid($token, $serverTimeFn)) {
            $payload = self::parseJwt($token)['payload'];
            $iat = $payload['iat'];
            $exp = $payload['exp'];
            $channelClaims = $payload['x-ably-capability'];
        } else {
            $iat = round($serverTimeFn());
            $exp = $iat + 3600;
        }
        if ($channelName) {
            $channelClaims[$channelName] = ["*"];
        }
        $claims = array(
            "iat" => $iat,
            "exp" => $exp,
            "x-ably-clientId" => $clientId,
            "x-ably-capability" => $channelClaims
        );

        return $this->generateJwt($header, $claims);
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

    function isJwtValid($jwt, $timeFn)
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
        $signature = hash_hmac('SHA256', $header . "." . $payload, $this->getPrivateToken(), true);
        $isSignatureValid = self::base64urlEncode($signature) === $tokenSignature;

        return $isSignatureValid && !$isTokenExpired;
    }

    static function base64urlEncode($str)
    {
        return rtrim(strtr(base64_encode($str), '+/', '-_'), '=');
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
     * @param $jwt string
     * @return array
     */
    static function parseJwt($jwt)
    {
        $tokenParts = explode('.', $jwt);
        $header = json_decode(base64_decode($tokenParts[0]), true);
        $payload = json_decode(base64_decode($tokenParts[1]), true);;
        return array('header'=> $header, 'payload' => $payload);
    }

    function generateJwt($headers, $payload)
    {
        $encodedHeaders = self::base64urlEncode(json_encode($headers));
        $encodedPayload = self::base64urlEncode(json_encode($payload));

        $signature = hash_hmac('SHA256', "$encodedHeaders.$encodedPayload", $this->getPrivateToken(), true);
        $encodedSignature = self::base64urlEncode($signature);

        return "$encodedHeaders.$encodedPayload.$encodedSignature";
    }

    /**
     * Return the valid authentication response.
     *
     * @param \Illuminate\Http\Request $request
     * @param mixed $result
     * @return mixed
     */
    public function validAuthenticationResponse($request, $result)
    {
        return $result;
    }

    /**
     * Broadcast the given event.
     *
     * @param array $channels
     * @param string $event
     * @param array $payload
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
     * Format the channel array into an array of strings.
     *
     * @param array $channels
     * @return array
     */
    protected function formatChannels(array $channels)
    {
        return array_map(function ($channel) {
            $channel = (string)$channel;

            if (Str::startsWith($channel, ['private-', 'presence-'])) {
                return Str::startsWith($channel, 'private-')
                    ? Str::replaceFirst('private-', 'private:', $channel)
                    : Str::replaceFirst('presence-', 'presence:', $channel);
            }

            return 'public:' . $channel;
        }, $channels);
    }

    /**
     * Build an Ably message object for broadcasting.
     *
     * @param string $event
     * @param array $payload
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
     * Get the underlying Ably SDK instance.
     *
     * @return \Ably\AblyRest
     */
    public function getAbly()
    {
        return $this->ably;
    }
}
