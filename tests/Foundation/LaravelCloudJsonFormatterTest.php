<?php

namespace Illuminate\Tests\Foundation;

use Illuminate\Container\Container;
use Illuminate\Foundation\LaravelCloudJsonFormatter;
use Illuminate\Http\Request;
use Monolog\Level;
use Monolog\LogRecord;
use PHPUnit\Framework\TestCase;

class LaravelCloudJsonFormatterTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Container::setInstance(new Container);
    }

    protected function tearDown(): void
    {
        Container::setInstance(null);
        parent::tearDown();
    }

    protected function createRecord(): LogRecord
    {
        return new LogRecord(
            message: 'Test message',
            level: Level::Info,
            channel: 'test',
            datetime: new \DateTimeImmutable,
            extra: [],
            context: [],
        );
    }

    public function test_adds_cloud_request_id_as_top_level_key()
    {
        $app = Container::getInstance();
        $request = Request::create('/');
        $request->headers->set('X-Request-ID', '550e8400-e29b-41d4-a716-446655440000');
        $app->instance('request', $request);

        $formatter = new LaravelCloudJsonFormatter;
        $formatted = $formatter->format($this->createRecord());
        $decoded = json_decode($formatted, true);

        $this->assertEquals('550e8400-e29b-41d4-a716-446655440000', $decoded['cloud_request_id']);
    }

    public function test_does_not_add_field_when_no_request_bound()
    {
        $formatter = new LaravelCloudJsonFormatter;
        $formatted = $formatter->format($this->createRecord());
        $decoded = json_decode($formatted, true);

        $this->assertArrayNotHasKey('cloud_request_id', $decoded);
    }

    public function test_does_not_add_field_when_no_header_present()
    {
        $app = Container::getInstance();
        $request = Request::create('/');
        $app->instance('request', $request);

        $formatter = new LaravelCloudJsonFormatter;
        $formatted = $formatter->format($this->createRecord());
        $decoded = json_decode($formatted, true);

        $this->assertArrayNotHasKey('cloud_request_id', $decoded);
    }

    public function test_preserves_existing_log_fields()
    {
        $app = Container::getInstance();
        $request = Request::create('/');
        $request->headers->set('X-Request-ID', '6ba7b810-9dad-11d1-80b4-00c04fd430c8');
        $app->instance('request', $request);

        $record = new LogRecord(
            message: 'Test message',
            level: Level::Warning,
            channel: 'my-channel',
            datetime: new \DateTimeImmutable('2024-01-15 10:30:00'),
            extra: ['extra_field' => 'extra_value'],
            context: ['context_field' => 'context_value'],
        );

        $formatter = new LaravelCloudJsonFormatter;
        $formatted = $formatter->format($record);
        $decoded = json_decode($formatted, true);

        $this->assertEquals('Test message', $decoded['message']);
        $this->assertEquals('WARNING', $decoded['level_name']);
        $this->assertEquals('my-channel', $decoded['channel']);
        $this->assertEquals('extra_value', $decoded['extra']['extra_field']);
        $this->assertEquals('context_value', $decoded['context']['context_field']);
        $this->assertEquals('6ba7b810-9dad-11d1-80b4-00c04fd430c8', $decoded['cloud_request_id']);
    }
}
