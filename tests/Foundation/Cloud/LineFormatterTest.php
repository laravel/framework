<?php

namespace Illuminate\Tests\Foundation\Cloud;

use DateTimeImmutable;
use Illuminate\Foundation\Cloud\LineFormatter;
use Monolog\Level;
use Monolog\LogRecord;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class LineFormatterTest extends TestCase
{
    public function test_it_formats_log_levels()
    {
        $formatter = new LineFormatter;

        $this->assertSame(
            "\n  \033[37;44m INFO \033[39;49m This is info  \n\n",
            $formatter->format($this->createLogRecord('This is info', Level::Info)),
        );

        $this->assertSame(
            "\n  \033[30;43m WARNING \033[39;49m This is warning  \n\n",
            $formatter->format($this->createLogRecord('This is warning', Level::Warning)),
        );

        $this->assertSame(
            "\n  \033[37;41m ERROR \033[39;49m This is error  \n\n",
            $formatter->format($this->createLogRecord('This is error', Level::Error)),
        );
    }

    public function test_it_collapses_message_newlines_onto_one_line()
    {
        $formatted = (new LineFormatter)->format($this->createLogRecord("one\ntwo"));

        $this->assertSame("\n  \033[37;44m INFO \033[39;49m one two  \n\n", $formatted);
    }

    public function test_it_appends_context_and_extra()
    {
        $formatted = (new LineFormatter)->format($this->createLogRecord(
            context: ['environment' => 'production'],
            extra: ['user_id' => 5],
        ));

        $this->assertSame(
            "\n  \033[37;44m INFO \033[39;49m Test message {\"environment\":\"production\"} {\"user_id\":5}\n\n",
            $formatted,
        );
    }

    public function test_it_normalizes_an_exception_in_the_context()
    {
        $formatted = (new LineFormatter)->format($this->createLogRecord(
            context: ['exception' => new RuntimeException('boom')],
        ));

        $this->assertStringContainsString('[object] (RuntimeException(code: 0): boom', $formatted);
    }

    public function test_it_omits_empty_context_and_extra()
    {
        $formatted = (new LineFormatter)->format($this->createLogRecord());

        $this->assertSame("\n  \033[37;44m INFO \033[39;49m Test message  \n\n", $formatted);
        $this->assertStringNotContainsString('[]', $formatted);
    }

    protected function createLogRecord(
        string $message = 'Test message',
        Level $level = Level::Info,
        array $context = [],
        array $extra = [],
    ): LogRecord {
        return new LogRecord(
            datetime: new DateTimeImmutable,
            channel: 'test',
            level: $level,
            message: $message,
            context: $context,
            extra: $extra,
        );
    }
}
