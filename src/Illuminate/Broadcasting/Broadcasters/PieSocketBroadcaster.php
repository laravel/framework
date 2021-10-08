<?php

namespace Illuminate\Broadcasting\Broadcasters;

use Illuminate\Broadcasting\BroadcastException;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

use PieSocket\PieSocket;

/**
 * @author PieSocket (support@piesocket.com)
 */
class PieSocketBroadcaster extends Broadcaster
{
    /**
     * The PieSocket SDK instance.
     *
     * @var \PieSocket\PieSocket
     */
    protected $piesocket;

    /**
     * Create a new broadcaster instance.
     *
     * @param  \PieSocket\PieSocket  $piesocket
     * @return void
     */
    public function __construct(PieSocket $piesocket)
    {
      $this->piesocket = $piesocket;
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
        if (Str::startsWith($request->channel_name, 'private')) {
            $signature = $this->generatePieSocketSignature(
                $request->channel_name, auth()->user->id
            );

            return ['auth' => $signature];
        }

        $channelName = $this->normalizeChannelName($request->channel_name);

        $user = $this->retrieveUser($request, $channelName);

        $broadcastIdentifier = method_exists($user, 'getAuthIdentifierForBroadcasting')
                    ? $user->getAuthIdentifierForBroadcasting()
                    : $user->getAuthIdentifier();

        $signature = $this->generatePieSocketSignature(
            $request->channel_name,
            $userData = array_filter([
                'user_id' => (string) $broadcastIdentifier,
                'user_info' => $result,
            ])
        );

        return [
            'auth' => $signature,
            'channel_data' => json_encode($userData),
        ];
    }

    /**
     * Generate the JWT token needed for PieSocket authentication.
     *
     * @param  string  $channelName
     * @param  string  $socketId
     * @param  array|null  $userData
     * @return string
     */
    public function generatePieSocketSignature($channelName, $userData = null)
    {
      return $this->piesocket->generateAuthToken($channelName, $userData);
    }

    /**
     * Broadcast the given event.
     *
     * @param  array  $channels
     * @param  string  $event
     * @param  array  $payload
     * @return void
     */
    public function broadcast(array $channels, $event, array $payload = [])
    {
        foreach ($this->formatChannels($channels) as $channel) {
          $this->piesocket->publish($channel, [
            "event" => $event,
            "data" => $payload
          ]);
        }
    }

    /**
     * Return true if the channel is protected by authentication.
     *
     * @param  string  $channel
     * @return bool
     */
    public function isGuardedChannel($channel)
    {
      return env('PIESOCKET_FORCE_AUTH') || Str::startsWith($channel, ['private-']);
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
            return Str::startsWith($channel, 'private-')
                        ? Str::replaceFirst('private-', '', $channel)
                        : Str::replaceFirst('presence-', '', $channel);
        }

        return Str::replaceFirst('public-', '', $channel);
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
              return $channel;
            }

            return 'public-'.$channel;
        }, $channels);
    }

    /**
     * Get the public token value from the PieSocket key.
     *
     * @return mixed
     */
    protected function getPublicToken()
    {
      return $this->piesocket->config['api_key'];
    }

    /**
     * Get the private token value from the PieSocket key.
     *
     * @return mixed
     */
    protected function getPrivateToken()
    {
      return $this->piesocket->config['api_secret'];
    }

    /**
     * Get the underlying PieSocket SDK instance.
     *
     * @return \PieSocket\PieSocket
     */
    public function getPieSocket()
    {
        return $this->piesocket;
    }
}
