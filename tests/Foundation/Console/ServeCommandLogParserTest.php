<?php

namespace Illuminate\Tests\Foundation\Console;

use Illuminate\Foundation\Console\ServeCommand;
use PHPUnit\Framework\TestCase;

class ServeCommandLogParserTest extends TestCase
{
    public function testExtractRequestPortWithValidLogLine()
    {
        $line = '[Mon Nov 19 10:30:45 2024] :8080 Info';

        $this->assertEquals(8080, ServeCommand::getRequestPortFromLine($line));
    }

    public function testExtractRequestPortWithValidLogLineAndExtraData()
    {
        $line = '[Mon Nov 19 10:30:45 2024] :3000 [Client Connected]';

        $this->assertEquals(3000, ServeCommand::getRequestPortFromLine($line));
    }

    public function testExtractRequestPortWithValidLogLineWithoutDate()
    {
        $line = ':5000 [Server Started]';

        $this->assertEquals(5000, ServeCommand::getRequestPortFromLine($line));
    }

    public function testExtractRequestPortWithMissingPort()
    {
        $line = '[Mon Nov 19 10:30:45 2024] Info';

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Failed to extract the request port. Ensure the log line contains a valid port: [Mon Nov 19 10:30:45 2024] Info');

        ServeCommand::getRequestPortFromLine($line);
    }

    public function testExtractRequestPortWithInvalidPortFormat()
    {
        $line = '[Mon Nov 19 10:30:45 2024] :abcd Info';

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Failed to extract the request port. Ensure the log line contains a valid port: [Mon Nov 19 10:30:45 2024] :abcd Info');

        ServeCommand::getRequestPortFromLine($line);
    }

    public function testExtractRequestPortWithEmptyLogLine()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Failed to extract the request port. Ensure the log line contains a valid port: ');

        ServeCommand::getRequestPortFromLine('');
    }

    public function testExtractRequestPortWithWhitespaceOnlyLine()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Failed to extract the request port. Ensure the log line contains a valid port: ');

        ServeCommand::getRequestPortFromLine('   ');
    }

    public function testExtractRequestPortWithRandomString()
    {
        $line = 'Random log entry without port';

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Failed to extract the request port. Ensure the log line contains a valid port: Random log entry without port');

        ServeCommand::getRequestPortFromLine($line);
    }
}
