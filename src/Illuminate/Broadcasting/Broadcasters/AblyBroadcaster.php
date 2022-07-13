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
        if (self::$serverTimeDiff == null) {
            self::setServerTime(round($this->ably->time() / 1000));
        }
    }

    private static $serverTimeDiff = null;
    /**
     * @param $time int
     * @return void
     */
    private static function setServerTime($time)
    {
        self::$serverTimeDiff = time() - $time;
    }

    /**
     * @return int
     */
    private static function getServerTime()
    {
        if (self::$serverTimeDiff != null) {
            return time() - self::$serverTimeDiff;
        }
        return time();
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
        $userId = null;
        if ($this->isGuardedChannel($channelName)) {
            $user = $this->retrieveUser($request, $normalizedChannelName);
            if (!$user) {
                throw new AccessDeniedHttpException( "User not authenticated, " . $this->stringify( $channelName, $connectionId ) );
            }
            $userId = method_exists($user, 'getAuthIdentifierForBroadcasting')
                ? $user->getAuthIdentifierForBroadcasting()
                : $user->getAuthIdentifier();

            try {
                $userData = parent::verifyUserCanAccessChannel($request, $normalizedChannelName);
            } catch (\Exception $e) {
                throw new AccessDeniedHttpException("Access denied, " . $this->stringify($channelName, $connectionId, $userId), $e);
            }
        }

        try {
            $signedToken = $this->getSignedToken($channelName, $token, $userId );
        } catch (\Exception $_) { // excluding exception to avoid exposing private key
            throw new AccessDeniedHttpException("malformed token, " . $this->stringify($channelName, $connectionId, $userId));
        }

        $response = array('token' => $signedToken);
        if (isset($userData) && is_array($userData)) {
            $response['info'] = $userData;
        }
        return $response;
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
     * @param $channelName string
     * @param $token string
     * @param $clientId string
     * @return string
     */
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
        $serverTimeFn = function () {
            return self::getServerTime();
        };
        if ($token && $this->isJwtValid($token, $serverTimeFn)) {
            $payload = self::parseJwt($token)['payload'];
            $iat = $payload['iat'];
            $exp = $payload['exp'];
            $channelClaims = json_decode($payload['x-ably-capability'], true);
        } else {
            $iat = $serverTimeFn();
            $exp = $iat + 3600;
        }
        if ($channelName) {
            $channelClaims[$channelName] = ["*"];
        }
        $claims = array(
            "iat" => $iat,
            "exp" => $exp,
            "x-ably-clientId" => $clientId,
            "x-ably-capability" => json_encode($channelClaims)
        );

        return $this->generateJwt($header, $claims);
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

    /**
     * Checks if channel is a private channel
     * @param $channel string
     * @return bool
     */
    public function isPrivateChannel($channel)
    {
        return Str::startsWith($channel, 'private:');
    }

    /**
     * Checks if channel is a presence channel
     * @param $channel string
     * @return bool
     */
    public function isPresenceChannel($channel)
    {
        return Str::startsWith($channel, 'presence:');
    }

    /**
     * Checks if channel needs authentication
     * @param $channel string
     * @return bool
     */
    public function isGuardedChannel($channel)
    {
        return $this->isPrivateChannel($channel) || $this->isPresenceChannel($channel);
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
     * @param $channelName string
     * @param $connectionId string
     * @param $userId string
     * @return string
     */
    protected function stringify($channelName, $connectionId, $userId = null)
    {
        $message = "channel-name:" . $channelName . " ably-connection-id:" . $connectionId;
        if ($userId) {
            return "user-id:" . $userId . " " . $message;
        }
        return $message;
    }

    // JWT related PHP utility functions

    /**
     * @param $jwt string
     * @return array
     */
    static function parseJwt($jwt)
    {
        $tokenParts = explode('.', $jwt);
        $header = json_decode(base64_decode($tokenParts[0]), true);
        $payload = json_decode(base64_decode($tokenParts[1]), true);;
        return array('header' => $header, 'payload' => $payload);
    }

    /**
     * @param $headers array
     * @param $payload array
     * @return string
     */
    function generateJwt($headers, $payload)
    {
        $encodedHeaders = self::base64urlEncode(json_encode($headers));
        $encodedPayload = self::base64urlEncode(json_encode($payload));

        $signature = hash_hmac('SHA256', "$encodedHeaders.$encodedPayload", $this->getPrivateToken(), true);
        $encodedSignature = self::base64urlEncode($signature);

        return "$encodedHeaders.$encodedPayload.$encodedSignature";
    }

    /**
     * @param $jwt string
     * @param $timeFn
     * @return bool
     */
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
}
