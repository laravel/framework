<?php

namespace Illuminate\Support\Testing\Fakes;

use Illuminate\Config\Repository;
use Illuminate\Log\LogManager;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;
use Illuminate\Tests\Integration\Foundation\FakeHandler;
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
        return parent::resolve($name, [...$this->getTestingConfig(), ...['name' => $name]])
            ->pushProcessor(function(LogRecord $logRecord): LogRecord {
                return $logRecord->with(datetime: Date::now()->toImmutable());
            }
        );
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

        $callback ??= fn () => true;

        return (new Collection($testHandler->getRecords()))
            ->filter($callback)
            ->map($this->mapLogRecordToArray(...));
    }

    /**
     * @param  LogRecord  $logRecord
     * @return array{"message": string, "context": array<array-key, mixed>, "level_int": 100|200|250|300|400|500|550|600, "level": "emergency"|"alert"|"critical"|"error"|"warning"|"notice"|"info"|"debug", "channel": string, "datetime": \DateTimeInterface, "extra": array<array-key, mixed>}
     */
    protected function mapLogRecordToArray(LogRecord $logRecord): array
    {
        $arr = $logRecord->toArray();
        $arr['level_int'] = $arr['level'];
        $arr['level'] = strtolower($arr['level_name']);
        unset($arr['level_name']);
        $arr['datetime'] = Date::make($arr['datetime']);

        return $arr;
    }
}
