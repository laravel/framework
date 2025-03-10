<?php

namespace Illuminate\Support\Testing\Fakes;

use Illuminate\Config\Repository;
use Illuminate\Log\LogManager;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;
use Monolog\Handler\TestHandler;
use Monolog\LogRecord as MonologLogRecord;

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
     * Get the testing config set by the app's configuration.
     *
     * @return array<array-key, mixed>
     */
    protected function getTestingConfig(): array
    {
        return $this->app->make(Repository::class)->get('logging.channels.testing', []);
    }

    /**
     * @param  string  $name
     * @param  array|null  $config
     * @return \Monolog\Logger
     */
    #[\Override]
    protected function resolve($name, ?array $config = null)
    {
        return parent::resolve($name, array_merge(
            $config ?? $this->configurationFor($name) ?? [],
            $this->getTestingConfig(),
            [
                'name' => $name,
                'driver' => 'monolog',
                'handler' => TestHandler::class,
            ],
        ))->pushProcessor($this->useAppTimeForLogRecord(...));
    }

    /**
     * Use app's "now" for the LogRecord's datetime.
     *
     * @param  \Monolog\LogRecord  $logRecord
     * @return \Monolog\LogRecord
     */
    protected function useAppTimeForLogRecord(MonologLogRecord $logRecord): MonologLogRecord
    {
        return $logRecord->with(datetime: Date::now()->toImmutable());
    }

    /**
     * Get logs written to any configuration channel that pass an optional truth-test callback.
     *
     * @param  (\Closure(array<string, mixed>): bool)|null  $callback
     * @return Collection<int, array>
     */
    public function logged(?\Closure $callback = null): Collection
    {
        $logs = (new Collection($this->channels))
            ->flatMap(fn ($logger) => $logger->getHandlers()[0]->getRecords())
            ->map($this->mapLogRecordToArray(...));

        if ($callback === null) {
            return $logs;
        }

        return $logs->filter($callback);
    }

    /**
     * Get logs written a specified configuration channel which pass an optional truth-test callback.
     *
     * @param  (\Closure(array<string, mixed>): bool)|null  $callback
     * @param  string|null  $channel
     * @return Collection<int, array<string, mixed>>
     */
    public function loggedToChannel(?\Closure $callback = null, ?string $channel = null): Collection
    {
        $logs = (new Collection(
            $this->driver($channel)
                ->getHandlers()[0]
                ->getRecords()
        ))->map($this->mapLogRecordToArray(...));

        if ($callback === null) {
            return $logs;
        }

        return $logs->filter($callback);
    }

    /**
     * Convert LogRecord to an array.
     *
     * @param  \Monolog\LogRecord  $logRecord
     * @return array{"message": string, "context": array<array-key, mixed>, "level_int": 100|200|250|300|400|500|550|600, "level": "debug"|"info"|"notice"|"warning"|"error"|"critical"|"alert"|"emergency", "channel": string, "datetime": \DateTimeInterface, "extra": array<array-key, mixed>}
     */
    protected function mapLogRecordToArray(MonologLogRecord $logRecord): array
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
