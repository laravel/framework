<?php

namespace Illuminate\Tests\Foundation\Cloud;

use Illuminate\Foundation\Cloud\LineFormatter;
use Monolog\Level;
use Monolog\LogRecord;
use PHPUnit\Framework\TestCase;

class LineFormatterTest extends TestCase
{
    protected function createRecord(
        string $message = 'Test message',
        Level $level = Level::Info,
        array $context = [],
        array $extra = [],
    ): LogRecord {
        return new LogRecord(
            datetime: new \DateTimeImmutable,
            channel: 'test',
            level: $level,
            message: $message,
            context: $context,
            extra: $extra,
        );
    }

    public function test_formats_info_logs_with_a_blue_badge()
    {
        $formatted = (new LineFormatter)->format($this->createRecord('OutputLog command executed'));

        $this->assertSame("\033[44;97mINFO\033[0m OutputLog command executed  \n", $formatted);
    }

    public function test_formats_warning_and_error_badges()
    {
        $formatter = new LineFormatter;

        $this->assertSame(
            "\033[43;30mWARNING\033[0m Careful  \n",
            $formatter->format($this->createRecord('Careful', Level::Warning)),
        );

        $this->assertSame(
            "\033[41;97mERROR\033[0m Broken  \n",
            $formatter->format($this->createRecord('Broken', Level::Error)),
        );
    }

    public function test_preserves_ansi_escape_sequences_in_the_message()
    {
        $formatted = (new LineFormatter)->format($this->createRecord("\033[31mred\033[0m", Level::Error));

        $this->assertStringContainsString("\033[31mred\033[0m", $formatted);
        $this->assertStringNotContainsString('\\u001b', $formatted);
    }

    public function test_collapses_message_newlines_onto_one_line()
    {
        $formatted = (new LineFormatter)->format($this->createRecord("one\ntwo"));

        $this->assertSame("\033[44;97mINFO\033[0m one two  \n", $formatted);
        $this->assertSame(1, substr_count($formatted, "\n"));
    }

    public function test_appends_non_empty_context_and_extra()
    {
        $formatted = (new LineFormatter)->format($this->createRecord(
            context: ['command' => 'OutputLog'],
            extra: ['user_id' => 5],
        ));

        $this->assertSame(
            "\033[44;97mINFO\033[0m Test message {\"command\":\"OutputLog\"} {\"user_id\":5}\n",
            $formatted,
        );
    }

    public function test_normalizes_an_exception_in_the_context()
    {
        $formatted = (new LineFormatter)->format($this->createRecord(
            context: ['exception' => new \RuntimeException('boom')],
        ));

        $this->assertStringContainsString('[object] (RuntimeException(code: 0): boom', $formatted);
    }

    public function test_omits_empty_context_and_extra()
    {
        $formatted = (new LineFormatter)->format($this->createRecord());

        $this->assertSame("\033[44;97mINFO\033[0m Test message  \n", $formatted);
        $this->assertStringNotContainsString('[]', $formatted);
    }
}
