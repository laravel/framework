<?php

namespace Illuminate\Log;

use ArrayAccess;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;
use LogicException;
use Monolog\LogRecord as MonologLogRecord;

class LogRecord implements ArrayAccess, Arrayable
{
    /**
     * @param  string  $message
     * @param  array<array-key, mixed>  $context
     * @param  "debug"|"info"|"notice"|"warning"|"error"|"critical"|"alert"|"emergency"  $level
     * @param  string  $channel
     * @param  CarbonInterface  $datetime
     * @param  array<array-key, mixed>  $extra
     * @param  string|null  $configurationChannel
     */
    public function __construct(
        public string $message,
        public array $context,
        public string $level,
        public string $channel,
        public CarbonInterface $datetime,
        public array $extra,
        public ?string $configurationChannel,
    ) {
    }

    public function offsetExists(mixed $offset): bool
    {
        return property_exists($this, $offset);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->{$offset};
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->{$offset} = $value;
    }

    public function offsetUnset(mixed $offset): void
    {
        throw new LogicException('Response data may not be mutated using array access.');
    }

    public static function fromMonolog(MonologLogRecord $logRecord): static
    {
        return new static(
            message: $logRecord->message,
            context: $logRecord->context,
            level: strtolower($logRecord->level->name),
            channel: $logRecord->channel,
            datetime: Date::make($logRecord->datetime),
            extra:  (new Collection($logRecord->extra))->except('__configuration_channel')->all(),
            configurationChannel: $logRecord->extra['__configuration_channel'] ?? null,
        );
    }

    public function toArray()
    {
        return [
            'message' => $this->message,
            'context' => $this->context,
            'level' => $this->level,
            'channel' => $this->channel,
            'datetime' => $this->datetime,
            'extra' => $this->extra,
            'configurationChannel' => $this->configurationChannel,
        ];
    }
}
