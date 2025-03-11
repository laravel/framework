<?php

namespace Illuminate\Tests\Support;

use Carbon\CarbonImmutable;
use Illuminate\Log\LogRecord;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Log;
use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\ExpectationFailedException;

class LogFakeTest extends TestCase
{
    public function test_logged_checks_all_channels()
    {
        Log::fake();

        Log::debug('hello');
        Log::channel('slack')->critical('oh no');
        Log::channel('stack')->info('hello');

        $loggedWithoutCallback = Log::logged();
        $this->assertCount(3, $loggedWithoutCallback);

        $loggedWithCallback = Log::logged(fn ($logRecord) => $logRecord['message'] === 'hello');

        $this->assertCount(2, $loggedWithCallback);
    }

    public function test_all_channels_write_to_test_handler()
    {
        config(['logging.default' => 'stack']);
        Log::fake();

        $this->travelTo('2025-03-09 11:11:00Z');
        Log::debug('hello');

        $this->travelTo('2025-03-09 11:11:10Z');
        Log::channel('single')->warning('Danger Will Robinson');
        $this->travelTo('2025-03-09 11:12:10Z');
        Log::channel('single')->info('all clear', ['value' => 'foo']);

        Log::channel('not-in-config')->alert('hi', ['contextual' => true]);
        Log::channel('slack')->critical('some slack message', ['album' => 'Marquee Moon']);

        $logsWrittenToDefault = Log::logged(fn ($logRecord) => $logRecord['configurationChannel'] === 'stack');
        $this->assertCount(1, $logsWrittenToDefault);
        $this->assertLogRecordArrayMatches([
            'message' => 'hello',
            'level' => 'debug',
            'channel' => 'stack',
            'datetime' => Carbon::parse('2025-03-09 11:11:00Z'),
        ], $logsWrittenToDefault[0]);

        $logsWrittenToSingle = Log::logged(fn ($logRecord) => $logRecord['configurationChannel'] === 'single');

        $this->assertCount(2, $logsWrittenToSingle);

        $this->assertLogRecordArrayMatches([
            'message' => 'Danger Will Robinson',
            'channel' => 'single',
            'level' => 'warning',
            'context' => [],
            'extra' => [],
            'datetime' => Carbon::parse('2025-03-09 11:11:10Z'),
        ], $logsWrittenToSingle->first());

        $this->assertLogRecordArrayMatches([
            'message' => 'all clear',
            'channel' => 'single',
            'level' => 'info',
            'context' => ['value' => 'foo'],
            'extra' => [],
            'datetime' => CarbonImmutable::parse('2025-03-09 11:12:10Z'),
        ], $logsWrittenToSingle[1]);

        $logsWrittenToNotInConfig = Log::logged(fn ($logRecord) => $logRecord['configurationChannel'] === 'not-in-config');
        $this->assertCount(1, $logsWrittenToNotInConfig);
        $this->assertLogRecordArrayMatches([
            'message' => 'hi',
            'channel' => 'not-in-config',
            'level' => 'alert',
            'context' => ['contextual' => true],
            'extra' => [],
            'datetime' => CarbonImmutable::parse('2025-03-09 11:12:10Z'),
        ], $logsWrittenToNotInConfig[0]);

        $logsWrittenToSlack = Log::logged(fn ($logRecord) => $logRecord['configurationChannel'] === 'slack');
        $this->assertCount(1, $logsWrittenToSlack);
        $this->assertLogRecordArrayMatches([
            'message' => 'some slack message',
            'context' => ['album' => 'Marquee Moon'],
            'level' => 'critical',
            'datetime' => CarbonImmutable::parse('2025-03-09 11:12:10Z'),
        ], $logsWrittenToSlack[0]);
    }

    public function test_it_respects_context_processor()
    {
        Log::fake();
        Context::add('artist', 'Television');
        Log::build([])->critical('Friction');

        $logs = Log::logged(fn ($logRecord) => $logRecord['configurationChannel'] === 'ondemand');
        $this->assertLogRecordArrayMatches([
            'channel' => 'ondemand',
            'extra' => ['artist' => 'Television'],
            'level' => 'critical',
        ], $logs[0]);
    }

    public function test_it_respects_config_level_of_underlying_channel()
    {
        config(['logging.channels.single.level' => 'warning']);
        Log::fake();

        Log::channel('single')->debug('you will not see me');
        Log::channel('single')->info('not visible');
        Log::channel('single')->warning('i should be visible');
        Log::channel('single')->critical('also visible');

        $logs = Log::logged();
        $this->assertCount(2, $logs);

        $this->assertEqualsCanonicalizing(['i should be visible', 'also visible'], $logs->pluck('message')->all());
    }

    public function test_assert_logged()
    {
        Log::fake();

        Log::channel('slack')->critical('oh no');

        Log::assertLogged('oh no');

        try {
            Log::assertLogged(fn ($logRecord) => $logRecord['level'] === 'info');
            $this->fail('No ExpectationFailedException was was thrown');
        } catch (ExpectationFailedException $e) {
            $this->assertStringContainsString('The expected log was not recorded.', $e->getMessage());
        }
    }

    private function assertLogRecordArrayMatches(array $expected, LogRecord $actual): void
    {
        $this->assertEqualsCanonicalizing(
            $expected,
            collect($actual->toArray())->only(array_keys($expected))->all()
        );
    }
}
