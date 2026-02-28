<?php

namespace Illuminate\Broadcasting\Broadcasters;

use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class PollBroadcaster extends Broadcaster
{
    use UsePusherChannelConventions;

    /**
     * The cache repository instance.
     */
    protected Cache $cache;

    /**
     * The time-to-live for broadcast events in seconds.
     */
    protected int $ttl;

    /**
     * The cache key prefix.
     */
    protected string $prefix;

    /**
     * The presence timeout in seconds.
     */
    protected int $presenceTimeout;

    /**
     * Create a new broadcaster instance.
     */
    public function __construct(Cache $cache, int $ttl = 60, string $prefix = 'poll_broadcast:', int $presenceTimeout = 30)
    {
        $this->cache = $cache;
        $this->ttl = $ttl;
        $this->prefix = $prefix;
        $this->presenceTimeout = $presenceTimeout;
    }

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    public function broadcast(array $channels, $event, array $payload = [])
    {
        if (empty($channels)) {
            return;
        }

        $eventId = (string) Str::uuid7();
        $socket = Arr::pull($payload, 'socket');

        $this->cache->put($this->eventKey($eventId), [
            'id' => $eventId,
            'event' => $event,
            'data' => $payload,
            'socket' => $socket,
            'timestamp' => time(),
        ], $this->ttl);

        foreach ($this->formatChannels($channels) as $channel) {
            $this->atomic($this->channelKey($channel), function () use ($channel, $eventId) {
                $ids = $this->cache->get($this->channelKey($channel), []);

                $ids[] = $eventId;

                $this->cache->put($this->channelKey($channel), $ids, $this->ttl);
            });
        }
    }

    /**
     * Get events for the given channels since the last event ID.
     */
    public function getEvents(array $channels, ?string $lastEventId = null): array
    {
        if (is_null($lastEventId)) {
            return [
                'events' => [],
                'lastEventId' => (string) Str::uuid7(),
            ];
        }

        $cutoffId = (string) Str::uuid7(now()->subSeconds($this->ttl));
        $lastEventId = max($lastEventId, $cutoffId);

        $events = (new Collection($channels))
            ->flatMap(fn ($channel) => $this->getChannelEvents($channel, $lastEventId, $cutoffId))
            ->sortBy('id')
            ->values()
            ->all();

        return [
            'events' => $events,
            'lastEventId' => ! empty($events) ? end($events)['id'] : $lastEventId,
        ];
    }

    /**
     * Get new events for a single channel since the given last event ID.
     */
    protected function getChannelEvents(string $channel, string $lastEventId, string $cutoffId): array
    {
        $ids = $this->pruneExpiredChannelIds($channel, $cutoffId);

        return (new Collection($ids))
            ->filter(fn ($id) => strcmp($id, $lastEventId) > 0)
            ->map(fn ($id) => $this->cache->get($this->eventKey($id)))
            ->filter()
            ->map(fn ($event) => $event + ['channel' => $channel])
            ->values()
            ->all();
    }

    /**
     * Prune expired event IDs for a channel.
     */
    protected function pruneExpiredChannelIds(string $channel, string $cutoffId): array
    {
        return $this->atomic($this->channelKey($channel), function () use ($channel, $cutoffId) {
            $ids = new Collection($this->cache->get($this->channelKey($channel), []));

            $surviving = $ids->filter(fn ($id) => strcmp($id, $cutoffId) > 0)->values();

            if ($surviving->count() < $ids->count()) {
                $this->cache->put($this->channelKey($channel), $surviving->all(), $this->ttl);
            }

            return $surviving->all();
        });
    }

    /**
     * Update presence information for a user on a channel.
     */
    public function updatePresence(string $channel, mixed $userId, mixed $userInfo): array
    {
        return $this->atomic($this->presenceKey($channel), function () use ($channel, $userId, $userInfo) {
            $cutoff = time() - $this->presenceTimeout;

            $members = (new Collection($this->cache->get($this->presenceKey($channel), [])))
                ->put($userId, [
                    'user_id' => $userId,
                    'user_info' => $userInfo,
                    'last_seen' => time(),
                ])
                ->filter(fn ($member) => $member['last_seen'] >= $cutoff);

            $this->cache->put($this->presenceKey($channel), $members->all(), $this->ttl);

            return $members->map(fn ($member) => [
                'user_id' => $member['user_id'],
                'user_info' => $member['user_info'],
            ])->values()->all();
        });
    }

    /**
     * Execute a callback within a cache-based atomic lock.
     */
    protected function atomic(string $key, callable $callback, int $lockTimeout = 10, int $waitTimeout = 5): mixed
    {
        return $this->cache->withoutOverlapping($key.':lock', $callback, $lockTimeout, $waitTimeout);
    }

    /**
     * Get the cache key for a channel's event index.
     */
    public function channelKey(string $channel): string
    {
        return $this->prefix.$channel;
    }

    /**
     * Get the cache key for an event payload.
     */
    public function eventKey(string $eventId): string
    {
        return $this->prefix.'event:'.$eventId;
    }

    /**
     * Get the cache key for a channel's presence members.
     */
    public function presenceKey(string $channel): string
    {
        return $this->prefix.'presence:'.$channel;
    }
}
