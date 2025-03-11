<?php

namespace Illuminate\Log;

use ArrayAccess;
use DateTimeInterface;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;
use LogicException;
use Monolog\LogRecord as MonologLogRecord;

class LogRecord implements ArrayAccess, Arrayable
{
    /**
     * Create the LogRecord instance.
     *
     * @param  string  $message  The message written.
     * @param  array<array-key, mixed>  $context  The context data written for the log.
     * @param  "debug"|"info"|"notice"|"warning"|"error"|"critical"|"alert"|"emergency"  $level  The log's level.
     * @param  string  $channel  The log channel name.
     * @param  \DateTimeInterface  $datetime  The datetime the log was recorded.
     * @param  array<array-key, mixed>  $extra  Additional data passed to the log record, including data from Context.
     * @param  string|null  $configurationChannel  The Laravel configuration channel used to write the log data.
     * @return void
     */
    public function __construct(
        public string $message,
        public array $context,
        public string $level,
        public string $channel,
        public DateTimeInterface $datetime,
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
        throw new LogicException('The log record properties may not be unset.');
    }

    /**
     * Convert from a Monolog LogRecord to an instance of Laravel's LogRecord.
     *
     * @param  MonologLogRecord  $logRecord
     * @return static
     */
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

    /**
     * Get an array representation of the LogRecord.
     *
     * @return array{"message": string, "context": array<array-key, mixed>, "level_int": 100|200|250|300|400|500|550|600, "level": "debug"|"info"|"notice"|"warning"|"error"|"critical"|"alert"|"emergency", "channel": string, "datetime": \DateTimeInterface, "extra": array<array-key, mixed>, "configurationChannel": ?string}
     */
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
