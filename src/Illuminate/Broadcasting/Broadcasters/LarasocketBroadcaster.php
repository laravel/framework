<?php

namespace Illuminate\Broadcasting\Broadcasters;

use Illuminate\Broadcasting\BroadcastException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;

class LarasocketBroadcaster extends Broadcaster
{
    use UsePusherChannelConventions;

    private const LARASOCKET_HOST = 'https://larasocket.com';

    protected $larasocketToken;

    /**
     * Create a new broadcaster instance.
     */
    public function __construct(array $config)
    {
        $this->larasocketToken = $config['token'];
    }

    /**
     * Authenticate the incoming request for a given channel.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
     *
     * @return mixed
     */
    public function auth($request)
    {
        $channelName = $this->normalizeChannelName($request->channel_name);

        if ($this->isGuardedChannel($request->channel_name) &&
            ! $this->retrieveUser($request, $channelName)) {
            throw new AccessDeniedHttpException();
        }

        return parent::verifyUserCanAccessChannel(
            $request,
            $channelName
        );
    }

    /**
     * Return the valid authentication response.
     *
     * @param \Illuminate\Http\Request $request
     * @param mixed                    $result
     *
     * @return mixed
     */
    public function validAuthenticationResponse($request, $result)
    {
        if (0 === strncmp($request->channel_name, 'private', strlen('private'))) {
            return $this->authPrivate(
                $request->channel_name,
                $request->socket_id
            );
        }

        $channelName = $this->normalizeChannelName($request->channel_name);

        return $this->authPresence(
            $request->channel_name,
            $request->socket_id,
            $this->retrieveUser($request, $channelName)->getAuthIdentifier(),
            $result
        );
    }

    /**
     * Broadcast the given event.
     *
     * @param array $channels
     * @param string $event
     * @param array $payload
     *
     * @return \Illuminate\Http\Client\Response
     * @throws \Illuminate\Broadcasting\BroadcastException
     */
    public function broadcast(array $channels, $event, array $payload = [])
    {
        $socket = Arr::pull($payload, 'socket');

        $response = $this->trigger(
            $this->formatChannels($channels),
            $event,
            $payload,
            $socket
        );

        if ($response->status() >= 200
            && $response->status() <= 299
            && ($json = $response->json())) {
            return $response;
        }

        throw new BroadcastException(
            is_bool($response) ? 'Failed to connect to Laravel Websockets.' : $response
        );
    }

    /**
     * A broadcast-ed event has been triggered. We need to send it over to Larasocket servers for processing and then
     * dispatching to listening clients.
     *
     * @param $channels
     * @param $event
     * @param $data
     * @param $connectionId
     *
     * @return \Illuminate\Http\Client\Response
     */
    public function trigger($channels, $event, $data, $connectionId)
    {
        if (true === is_string($channels)) {
            $channels = [$channels];
        }

        $url = self::LARASOCKET_HOST.'/api/broadcast';

        return Http::
        withToken($this->larasocketToken)
            ->withHeaders(['Accept' => 'application/json'])
            ->post($url, [
                'event' => $event,
                'channels' => $channels,
                'payload' => json_encode($data),
                'connection_id' => $connectionId,
            ])
            ;
    }

    /**
     * @param string $channel
     * @param $connectionId
     *
     * @return array
     */
    public function authPrivate(string $channel, $connectionId)
    {
        return [
            'connection_id' => $connectionId,
            'channel' => $channel,
        ];
    }

    /**
     * @param string $channel
     * @param $connectionId
     * @param $uid
     * @param $authResults
     *
     * @return array
     */
    public function authPresence(string $channel, $connectionId, $uid, $authResults)
    {
        return $authResults;
    }
}
