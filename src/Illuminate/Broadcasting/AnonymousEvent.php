<?php

namespace Illuminate\Broadcasting;

use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Support\Arr;

class AnonymousEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithBroadcasting, InteractsWithSockets;

    /**
     * The connection the event should be broadcast on.
     *
     * @var string|null
     */
    protected ?string $connection = null;

    /**
     * The name the event should be broadcast as.
     *
     * @var string|null
     */
    protected ?string $name = null;

    /**
     * The payload the event should be broadcast with.
     *
     * @var array<string, mixed>
     */
    protected array $payload = [];

    /**
     * Should the broadcast include the current user.
     *
     * @var bool
     */
    protected bool $includeCurrentUser = true;

    /**
     * Indicates if the event should be broadcast synchronously.
     *
     * @var bool
     */
    protected bool $shouldBroadcastNow = false;

    /**
     * Create a new anonymous broadcastable event instance.
     *
     * @param  \Illuminate\Broadcasting\Channel|array|string  $channels
     * @return void
     */
    public function __construct(protected Channel|array|string $channels)
    {
        $this->channels = Arr::wrap($channels);
    }

    /**
     * Set the connection the event should be broadcast on.
     *
     * @param  string  $connection
     * @return $this
     */
    public function via(string $connection): static
    {
        $this->connection = $connection;

        return $this;
    }

    /**
     * Set the name the event should be broadcast as.
     *
     * @param  string  $name
     * @return $this
     */
    public function as(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Set the payload the event should be broadcast with.
     *
     * @param  \Illuminate\Contracts\Support\Arrayable|array  $payload
     * @return $this
     */
    public function with(Arrayable|array $payload): static
    {
        $this->payload = $payload instanceof Arrayable
            ? $payload->toArray()
            : collect($payload)->map(
                fn ($p) => $p instanceof Arrayable ? $p->toArray() : $p
            )->all();

        return $this;
    }

    /**
     * Broadcast the event to everyone except the current user.
     *
     * @return $this
     */
    public function toOthers(): static
    {
        $this->includeCurrentUser = false;

        return $this;
    }

    /**
     * Broadcast the event.
     *
     * @return void
     */
    public function sendNow(): void
    {
        $this->shouldBroadcastNow = true;

        $this->send();
    }

    /**
     * Broadcast the event.
     *
     * @return void
     */
    public function send(): void
    {
        $broadcast = broadcast($this)->via($this->connection);

        if (! $this->includeCurrentUser) {
            $broadcast->toOthers();
        }
    }

    /**
     * Get the name the event should broadcast as.
     *
     * @return string
     */
    public function broadcastAs(): string
    {
        return $this->name ?: class_basename($this);
    }

    /**
     * Get the payload the event should broadcast with.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return $this->payload;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|\Illuminate\Broadcasting\Channel[]|string[]|string
     */
    public function broadcastOn(): Channel|array
    {
        return $this->channels;
    }

    /**
     * Determine if the event should be broadcast synchronously.
     *
     * @return bool
     */
    public function shouldBroadcastNow(): bool
    {
        return $this->shouldBroadcastNow;
    }
}
