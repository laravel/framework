<?php

namespace Illuminate\Tests\Support;

use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Log;
use Orchestra\Testbench\TestCase;

class LogFakeTest extends TestCase
{
    public function test_all_channels_write_to_test_handler()
    {
        Log::fake();
        Log::debug('hello');
        Log::channel('single')->warning('Danger Will Robinson');
        Log::channel('single')->info('all clear', ['value' => 'foo']);
        Log::channel('not-in-config')->alert('hi', ['contextual' => true]);
        Log::channel('slack')->debug('some slack message', ['album' => 'Marquee Moon']);

        $logsWrittenToDefault = Log::logged();
        $this->assertCount(1, $logsWrittenToDefault);
        $this->assertSame('hello', $logsWrittenToDefault[0]['message']);
        $this->assertSame('debug', $logsWrittenToDefault[0]['level_name']);

        $logsWrittenToSingle = Log::logged(channel: 'single');
        $this->assertCount(2, $logsWrittenToSingle);

        $this->assertLogRecordArrayMatches([
            'message' => 'Danger Will Robinson',
            'channel' => 'single',
            'level_name' => 'warning',
            'context' => [],
            'extra' => [],
        ], $logsWrittenToSingle[0]);

        $this->assertLogRecordArrayMatches([
            'message' => 'all clear',
            'channel' => 'single',
            'level_name' => 'info',
            'context' => ['value' => 'foo'],
            'extra' => [],
        ], $logsWrittenToSingle[1]);

        $logsWrittenToNotInConfig = Log::logged(channel: 'not-in-config');
        $this->assertCount(1, $logsWrittenToNotInConfig);
        $this->assertLogRecordArrayMatches([
            'message' => 'hi',
            'channel' => 'not-in-config',
            'level_name' => 'alert',
            'context' => ['contextual' => true],
            'extra' => [],
        ], $logsWrittenToNotInConfig[0]);

        $logsWrittenToSlack = Log::logged(channel: 'slack');
        $this->assertCount(1, $logsWrittenToSlack);
        $this->assertLogRecordArrayMatches([
            'message' => 'some slack message',
            'context' => ['album' => 'Marquee Moon'],
        ], $logsWrittenToSlack[0]);
    }

    public function test_it_respects_context_processor()
    {
        Log::fake();
        Context::add('artist', 'Television');
        Log::build([])->critical('Friction');

        $logs = Log::logged(channel: 'ondemand');
        $this->assertLogRecordArrayMatches([
            'channel' => 'ondemand',
            'extra' => ['artist' => 'Television'],
            'level_name' => 'critical',
        ], $logs[0]);
    }

    private function assertLogRecordArrayMatches(array $expected, array $actual)
    {
        $this->assertEqualsCanonicalizing($expected, collect($actual)->only(array_keys($expected))->all());
    }
}
