<?php

namespace Illuminate\Broadcasting\Broadcasters;

use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class PollBroadcaster extends Broadcaster
{
    use UsePusherChannelConventions;

    /**
     * The cache repository instance.
     *
     * @var \Illuminate\Contracts\Cache\Repository
     */
    protected $cache;

    /**
     * The time-to-live for broadcast events in seconds.
     *
     * @var int
     */
    protected $ttl;

    /**
     * The cache key prefix.
     *
     * @var string
     */
    protected $prefix;

    /**
     * The presence timeout in seconds.
     *
     * @var int
     */
    protected $presenceTimeout;

    /**
     * The lottery odds for pruning expired event IDs.
     *
     * @var array
     */
    protected $lottery;

    /**
     * Create a new broadcaster instance.
     *
     * @param  \Illuminate\Contracts\Cache\Repository  $cache
     * @param  int  $ttl
     * @param  string  $prefix
     * @param  int  $presenceTimeout
     * @param  array  $lottery
     */
    public function __construct(Cache $cache, $ttl = 60, $prefix = 'poll_broadcast:', $presenceTimeout = 30, $lottery = [2, 100])
    {
        $this->cache = $cache;
        $this->ttl = $ttl;
        $this->prefix = $prefix;
        $this->presenceTimeout = $presenceTimeout;
        $this->lottery = $lottery;
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
        if (is_bool($result)) {
            return json_encode($result);
        }

        $channelName = $this->normalizeChannelName($request->channel_name);

        $user = $this->retrieveUser($request, $channelName);

        $broadcastIdentifier = method_exists($user, 'getAuthIdentifierForBroadcasting')
            ? $user->getAuthIdentifierForBroadcasting()
            : $user->getAuthIdentifier();

        return json_encode(['channel_data' => [
            'user_id' => $broadcastIdentifier,
            'user_info' => $result,
        ]]);
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
        if (empty($channels)) {
            return;
        }

        $eventId = (string) Str::ulid();
        $socket = Arr::pull($payload, 'socket');

        $this->cache->put($this->eventKey($eventId), [
            'id' => $eventId,
            'event' => $event,
            'data' => $payload,
            'socket' => $socket,
            'timestamp' => time(),
        ], $this->ttl);

        foreach ($this->formatChannels($channels) as $channel) {
            $this->cache->withoutOverlapping($this->channelKey($channel).':lock', function () use ($channel, $eventId) {
                $ids = $this->cache->get($this->channelKey($channel), []);

                $ids[] = $eventId;

                if (random_int(1, $this->lottery[1]) <= $this->lottery[0]) {
                    $ids = $this->pruneExpiredIds($ids);
                }

                $this->cache->put($this->channelKey($channel), $ids, $this->ttl);
            }, 10, 5);
        }
    }

    /**
     * Get events for the given channels since the last event ID.
     *
     * @param  array  $channels
     * @param  string|null  $lastEventId
     * @return array
     */
    public function getEvents(array $channels, $lastEventId = null)
    {
        if (is_null($lastEventId)) {
            return [
                'events' => [],
                'lastEventId' => (string) Str::ulid(),
            ];
        }

        $events = [];

        foreach ($channels as $channel) {
            $ids = $this->cache->get($this->channelKey($channel), []);

            foreach ($ids as $id) {
                if (strcmp($id, $lastEventId) <= 0) {
                    continue;
                }

                $event = $this->cache->get($this->eventKey($id));

                if (is_null($event)) {
                    continue;
                }

                $event['channel'] = $channel;
                $events[] = $event;
            }
        }

        usort($events, fn ($a, $b) => strcmp($a['id'], $b['id']));

        $newLastEventId = ! empty($events)
            ? end($events)['id']
            : $lastEventId;

        return [
            'events' => $events,
            'lastEventId' => $newLastEventId,
        ];
    }

    /**
     * Update presence information for a user on a channel.
     *
     * @param  string  $channel
     * @param  mixed  $userId
     * @param  mixed  $userInfo
     * @return array
     */
    public function updatePresence($channel, $userId, $userInfo)
    {
        return $this->cache->withoutOverlapping($this->presenceKey($channel).':lock', function () use ($channel, $userId, $userInfo) {
            $members = $this->cache->get($this->presenceKey($channel), []);

            $members[$userId] = [
                'user_id' => $userId,
                'user_info' => $userInfo,
                'last_seen' => time(),
            ];

            $cutoff = time() - $this->presenceTimeout;

            $members = array_filter($members, fn ($member) => $member['last_seen'] >= $cutoff);

            $this->cache->put($this->presenceKey($channel), $members, $this->ttl);

            return array_values(array_map(fn ($member) => [
                'user_id' => $member['user_id'],
                'user_info' => $member['user_info'],
            ], $members));
        }, 10, 5);
    }

    /**
     * Prune expired event IDs from the given array using ULID timestamp comparison.
     *
     * @param  array  $ids
     * @return array
     */
    protected function pruneExpiredIds(array $ids)
    {
        $cutoffTime = now()->subSeconds($this->ttl);
        $cutoffUlid = (string) Str::ulid($cutoffTime);

        return array_values(array_filter($ids, fn ($id) => strcmp($id, $cutoffUlid) > 0));
    }

    /**
     * Get the cache key for a channel's event index.
     *
     * @param  string  $channel
     * @return string
     */
    public function channelKey($channel)
    {
        return $this->prefix.$channel;
    }

    /**
     * Get the cache key for an event payload.
     *
     * @param  string  $eventId
     * @return string
     */
    public function eventKey($eventId)
    {
        return $this->prefix.'event:'.$eventId;
    }

    /**
     * Get the cache key for a channel's presence members.
     *
     * @param  string  $channel
     * @return string
     */
    public function presenceKey($channel)
    {
        return $this->prefix.'presence:'.$channel;
    }
}
