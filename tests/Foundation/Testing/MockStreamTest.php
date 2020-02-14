<?php

namespace Illuminate\Tests\Foundation\Testing;

use ErrorException;
use Illuminate\Foundation\Testing\BufferedConsoleOutput;
use Illuminate\Foundation\Testing\MockStream;
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
            ->with('Taylor', false)
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

    public function testGetStreamReturnsAnOpenedResource()
    {
        MockStream::register(new BufferedConsoleOutput);
        $stream = MockStream::getStream();

        $this->assertIsResource($stream);

        fputs($stream, 'Taylor');
        $contents = stream_get_contents($stream);
        fclose($stream);

        $this->assertEquals('Taylor', $contents);
    }
}
