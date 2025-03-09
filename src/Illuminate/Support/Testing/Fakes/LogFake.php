<?php

namespace Illuminate\Support\Testing\Fakes;

use Illuminate\Config\Repository;
use Illuminate\Log\LogManager;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;
use Monolog\Handler\TestHandler;
use Monolog\LogRecord;

class LogFake extends LogManager implements Fake
{
    /**
     * Create a new LogFake instance.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @param  \Illuminate\Log\LogManager  $logManager  The original LogManager instance
     * @return void
     */
    public function __construct($app, public LogManager $logManager)
    {
        parent::__construct($app);
    }

    /**
     * Get the testing config set by the user, or default to using monolog's TestHandler.
     *
     * @return array<array-key, mixed>
     */
    protected function getTestingConfig(): array
    {
        return $this->app->make(Repository::class)->get('logging.channels.testing', [
            'driver' => 'monolog',
            'handler' => TestHandler::class,
        ]);
    }

    #[\Override]
    protected function resolve($name, ?array $config = null)
    {
        return parent::resolve($name, [
            ...($config ?? $this->configurationFor($name)),
            ...$this->getTestingConfig(),
            ...['name' => $name],
        ])->pushProcessor($this->useAppTimeForLogRecord(...));
    }

    /**
     * Use app's "now" for the LogRecord's datetime.
     *
     * @param  LogRecord  $logRecord
     * @return LogRecord
     */
    protected function useAppTimeForLogRecord(LogRecord $logRecord): LogRecord
    {
        return $logRecord->with(datetime: Date::now()->toImmutable());
    }

    /**
     * @param  (\Closure(\Monolog\LogRecord): bool)|null  $callback
     * @param  string|null  $channel
     * @return Collection<int, LogRecord>
     */
    public function logged(?\Closure $callback = null, ?string $channel = null): Collection
    {
        /** @var \Monolog\Handler\TestHandler $testHandler */
        $testHandler = $this->driver($channel)->getHandlers()[0];

        return (new Collection($testHandler->getRecords()))
            ->when($callback, fn ($collection) => $collection->filter($callback))
            ->map($this->mapLogRecordToArray(...));
    }

    /**
     * Convert LogRecord to an array.
     *
     * @param  LogRecord  $logRecord
     * @return array{"message": string, "context": array<array-key, mixed>, "level_int": 100|200|250|300|400|500|550|600, "level": "debug"|"info"|"notice"|"warning"|"error"|"critical"|"alert"|"emergency", "channel": string, "datetime": \DateTimeInterface, "extra": array<array-key, mixed>}
     */
    protected function mapLogRecordToArray(LogRecord $logRecord): array
    {
        return [
            'message' => $logRecord->message,
            'context' => $logRecord->context,
            'level_int' => $logRecord->level->value,
            'level' => strtolower($logRecord->level->name),
            'channel' => $logRecord->channel,
            'datetime' => Date::make($logRecord->datetime),
            'extra' => $logRecord->extra,
        ];
    }
}
