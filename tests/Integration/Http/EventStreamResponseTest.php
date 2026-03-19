<?php

namespace Illuminate\Tests\Integration\Http;

use Exception;
use Illuminate\Http\StreamedEvent;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Orchestra\Testbench\TestCase;

class EventStreamResponseTest extends TestCase
{
    public function testEventStreamResponse()
    {
        Route::get('/stream', function () {
            return response()->eventStream(function () {
                yield new StreamedEvent(
                    event: 'update',
                    data: ['message' => 'hello'],
                );

                yield new StreamedEvent(
                    event: 'update',
                    data: ['message' => 'world'],
                );
            });
        });

        $response = $this->get('/stream');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/event-stream; charset=utf-8');
        $response->assertHeader('X-Accel-Buffering', 'no');

        $content = $response->streamedContent();

        $this->assertStringContainsString("event: update\n", $content);
        $this->assertStringContainsString('data: {"message":"hello"}', $content);
        $this->assertStringContainsString('data: {"message":"world"}', $content);
        $this->assertStringContainsString('data: </stream>', $content);
    }

    public function testEventStreamExceptionEmitsErrorEventOnStream()
    {
        Route::get('/stream', function () {
            return response()->eventStream(function () {
                yield new StreamedEvent(
                    event: 'update',
                    data: ['message' => 'hello'],
                );

                throw new Exception('Something went wrong during streaming');
            });
        });

        Log::shouldReceive('error')
            ->once()
            ->with('Something went wrong during streaming', \Mockery::type('array'));

        $response = $this->get('/stream');
        $content = $response->streamedContent();

        $this->assertStringContainsString("event: update\n", $content);
        $this->assertStringContainsString('data: {"message":"hello"}', $content);
        $this->assertStringContainsString("event: error\n", $content);
        $this->assertStringContainsString('data: Something went wrong during streaming', $content);
        $this->assertStringNotContainsString('data: </stream>', $content);
    }

    public function testEventStreamExceptionIsReportedToExceptionHandler()
    {
        Route::get('/stream', function () {
            return response()->eventStream(function () {
                throw new Exception('Test exception reporting');
            });
        });

        Log::shouldReceive('error')
            ->once()
            ->with('Test exception reporting', \Mockery::type('array'));

        $response = $this->get('/stream');
        $response->streamedContent();
    }
}
