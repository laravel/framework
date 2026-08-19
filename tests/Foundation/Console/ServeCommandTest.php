<?php

namespace Illuminate\Tests\Foundation\Console;

use Illuminate\Foundation\Console\ServeCommand;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArrayInput;

class ServeCommandTest extends TestCase
{
    #[DataProvider('hostAndPortProvider')]
    public function testHostAndPortParsing($host, $expectedHost, $expectedPort)
    {
        $command = new class extends ServeCommand
        {
            public function getHostAndPortForTesting()
            {
                return $this->getHostAndPort();
            }
        };

        $command->setInput(new ArrayInput(['--host' => $host], $command->getDefinition()));

        $this->assertSame([$expectedHost, $expectedPort], $command->getHostAndPortForTesting());
    }

    public static function hostAndPortProvider()
    {
        return [
            'hostname with port' => ['localhost:8888', 'localhost', '8888'],
            'IPv4 address with port' => ['127.0.0.1:8888', '127.0.0.1', '8888'],
            'IPv6 address with port' => ['[::1]:8888', '[::1]', '8888'],
            'hostname without port' => ['localhost', 'localhost', null],
            'IPv6 address without port' => ['[::1]', '[::1]', null],
        ];
    }
}
