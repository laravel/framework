<?php

namespace Illuminate\Tests\Testing;

use ErrorException;
use Illuminate\Testing\MockStream;
use Mockery;
use Orchestra\Testbench\TestCase;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\StreamOutput;

class MockStreamTest extends TestCase
{
    public function testMockStreamWritesToOutputInterface()
    {
        $mock = Mockery::mock(BufferedOutput::class.'[doWrite]')
            ->shouldAllowMockingProtectedMethods();

        $mock->shouldReceive('doWrite')
            ->with('Taylor', true)
            ->once();

        MockStream::register($mock);
        $stream = fopen('mock://stream', 'r+');
        $output = new StreamOutput($stream);
        $output->write('Taylor');
        MockStream::restore();
    }

    public function testMockStreamIsUnregisteredOnRestore()
    {
        MockStream::register(new BufferedOutput);
        MockStream::restore();

        $failed = false;
        try {
            fopen('mock://test', 'r+');
        } catch (ErrorException $e) {
            $failed = true;
        }

        $this->assertTrue($failed);
    }
}
