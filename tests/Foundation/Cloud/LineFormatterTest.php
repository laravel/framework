<?php

namespace Illuminate\Tests\Foundation\Cloud;

use DateTimeImmutable;
use Illuminate\Foundation\Cloud\LineFormatter;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class LineFormatterTest extends TestCase
{
    public function testItFormatsLogLevels()
    {
        $formatter = new LineFormatter;

        $this->assertSame(
            "\n  \033[37;44m INFO \033[39;49m This is info  \n\n",
            $formatter->format($this->createLogRecord('This is info')),
        );

        $this->assertSame(
            "\n  \033[30;43m WARNING \033[39;49m This is warning  \n\n",
            $formatter->format($this->createLogRecord('This is warning', Logger::WARNING, 'WARNING')),
        );

        $this->assertSame(
            "\n  \033[37;41m ERROR \033[39;49m This is error  \n\n",
            $formatter->format($this->createLogRecord('This is error', Logger::ERROR, 'ERROR')),
        );
    }

    public function testItCollapsesMessageNewlinesOntoOneLine()
    {
        $formatted = (new LineFormatter)->format($this->createLogRecord("one\ntwo"));

        $this->assertSame("\n  \033[37;44m INFO \033[39;49m one two  \n\n", $formatted);
    }

    public function testItAppendsContextAndExtra()
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

    public function testItNormalizesAnExceptionInTheContext()
    {
        $formatted = (new LineFormatter)->format($this->createLogRecord(
            context: ['exception' => new RuntimeException('boom')],
        ));

        $this->assertStringContainsString('[object] (RuntimeException(code: 0): boom', $formatted);
    }

    public function testItOmitsEmptyContextAndExtra()
    {
        $formatted = (new LineFormatter)->format($this->createLogRecord());

        $this->assertSame("\n  \033[37;44m INFO \033[39;49m Test message  \n\n", $formatted);
        $this->assertStringNotContainsString('[]', $formatted);
    }

    protected function createLogRecord(
        string $message = 'Test message',
        int $level = Logger::INFO,
        string $levelName = 'INFO',
        array $context = [],
        array $extra = [],
    ): array {
        return [
            'message' => $message,
            'context' => $context,
            'level' => $level,
            'level_name' => $levelName,
            'channel' => 'test',
            'datetime' => new DateTimeImmutable,
            'extra' => $extra,
        ];
    }
}
