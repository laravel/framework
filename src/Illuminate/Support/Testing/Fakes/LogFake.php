<?php

namespace Illuminate\Support\Testing\Fakes;

use Closure;
use Illuminate\Config\Repository;
use Illuminate\Log\LogManager;
use Illuminate\Log\LogRecord;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;
use Monolog\Handler\TestHandler;
use Monolog\LogRecord as MonologLogRecord;
use PHPUnit\Framework\Assert as PHPUnit;

class LogFake extends LogManager implements Fake
{
    /**
     * Create a new LogFake instance.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app  The application instance.
     * @param  \Illuminate\Log\LogManager  $logManager  The original LogManager instance.
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
    protected function getTestingConfig()
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
        ))->pushProcessor(fn (MonologLogRecord $logRecord) => $logRecord->with(
            datetime: Date::now()->toImmutable(),
            extra: array_merge($logRecord->extra, ['__configuration_channel' => $name])
        ));
    }

    /**
     * Assert at least one log was written which passing a truth-test callback.
     *
     * @param  string|(\Closure(LogRecord): bool)  $callback
     * @return void
     */
    public function assertLogged($callback)
    {
        $callback = is_string($callback) ? (fn ($logRecord) => $logRecord->message === $callback) : $callback;

        PHPUnit::assertTrue(
            $this->logged($callback)->count() > 0,
            'The expected log was not recorded.'
        );
    }

    /**
     * Get logs written to any configuration channel that pass an optional truth-test callback.
     *
     * @param  (\Closure(\Illuminate\Log\LogRecord): bool)|null  $callback
     * @return Collection<int, \Illuminate\Log\LogRecord>
     */
    public function logged($callback = null)
    {
        $logs = (new Collection($this->channels))
            ->flatMap(fn ($logger) => $logger->getHandlers()[0]->getRecords())
            ->map(LogRecord::fromMonolog(...));

        if ($callback !== null) {
            $logs = $logs->filter($callback);
        }

        return $logs->values();
    }
}
