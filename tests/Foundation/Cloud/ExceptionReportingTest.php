<?php

namespace Illuminate\Tests\Foundation\Cloud;

use Closure;
use Exception;
use Illuminate\Auth\GenericUser;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Cloud\Events;
use Illuminate\Foundation\CloudBootstrapper as Cloud;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Queue\Events\JobPopping;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Laravel\SerializableClosure\SerializableClosure;
use Orchestra\Testbench\Attributes\WithMigration;
use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use Ramsey\Uuid\Uuid;
use RuntimeException;
use stdClass;
use Symfony\Component\ErrorHandler\Error\FatalError;
use Throwable;

#[WithMigration]
#[WithMigration('laravel', 'queue')]
class ExceptionReportingTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected $serverSettingsToRestore = [];

    protected function setUp(): void
    {
        parent::setUp();

        foreach ([
            'LARAVEL_CLOUD',
            'LARAVEL_CLOUD_EXCEPTIONS',
            'REQUEST_TIME_FLOAT',
            'argv',
        ] as $key) {
            $this->serverSettingsToRestore[$key] = $_SERVER[$key] ?? '__undefined__';
        }

        $this->app->setBasePath(dirname($this->app->basePath(), levels: 4));
    }

    protected function tearDown(): void
    {
        foreach ($this->serverSettingsToRestore as $key => $value) {
            if ($value === '__undefined__') {
                unset($_SERVER[$key]);
            } else {
                $_SERVER[$key] = $value;
            }
        }

        parent::tearDown();
    }

    public function testItDoesNotEmitExceptionsByDefault(): void
    {
        $streams = $this->fakeEventsStreams();

        report(new RuntimeException('Whoops!'));
        $this->assertCount(0, $streams);

        $_SERVER['LARAVEL_CLOUD'] = '1';
        Cloud::registerEvents($this->app);
        Cloud::registerExceptionReporting($this->app);

        report(new RuntimeException('Whoops!'));
        $this->assertCount(0, $streams);

        // This config existing will activate exception reporting
        $_SERVER['LARAVEL_CLOUD_EXCEPTIONS'] = json_encode([]);
        Cloud::registerExceptionReporting($this->app);

        report(new RuntimeException('Whoops!'));
        $this->assertCount(1, $streams);
    }

    public function testItEmitsExceptionsAsEvents(): void
    {
        $this->freezeTime();
        $_SERVER['REQUEST_TIME_FLOAT'] = (float) now()->subMinute()->format('U.u');
        $this->setupExceptionReporting();
        $streams = $this->fakeEventsStreams();

        Route::get('/test', function () {
            $this->setRunningInConsole(false);
            report(new RuntimeException('Whoops!', code: 54));
        });
        $this->get('/test', ['Cloud-Request-ID' => '465ebb4e-2f86-434f-8e1b-cc364f317cef'])->assertOk();

        $this->assertCount(1, $streams);
        $streams[0]->assertWrittenJson(function (array $payload) {
            unset($payload['trace']); // we will test this in isolation

            $this->assertSame([
                '_cloud_event' => 'exception',
                'timestamp' => now()->format('Y-m-d H:i:s.u'),
                'exception_context' => [],
                'laravel_context' => [],
                'trace_id' => '465ebb4e-2f86-434f-8e1b-cc364f317cef',
                'execution_type' => 'request',
                'execution_context' => [
                    'timestamp' => now()->subMinute()->format('Y-m-d H:i:s.u'),
                    'headers' => [
                        'host' => ['localhost'],
                        'user-agent' => ['Symfony'],
                        'accept' => ['text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8'],
                        'accept-language' => ['en-us,en;q=0.5'],
                        'accept-charset' => ['ISO-8859-1,utf-8;q=0.7,*;q=0.7'],
                        'cloud-request-id' => ['465ebb4e-2f86-434f-8e1b-cc364f317cef'],
                    ],
                    'method' => 'GET',
                    'url' => 'http://localhost/test',
                    'ip' => '127.0.0.1',
                    'route' => [
                        'name' => null,
                        'methods' => [
                            0 => 'GET',
                            1 => 'HEAD',
                        ],
                        'domain' => null,
                        'path' => '/test',
                        'action' => 'Closure',
                    ],
                    'payload' => null,
                    'files' => null,
                ],
                'user_id' => null,
                'handled' => true,
                'class' => 'RuntimeException',
                'code' => '54',
                'message' => 'Whoops!',
                'previous' => [],
            ], $payload);

            return true;
        });
    }

    public function testItPreservesZeroFractionsInRequestPayload(): void
    {
        $this->setupExceptionReporting(['capture_request_payload' => true]);
        $streams = $this->fakeEventsStreams();

        Route::post('/test', function () {
            $this->setRunningInConsole(false);
            report(new RuntimeException('Whoops!'));
        });
        $this->post('/test', ['amount' => 5.0])->assertOk();

        $this->assertCount(1, $streams);
        $streams[0]->assertWritten(function (string $stream) {
            $this->assertStringContainsString('"amount":5.0', $stream);

            return true;
        });
    }

    public function testItDoesNotEscapeSlashesInRequestPayload(): void
    {
        $this->setupExceptionReporting(['capture_request_payload' => true]);
        $streams = $this->fakeEventsStreams();

        Route::post('/test', function () {
            $this->setRunningInConsole(false);
            report(new RuntimeException('Whoops!'));
        });
        $this->post('/test', ['path' => 'foo/bar'])->assertOk();

        $this->assertCount(1, $streams);
        $streams[0]->assertWritten(function (string $stream) {
            $this->assertStringContainsString('"path":"foo/bar"', $stream);

            return true;
        });
    }

    public function testItDoesNotEscapeUnicodeCharactersInRequestPayload(): void
    {
        $this->setupExceptionReporting(['capture_request_payload' => true]);
        $streams = $this->fakeEventsStreams();

        Route::post('/test', function () {
            $this->setRunningInConsole(false);
            report(new RuntimeException('Whoops!'));
        });
        $this->post('/test', ['name' => 'café', 'emoji' => '🎉'])->assertOk();

        $this->assertCount(1, $streams);
        $streams[0]->assertWritten(function (string $stream) {
            $this->assertStringContainsString('café', $stream);
            $this->assertStringContainsString('🎉', $stream);

            return true;
        });
    }

    public function testItCapturesTheRequestPayloadForFormRequests(): void
    {
        $this->setupExceptionReporting(['capture_request_payload' => true]);
        $streams = $this->fakeEventsStreams();

        Route::post('/test', function () {
            $this->setRunningInConsole(false);
            report(new RuntimeException('Whoops!'));
        });
        $this->post('/test', ['name' => 'Tim', 'nested' => ['key' => 'value']])->assertOk();

        $this->assertCount(1, $streams);
        $streams[0]->assertWrittenJson(function (array $payload) {
            $this->assertSame([
                'name' => 'Tim',
                'nested' => ['key' => 'value'],
            ], $payload['execution_context']['payload']);
            $this->assertSame([], $payload['execution_context']['files']);

            return true;
        });
    }

    public function testItCapturesTheRequestPayloadForJsonRequests(): void
    {
        $this->setupExceptionReporting(['capture_request_payload' => true]);
        $streams = $this->fakeEventsStreams();

        Route::post('/test', function () {
            $this->setRunningInConsole(false);
            report(new RuntimeException('Whoops!'));
        });
        $this->postJson('/test', ['name' => 'Tim', 'nested' => ['key' => 'value']])->assertOk();

        $this->assertCount(1, $streams);
        $streams[0]->assertWrittenJson(function (array $payload) {
            $this->assertSame([
                'name' => 'Tim',
                'nested' => ['key' => 'value'],
            ], $payload['execution_context']['payload']);
            $this->assertSame([], $payload['execution_context']['files']);

            return true;
        });
    }

    public static function nonObjectJsonPayloadProvider(): array
    {
        return [
            'boolean true' => [true, [true]],
            'boolean false' => [false, [false]],
            'string' => ['foo', ['foo']],
            'integer' => [42, [42]],
            'float' => [3.14, [3.14]],
            // `(array) null` is `[]`, so a bare `null` body ends up
            // indistinguishable from an empty array body.
            'null' => [null, []],
        ];
    }

    #[DataProvider('nonObjectJsonPayloadProvider')]
    public function testLaravelDoesNotSupportNonObjectJsonPayloads(mixed $value, array $expected): void
    {
        $this->setupExceptionReporting(['capture_request_payload' => true]);
        $streams = $this->fakeEventsStreams();

        Route::post('/test', function () {
            $this->setRunningInConsole(false);
            report(new RuntimeException('Whoops!'));
        });

        $content = json_encode($value);

        $this->call('POST', '/test', server: $this->transformHeadersToServerVars([
            'CONTENT_LENGTH' => strlen($content),
            'CONTENT_TYPE' => 'application/json',
        ]), content: $content)->assertOk();

        $this->assertCount(1, $streams);
        $streams[0]->assertWrittenJson(function (array $payload) use ($expected) {
            $this->assertSame($expected, $payload['execution_context']['payload']);

            return true;
        });
    }

    public function testItCapturesBinaryRequestPayloads(): void
    {
        $this->setupExceptionReporting(['capture_request_payload' => true]);
        $streams = $this->fakeEventsStreams();

        Route::post('/test', function () {
            $this->setRunningInConsole(false);
            report(new RuntimeException('Whoops!'));
        });
        $this->post('/test', ['binary' => hex2bin('abc123')])->assertOk();

        $this->assertCount(1, $streams);
        $streams[0]->assertWrittenJson(function (array $payload) {
            $this->assertSame('��#', $payload['execution_context']['payload']['binary']);

            return true;
        });
    }

    public function testItCapturesNonUtf8RequestPayloads(): void
    {
        $this->setupExceptionReporting(['capture_request_payload' => true]);
        $streams = $this->fakeEventsStreams();

        $latin1 = mb_convert_encoding('café', 'ISO-8859-1', 'UTF-8');

        $this->assertFalse(mb_check_encoding($latin1, 'UTF-8'));

        Route::post('/test', function () {
            $this->setRunningInConsole(false);
            report(new RuntimeException('Whoops!'));
        });
        $this->post('/test', ['key' => $latin1])->assertOk();

        $this->assertCount(1, $streams);
        $streams[0]->assertWrittenJson(function (array $payload) {
            $this->assertSame('caf�', $payload['execution_context']['payload']['key']);

            return true;
        });
    }

    public function testItRecursivelyRedactsNestedSensitiveRequestPayloadFields(): void
    {
        $this->setupExceptionReporting(['capture_request_payload' => true]);
        $streams = $this->fakeEventsStreams();

        Route::post('/test', function () {
            $this->setRunningInConsole(false);
            report(new RuntimeException('Whoops!'));
        });
        $this->post('/test', [
            'name' => 'Tim',
            'foo' => [
                'bar' => [
                    'password' => 'super-secret',
                    'name' => 'Tim',
                ],
            ],
        ])->assertOk();

        $this->assertCount(1, $streams);
        $streams[0]->assertWrittenJson(function (array $payload) {
            $this->assertSame([
                'name' => 'Tim',
                'foo' => [
                    'bar' => [
                        'password' => '[12 bytes redacted]',
                        'name' => 'Tim',
                    ],
                ],
            ], $payload['execution_context']['payload']);

            return true;
        });
    }

    public function testItRedactsSensitiveRequestPayloadFields(): void
    {
        $this->setupExceptionReporting(['capture_request_payload' => true]);
        $streams = $this->fakeEventsStreams();

        Route::post('/test', function () {
            $this->setRunningInConsole(false);
            report(new RuntimeException('Whoops!'));
        });
        $this->post('/test', [
            'name' => 'Tim',
            '_token' => 'csrf-token-value',
            'password' => 'super-secret',
            'password_confirmation' => 'super-secret-',
        ])->assertOk();

        $this->assertCount(1, $streams);
        $streams[0]->assertWrittenJson(function (array $payload) {
            $this->assertSame([
                'name' => 'Tim',
                '_token' => '[16 bytes redacted]',
                'password' => '[12 bytes redacted]',
                'password_confirmation' => '[13 bytes redacted]',
            ], $payload['execution_context']['payload']);
            $this->assertSame([], $payload['execution_context']['files']);

            return true;
        });
    }

    public function testItCanConfigureCustomRedactedRequestPayloadFields(): void
    {
        $this->setupExceptionReporting([
            'capture_request_payload' => true,
            'redact_request_payload_fields' => ['name', 'another'],
        ]);
        $streams = $this->fakeEventsStreams();

        Route::post('/test', function () {
            $this->setRunningInConsole(false);
            report(new RuntimeException('Whoops!'));
        });
        $this->post('/test', [
            'name' => 'Tim',
            'another' => 'One 😎',
            '_token' => 'csrf-token-value',
            'password' => 'super-secret',
            'password_confirmation' => 'super-secret-',
        ])->assertOk();

        $this->assertCount(1, $streams);
        $streams[0]->assertWrittenJson(function (array $payload) {
            $this->assertSame([
                'name' => '[3 bytes redacted]',
                'another' => '[8 bytes redacted]',
                '_token' => 'csrf-token-value',
                'password' => 'super-secret',
                'password_confirmation' => 'super-secret-',
            ], $payload['execution_context']['payload']);

            return true;
        });
    }

    public function testItCapturesUploadedFiles(): void
    {
        $this->setupExceptionReporting(['capture_request_payload' => true]);
        $streams = $this->fakeEventsStreams();

        Route::post('/test', function () {
            $this->setRunningInConsole(false);
            report(new RuntimeException('Whoops!'));
        });
        $this->post('/test', [
            'avatar' => \Illuminate\Http\UploadedFile::fake()->create('avatar.png', 12),
        ])->assertOk();

        $this->assertCount(1, $streams);
        $streams[0]->assertWrittenJson(function (array $payload) {
            $this->assertSame([], $payload['execution_context']['payload']);
            $this->assertSame([
                'avatar' => [
                    'originalName' => 'avatar.png',
                    'size' => 12288,
                    'error' => 0,
                ],
            ], $payload['execution_context']['files']);

            return true;
        });
    }

    public function testItCanDisableRequestPayloadCapture(): void
    {
        $this->setupExceptionReporting(['capture_request_payload' => false]);
        $streams = $this->fakeEventsStreams();

        Route::post('/test', function () {
            $this->setRunningInConsole(false);
            report(new RuntimeException('Whoops!'));
        });
        $this->post('/test', ['name' => 'Tim'])->assertOk();

        $this->assertCount(1, $streams);
        $streams[0]->assertWrittenJson(function (array $payload) {
            $this->assertNull($payload['execution_context']['payload']);
            $this->assertNull($payload['execution_context']['files']);

            return true;
        });
    }

    public function testRequestPayloadCaptureIsOffByDefault(): void
    {
        $this->setupExceptionReporting();
        $streams = $this->fakeEventsStreams();

        Route::post('/test', function () {
            $this->setRunningInConsole(false);
            report(new RuntimeException('Whoops!'));
        });
        $this->post('/test', ['name' => 'Tim'])->assertOk();

        $this->assertCount(1, $streams);
        $streams[0]->assertWrittenJson(function (array $payload) {
            $this->assertNull($payload['execution_context']['payload']);
            $this->assertNull($payload['execution_context']['files']);

            return true;
        });
    }

    public function testItSerializesRouteMethodsAsAJsonArray(): void
    {
        $this->setupExceptionReporting();
        $streams = $this->fakeEventsStreams();

        // Sorting ['PUT', 'GET', 'HEAD'] alphabetically reassigns the values
        // to non-sequential keys (1, 2, 0), which must be re-indexed before
        // the payload is emitted or it will be serialized as a JSON object
        // (e.g. {"1":"GET",...}) instead of a JSON array.
        Route::match(['PUT', 'GET'], '/test', function () {
            $this->setRunningInConsole(false);
            report(new RuntimeException('Whoops!'));
        });
        $this->get('/test')->assertOk();

        $this->assertCount(1, $streams);
        $streams[0]->assertWritten(function (string $stream) {
            $this->assertStringContainsString('"methods":["GET","HEAD","PUT"]', $stream);

            return true;
        });
    }

    public function testItCapturesExceptionContext(): void
    {
        $this->setupExceptionReporting();
        $streams = $this->fakeEventsStreams();

        // Callback based context...
        Exceptions::buildContextUsing(fn () => [
            'closure' => 'context',
        ]);

        // Handler based context. Defaults to userId so we will use that
        // rather than adding our own...
        Auth::setUser(new GenericUser([
            'id' => 'abc123',
        ]));

        // Exception attached context...
        $e = new ExceptionWithContext([
            'attached' => 'context',
        ]);

        report($e);

        $this->assertCount(1, $streams);
        $streams[0]->assertWrittenJsonContains([
            'exception_context' => [
                'attached' => 'context',
                'closure' => 'context',
                'userId' => 'abc123',
            ],
        ]);
    }

    public function testItCapturesEmptyExceptionContextWhenTheHandlerDoesNotSupportContextForException(): void
    {
        $this->app->instance(ExceptionHandler::class, new ExceptionHandlerWithoutContextForException);

        $this->setupExceptionReporting();
        $streams = $this->fakeEventsStreams();

        report(new RuntimeException('Whoops!'));

        $this->assertCount(1, $streams);
        $streams[0]->assertWrittenJsonContains([
            'exception_context' => [],
        ]);
    }

    public function testItCapturesEmptyExceptionContextWhenContextForExceptionThrows(): void
    {
        $this->app->instance(ExceptionHandler::class, new ExceptionHandlerThatThrowsFromContextForException);

        $this->setupExceptionReporting();
        $streams = $this->fakeEventsStreams();

        report(new RuntimeException('Whoops!'));

        $this->assertCount(1, $streams);
        $streams[0]->assertWrittenJsonContains([
            'exception_context' => [],
        ]);
    }

    public function testItCapturesLaravelContext(): void
    {
        $this->setupExceptionReporting();
        $streams = $this->fakeEventsStreams();

        Context::add('laravel', 'context');
        Context::addHidden('hidden', 'context');

        report(new RuntimeException('Whoops!'));

        $this->assertCount(1, $streams);
        $streams[0]->assertWrittenJsonContains([
            'laravel_context' => [
                'laravel' => 'context',
            ],
        ]);
    }

    public function testItMarksExceptionsAsHandled(): void
    {
        $this->setupExceptionReporting();
        $streams = $this->fakeEventsStreams();

        Route::get('/unhandled', function () {
            throw new RuntimeException('Whoops!');
        });
        Route::get('/handled', function () {
            report(new RuntimeException('Whoops!'));
        });

        $this->get('/unhandled')->assertServerError();
        $this->assertCount(1, $streams);
        $streams[0]->assertWrittenJsonContains([
            'handled' => false,
        ], write: 0);

        $this->get('/handled')->assertOk();
        $streams[0]->assertWrittenJsonContains([
            'handled' => true,
        ], write: 1);
    }

    public function testItNormalizesViewExceptions(): void
    {
        $this->setupExceptionReporting();
        $streams = $this->fakeEventsStreams();
        (fn () => $this->namespace = 'App')->call($this->app);
        Blade::anonymousComponentPath(__DIR__.'/components', 'tests');

        try {
            view()->file(__DIR__.'/foo.blade.php')->render();
        } catch (Throwable $e) {
            report($e);
        }

        $this->assertCount(1, $streams);
        $streams[0]->assertWritten(function ($stream) {
            $writes = explode("\n", $stream, 2);
            $this->assertCount(2, $writes);

            [
                $reportedInView,
                $thrownInView,
            ] = array_map(fn ($payload) => json_decode($payload, associative: true, flags: JSON_THROW_ON_ERROR), $writes);

            $this->assertSame([
                'file' => 'vendor/orchestra/testbench-core/laravel/storage/framework/views/eb85da9afb77a41f4af911a05a42817b.php',
                'line' => 4,
            ], $reportedInView['trace'][0]);
            $this->assertSame([
                'file' => 'tests/Foundation/Cloud/components/profile.blade.php',
                'line' => 3,
                'compiled_view' => 'vendor/orchestra/testbench-core/laravel/storage/framework/views/eb85da9afb77a41f4af911a05a42817b.php',
            ], $thrownInView['trace'][0]);

            $this->assertSame([
                'file' => 'vendor/orchestra/testbench-core/laravel/storage/framework/views/5158369fa173396ed4292b6aa122f8fa.php',
                'line' => 12,
            ], Arr::except($reportedInView['trace'][9], ['function', 'class', 'type', 'args']));
            $this->assertSame([
                'file' => 'tests/Foundation/Cloud/foo.blade.php',
                'line' => 3,
                'compiled_view' => 'vendor/orchestra/testbench-core/laravel/storage/framework/views/5158369fa173396ed4292b6aa122f8fa.php',
            ], Arr::except($thrownInView['trace'][9], ['function', 'class', 'type', 'args']));

            unset($reportedInView['trace'][0], $thrownInView['trace'][0], $thrownInView['trace'][9], $reportedInView['trace'][9]);

            $this->assertSame($reportedInView['trace'], $thrownInView['trace']);

            return true;
        });
    }

    public function testReportingViewExceptionStateIsNotClobberedByANestedReportCall(): void
    {
        $this->setupExceptionReporting();
        $streams = $this->fakeEventsStreams();

        $triggered = false;
        Exceptions::buildContextUsing(function () use (&$triggered) {
            if (! $triggered) {
                $triggered = true;
                report(new RuntimeException('Nested, unrelated exception'));
            }

            return [];
        });

        try {
            view()->file(__DIR__.'/reentrancy.blade.php')->render();
        } catch (Throwable $e) {
            report($e);
        }

        $this->assertCount(1, $streams);
        $streams[0]->assertWritten(function (string $stream) {
            $writes = array_map(
                fn ($line) => json_decode($line, associative: true, flags: JSON_THROW_ON_ERROR),
                explode("\n", trim($stream))
            );

            [$nested, $viewException] = $writes;

            $this->assertSame('Nested, unrelated exception', $nested['message']);
            $this->assertSame('RuntimeException', $viewException['class']);

            // The outer exception is a genuine view exception; its first
            // trace frame should be mapped back to the original
            // .blade.php source via the compiled-view lookup, not left
            // pointing at the compiled cache file.
            $this->assertArrayHasKey('compiled_view', $viewException['trace'][0]);
            $this->assertStringEndsWith('reentrancy.blade.php', $viewException['trace'][0]['file']);

            return true;
        });
    }

    public function testItNormalizesSqsQueueNames(): void
    {
        $this->freezeTime();
        $this->setupExceptionReporting();
        $streams = $this->fakeEventsStreams();
        Config::set('queue.default', 'database');

        ExceptionReportingJobThatReportsException::dispatch(fn () => Config::set('queue.connections.database', [
            'driver' => 'sqs',
            'prefix' => 'https://sqs.us-east-1.amazonaws.com/your-account-id',
            'queue' => 'queue-name',
            'suffix' => '-production',
        ]))->onQueue('https://sqs.us-east-1.amazonaws.com/your-account-id/queue-name-production');
        $job = json_decode(DB::table('jobs')->soleValue('payload'), flags: JSON_THROW_ON_ERROR);

        Artisan::call('queue:work', [
            '--queue' => 'https://sqs.us-east-1.amazonaws.com/your-account-id/queue-name-production',
            '--max-jobs' => 1,
            '--sleep' => 0,
            '--stop-when-empty' => true,
            '--tries' => 1,
        ]);

        $this->assertCount(1, $streams);
        $streams[0]->assertWrittenJson(function (array $json) use ($job) {
            $this->assertTrue(Str::isUuid($json['execution_context']['attempt_id']));
            unset($json['execution_context']['attempt_id']);

            $this->assertSame([
                'timestamp' => now()->format('Y-m-d H:i:s.u'),
                'attempt' => 1,
                'uuid' => $job->uuid,
                'name' => ExceptionReportingJobThatReportsException::class,
                'connection' => 'database',
                'queue' => 'queue-name',
            ], $json['execution_context']);

            return true;
        });
    }

    public function testItNormalizesCloudQueueNames(): void
    {
        $this->freezeTime();
        $this->setupExceptionReporting();
        $streams = $this->fakeEventsStreams();
        Config::set('queue.default', 'database');

        ExceptionReportingJobThatReportsException::dispatch(fn () => Config::set('queue.connections.database', [
            'driver' => 'cloud',
            'queue' => 'queue-name',
            'connection' => [
                'driver' => 'sqs',
                'prefix' => 'https://sqs.us-east-1.amazonaws.com/your-account-id',
                'queue' => 'queue-name',
                'suffix' => '-production',
            ],
        ]))->onQueue('https://sqs.us-east-1.amazonaws.com/your-account-id/queue-name-production');
        $job = json_decode(DB::table('jobs')->soleValue('payload'), flags: JSON_THROW_ON_ERROR);

        Artisan::call('queue:work', [
            '--queue' => 'https://sqs.us-east-1.amazonaws.com/your-account-id/queue-name-production',
            '--max-jobs' => 1,
            '--sleep' => 0,
            '--stop-when-empty' => true,
            '--tries' => 1,
        ]);

        $this->assertCount(1, $streams);
        $streams[0]->assertWrittenJson(function (array $json) use ($job) {
            $this->assertTrue(Str::isUuid($json['execution_context']['attempt_id']));
            unset($json['execution_context']['attempt_id']);

            $this->assertSame([
                'timestamp' => now()->format('Y-m-d H:i:s.u'),
                'attempt' => 1,
                'uuid' => $job->uuid,
                'name' => ExceptionReportingJobThatReportsException::class,
                'connection' => 'database',
                'queue' => 'queue-name',
            ], $json['execution_context']);

            return true;
        });
    }

    public function testItCapturesPreviousExceptions(): void
    {
        $this->setupExceptionReporting();
        $streams = $this->fakeEventsStreams();

        $line = __LINE__ + 1;
        report(new RuntimeException(
            'Whoops!',
            previous: new RuntimeException('Previous!')),
        );

        $this->assertCount(1, $streams);
        $streams[0]->assertWrittenJson(function (array $payload) use ($line) {
            $this->assertSame('Whoops!', $payload['message']);
            $this->assertIsList($payload['previous']);
            $this->assertCount(1, $payload['previous']);

            $previous = $payload['previous'][0];

            $this->assertSame([
                'file' => 'tests/Foundation/Cloud/ExceptionReportingTest.php',
                'line' => $line + 2,
            ], $previous['trace'][0]);
            unset($previous['trace']);

            $this->assertSame([
                'class' => 'RuntimeException',
                'code' => '0',
                'message' => 'Previous!',
            ], $previous);

            return true;
        });
    }

    public function testItFlattensPreviousExceptions(): void
    {
        $this->setupExceptionReporting();
        $streams = $this->fakeEventsStreams();

        $line = __LINE__ + 1;
        report(new RuntimeException(
            'Whoops!',
            previous: new RuntimeException(
                'Previous!',
                previous: new RuntimeException('Previous Previous!')
            )
        ));

        $this->assertCount(1, $streams);
        $streams[0]->assertWrittenJson(function ($payload) use ($line) {
            $this->assertSame('Whoops!', $payload['message']);
            $this->assertIsList($payload['previous']);
            $this->assertCount(2, $payload['previous']);

            $previous = $payload['previous'][0];
            $this->assertSame([
                'file' => 'tests/Foundation/Cloud/ExceptionReportingTest.php',
                'line' => $line + 2,
            ], $previous['trace'][0]);
            unset($previous['trace']);
            $this->assertSame([
                'class' => 'RuntimeException',
                'code' => '0',
                'message' => 'Previous!',
            ], $previous);

            $previous = $payload['previous'][1];
            $this->assertSame([
                'file' => 'tests/Foundation/Cloud/ExceptionReportingTest.php',
                'line' => $line + 4,
            ], $previous['trace'][0]);
            unset($previous['trace']);
            $this->assertSame([
                'class' => 'RuntimeException',
                'code' => '0',
                'message' => 'Previous Previous!',
            ], $previous);

            return true;
        });
    }

    public function testItCapturesUserIdInRequests(): void
    {
        $this->setupExceptionReporting();
        $streams = $this->fakeEventsStreams();

        Route::get('/test', fn () => throw new RuntimeException('Whoops!'));
        $this->actingAs(new GenericUser(['id' => 'abc123']))
            ->get('/test')
            ->assertServerError();

        $this->assertCount(1, $streams);
        $streams[0]->assertWrittenJsonContains([
            'user_id' => 'abc123',
        ]);
    }

    public function testItFallsBackToNullWhenTheUserCannotBeResolved(): void
    {
        $this->setupExceptionReporting();
        $streams = $this->fakeEventsStreams();

        Auth::setUser(new UserThatThrowsWhenItsAuthIdentifierIsRetrieved);

        report(new RuntimeException('Whoops!'));

        $this->assertCount(1, $streams);
        $streams[0]->assertWrittenJsonContains([
            'user_id' => null,
        ]);
    }

    public function testItCapturesUserIdInRequestsAfterLogout(): void
    {
        $this->markTestIncomplete('TODO');
    }

    public function testItDoesntCauseRecursionWhenRetrievingUserId(): void
    {
        $this->markTestIncomplete('TODO');
    }

    public function testItCapturesUserIdInConsole(): void
    {
        $this->setupExceptionReporting();
        $streams = $this->fakeEventsStreams();

        Artisan::command('test-command', function () {
            $_SERVER['argv'] = ['artisan', 'test-command'];
            Auth::setUser(new GenericUser(['id' => 'abc123']));
            report(new RuntimeException('Whoops!'));
        });

        $this->artisan('test-command')->assertOk();

        $this->assertCount(1, $streams);
        $streams[0]->assertWrittenJsonContains([
            'user_id' => 'abc123',
        ]);
    }

    public function testItCapturesUserIdInJobs(): void
    {
        $this->markTestIncomplete('TODO');
    }

    public function testItCapturesTraceIdInRequests(): void
    {
        $this->setupExceptionReporting();
        $streams = $this->fakeEventsStreams();

        Route::get('/test', function () {
            $this->setRunningInConsole(false);
            throw new RuntimeException('Whoops!');
        });
        $this->get('/test', ['Cloud-Request-ID' => '465ebb4e-2f86-434f-8e1b-cc364f317cef'])->assertServerError();

        $this->assertCount(1, $streams);
        $streams[0]->assertWrittenJsonContains([
            'trace_id' => '465ebb4e-2f86-434f-8e1b-cc364f317cef',
        ]);
    }

    public function testItAlwaysGetsTheCurrentRequestsTraceId(): void
    {
        $this->setupExceptionReporting();
        $streams = $this->fakeEventsStreams();

        Route::get('/test', function () {
            $this->setRunningInConsole(false);
            throw new RuntimeException('Whoops!');
        });
        $this->get('/test', ['Cloud-Request-ID' => '465ebb4e-2f86-434f-8e1b-cc364f317cef'])->assertServerError();
        $this->get('/test', ['Cloud-Request-ID' => '7bb3b9d5-6a54-42c3-8ed4-daf2fc0189dd'])->assertServerError();

        $this->assertCount(1, $streams);
        $streams[0]->assertWrittenJsonContains([
            'trace_id' => '465ebb4e-2f86-434f-8e1b-cc364f317cef',
        ], write: 0);
        $streams[0]->assertWrittenJsonContains([
            'trace_id' => '7bb3b9d5-6a54-42c3-8ed4-daf2fc0189dd',
        ], write: 1);
    }

    public function testItCapturesTraceIdInCommands(): void
    {
        $this->setupExceptionReporting();
        $streams = $this->fakeEventsStreams();

        Artisan::command('test-command', function () {
            $_SERVER['argv'] = ['artisan', 'test-command'];
            report(new RuntimeException('Whoops!'));
        });

        $this->artisan('test-command')->assertOk();

        $this->assertCount(1, $streams);
        $streams[0]->assertWrittenJson(function (array $payload) {
            $this->assertNotEmpty($payload['trace_id']);
            $this->assertTrue(Uuid::isValid($payload['trace_id']));

            return true;
        });
    }

    public function testItReusesTraceIdInCommands(): void
    {
        $this->setupExceptionReporting();
        $streams = $this->fakeEventsStreams();

        Artisan::command('test-command', function () {
            $_SERVER['argv'] = ['artisan', 'test-command'];
            report(new RuntimeException('Whoops!'));
            report(new RuntimeException('Whoops!'));
        });

        $this->artisan('test-command')->assertOk();

        $this->assertCount(1, $streams);
        $streams[0]->assertWritten(function (string $stream) {
            $writes = explode("\n", $stream, 2);
            $this->assertCount(2, $writes);

            [
                $first,
                $second,
            ] = array_map(fn ($payload) => json_decode($payload, associative: true, flags: JSON_THROW_ON_ERROR), $writes);

            $this->assertSame($first['trace_id'], $second['trace_id']);

            return true;
        });
    }

    public function testItCapturesTraceIdInJobs(): void
    {
        $this->markTestIncomplete('TODO');
    }

    public function testItCapturesTraceIdInScheduledTasks(): void
    {
        $this->markTestIncomplete('TODO');
    }

    public function testItCapturesExecutionSourceInCommands(): void
    {
        $this->markTestIncomplete('TODO');
    }

    public function testItCapturesExecutionSourceInJobs(): void
    {
        $this->markTestIncomplete('TODO');
    }

    public function testItCapturesExecutionSourceInScheduledTasks(): void
    {
        $this->markTestIncomplete('TODO');
    }

    public function testItCapturesRequestExecutionContext(): void
    {
        $this->freezeTime();
        $_SERVER['REQUEST_TIME_FLOAT'] = (float) now()->subMinute()->format('U.u');
        $this->setupExceptionReporting();
        $streams = $this->fakeEventsStreams();

        Route::domain('{product}.laravel.com')->get('/users/{user}', function () {
            $this->setRunningInConsole(false);
            throw new RuntimeException('Whoops!');
        })->name('users.show');
        $this->get('http://cloud.laravel.com/users/123', ['X-Custom-Header' => ['first', 'second']])->assertServerError();

        $this->assertCount(1, $streams);
        $streams[0]->assertWrittenJsonContains([
            'execution_context' => [
                'timestamp' => now()->subMinute()->format('Y-m-d H:i:s.u'),
                'headers' => [
                    'host' => ['cloud.laravel.com'],
                    'user-agent' => ['Symfony'],
                    'accept' => ['text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8'],
                    'accept-language' => ['en-us,en;q=0.5'],
                    'accept-charset' => ['ISO-8859-1,utf-8;q=0.7,*;q=0.7'],
                    'x-custom-header' => ['first', 'second'],
                ],
                'method' => 'GET',
                'url' => 'http://cloud.laravel.com/users/123',
                'ip' => '127.0.0.1',
                'route' => [
                    'name' => 'users.show',
                    'methods' => ['GET', 'HEAD'],
                    'domain' => '{product}.laravel.com',
                    'path' => '/users/{user}',
                    'action' => 'Closure',
                ],
                'payload' => null,
                'files' => null,
            ],
        ]);
    }

    public function testItRedactsSensitiveHeaders(): void
    {
        $this->markTestIncomplete('TODO');
    }

    public function testItCanDisableAndEnableHeaderCapture(): void
    {
        $this->markTestIncomplete('TODO');
    }

    public function testItCanDisableIPCapture(): void
    {
        $this->markTestIncomplete('TODO');
    }

    public function testItCapturesJobExecutionContext(): void
    {
        $this->freezeTime();
        $this->setupExceptionReporting();
        $streams = $this->fakeEventsStreams();
        Config::set('queue.default', 'database');

        ExceptionReportingJobThatReportsException::dispatch(fn () => Date::setTestNow(now()->addMinutes(1)));
        $this->assertCount(0, $streams);

        $job = json_decode(DB::table('jobs')->soleValue('payload'), flags: JSON_THROW_ON_ERROR);

        Artisan::call('queue:work', [
            '--max-jobs' => 1,
            '--sleep' => 0,
            '--stop-when-empty' => true,
            '--tries' => 1,
        ]);

        $this->assertCount(1, $streams);
        $streams[0]->assertWrittenJson(function ($json) use ($job) {
            $this->assertSame('job', $json['execution_type']);

            $this->assertTrue(Str::isUuid($json['execution_context']['attempt_id']));
            unset($json['execution_context']['attempt_id']);

            $this->assertSame([
                'timestamp' => now()->subMinute()->format('Y-m-d H:i:s.u'),
                'attempt' => 1,
                'uuid' => $job->uuid,
                'name' => ExceptionReportingJobThatReportsException::class,
                'connection' => 'database',
                'queue' => 'default',
            ], $json['execution_context']);

            return true;
        });
    }

    public function testItCapturesTheRawSerializableClosureStreamInTracesForQueuedClosures(): void
    {
        $this->setupExceptionReporting();
        $streams = $this->fakeEventsStreams();
        Config::set('queue.default', 'database');

        dispatch(function ($foo = null) {
            throw new RuntimeException('Whoops from a queued closure!');
        });

        Artisan::call('queue:work', [
            '--max-jobs' => 1,
            '--sleep' => 0,
            '--stop-when-empty' => true,
            '--tries' => 1,
        ]);

        $this->assertCount(1, $streams);
        $streams[0]->assertWrittenJson(function ($json) {
            // Laravel\SerializableClosure reconstructs the closure via a
            // synthetic "laravel-serializable-closure://" stream wrapper
            // whose "path" is the closure's entire source code. This
            // documents that the trace's "file" (and the next frame's
            // "function", via PHP's "{closure:FILE:LINE}" naming) both
            // currently surface that raw source, newlines included,
            // rather than a normal-looking file path.

            $this->assertSame([
                'file' => "laravel-serializable-closure://function (\$foo = null) {\n            throw new \\RuntimeException('Whoops from a queued closure!');\n        }",
                'line' => 3,
            ], $json['trace'][0]);

            $this->assertSame([
                'file' => 'src/Illuminate/Container/BoundMethod.php',
                'line' => 36,
                'function' => "{closure:laravel-serializable-closure://function (\$foo = null) {\n            throw new \\RuntimeException('Whoops from a queued closure!');\n        }:2}",
                'class' => 'Illuminate\Tests\Foundation\Cloud\ExceptionReportingTest',
                'type' => '::',
                'args' => ['types' => ['null', 'Illuminate\Queue\CallQueuedClosure']],
            ], $json['trace'][1]);

            return true;
        });
    }

    public function testExceptionsBetweenJobsAreAttributtedToTheQueueWorkCommand(): void
    {
        $this->freezeTime();
        $this->setupExceptionReporting();
        $streams = $this->fakeEventsStreams();
        Config::set('queue.default', 'database');
        $_SERVER['REQUEST_TIME_FLOAT'] = (float) now()->format('U.u');
        $_SERVER['argv'] = ['artisan', 'queue:work'];

        ExceptionReportingJobThatReportsException::dispatch(fn () => Date::setTestNow(now()->addMinutes(1)));
        $firstJob = json_decode(DB::table('jobs')->soleValue('payload'), flags: JSON_THROW_ON_ERROR);
        ExceptionReportingJobThatReportsException::dispatch(fn () => Date::setTestNow(now()->addMinutes(1)));
        $secondJob = json_decode(DB::table('jobs')->where('payload', 'not like', "%$firstJob->uuid%")->soleValue('payload'), flags: JSON_THROW_ON_ERROR);
        $this->assertCount(0, $streams);

        $popped = false;
        Event::listen(function (JobPopping $event) use (&$popped) {
            if ($popped) {
                report(new RuntimeException('Whoops!'));
            }

            $popped = true;
        });

        $this->travel(1)->minute();
        Artisan::call('queue:work', [
            '--max-jobs' => 2,
            '--sleep' => 0,
            '--stop-when-empty' => true,
            '--tries' => 1,
        ]);

        $this->assertCount(1, $streams);
        $streams[0]->assertWritten(function (string $stream) use ($firstJob, $secondJob) {
            $writes = explode("\n", $stream, 3);
            $this->assertCount(3, $writes);

            [
                $firstWrite,
                $secondWrite,
                $thirdWrite,
            ] = array_map(fn ($payload) => json_decode($payload, associative: true, flags: JSON_THROW_ON_ERROR), $writes);

            // First job

            $this->assertSame('job', $firstWrite['execution_type']);

            $this->assertTrue(Str::isUuid($firstWrite['execution_context']['attempt_id']));
            unset($firstWrite['execution_context']['attempt_id']);

            $this->assertSame([
                'timestamp' => now()->subMinutes(2)->format('Y-m-d H:i:s.u'),
                'attempt' => 1,
                'uuid' => $firstJob->uuid,
                'name' => ExceptionReportingJobThatReportsException::class,
                'connection' => 'database',
                'queue' => 'default',
            ], $firstWrite['execution_context']);

            // Command

            $this->assertSame('command', $secondWrite['execution_type']);

            $this->assertSame([
                'timestamp' => now()->subMinutes(3)->format('Y-m-d H:i:s.u'),
                'name' => 'queue:work',
                'class' => \Illuminate\Queue\Console\WorkCommand::class,
                'command' => 'queue:work',
            ], $secondWrite['execution_context']);

            // Second job

            $this->assertSame('job', $thirdWrite['execution_type']);

            $this->assertTrue(Str::isUuid($thirdWrite['execution_context']['attempt_id']));
            unset($thirdWrite['execution_context']['attempt_id']);

            $this->assertSame([
                'timestamp' => now()->subMinutes(1)->format('Y-m-d H:i:s.u'),
                'attempt' => 1,
                'uuid' => $secondJob->uuid,
                'name' => ExceptionReportingJobThatReportsException::class,
                'connection' => 'database',
                'queue' => 'default',
            ], $thirdWrite['execution_context']);

            return true;
        });
    }

    public function testItCapturesCommandExecutionContext(): void
    {
        $this->freezeTime();
        $_SERVER['REQUEST_TIME_FLOAT'] = (float) now()->subMinute()->format('U.u');
        $this->setupExceptionReporting();
        $streams = $this->fakeEventsStreams();

        Artisan::command('test-command', function () {
            $_SERVER['argv'] = ['artisan', 'test-command'];
            report(new RuntimeException('Whoops!'));
        });

        $this->artisan('test-command')->assertOk();

        $this->assertCount(1, $streams);
        $streams[0]->assertWrittenJsonContains([
            'execution_type' => 'command',
            'execution_context' => [
                'timestamp' => now()->subMinute()->format('Y-m-d H:i:s.u'),
                'name' => 'test-command',
                'class' => \Illuminate\Foundation\Console\ClosureCommand::class,
                'command' => 'test-command',
            ],
        ]);
    }

    public function testItCapturesTheClassAndFullCommandLineForClassBasedCommands(): void
    {
        $this->setupExceptionReporting();
        $streams = $this->fakeEventsStreams();
        Artisan::registerCommand(new ExceptionReportingTestCommand);

        $_SERVER['argv'] = ['artisan', 'test-class-command', '--flag', 'value'];
        $this->artisan('test-class-command', ['--flag' => 'value'])->assertOk();

        $this->assertCount(1, $streams);
        $streams[0]->assertWrittenJson(function (array $payload) {
            $this->assertSame(ExceptionReportingTestCommand::class, $payload['execution_context']['class']);
            $this->assertSame('test-class-command', $payload['execution_context']['name']);
            $this->assertSame('test-class-command --flag value', $payload['execution_context']['command']);

            return true;
        });
    }

    public function testConsoleCommandClassLookupDoesNotForceResolutionOfOtherLazyCommands(): void
    {
        $this->setupExceptionReporting();
        $streams = $this->fakeEventsStreams();
        LazilyResolvedTestCommand::$resolved = false;

        Artisan::command('test-command', function () {
            $_SERVER['argv'] = ['artisan', 'test-command'];
            report(new RuntimeException('Whoops!'));
        });

        // Registering by class string (rather than instance) is what makes
        // Laravel defer construction to the container command loader, only
        // when the command is actually looked up by name. Kernel::getArtisan()
        // is protected, and the container command loader snapshots the
        // command map by value, so we have to reach in and rebuild it
        // *after* the "test-command" bootstrapper above has already run.
        $artisan = $this->getArtisan();
        $artisan->resolveCommands([LazilyResolvedTestCommand::class]);
        $artisan->setContainerCommandLoader();

        $this->assertFalse(LazilyResolvedTestCommand::$resolved);

        $this->artisan('test-command')->assertOk();

        $this->assertCount(1, $streams);

        // Looking up our own command's class must use Kernel::commandNamed(),
        // which resolves only the requested command. Using Artisan::all()
        // here would force every lazily-loaded command through the
        // container command loader, defeating the laziness entirely.
        $this->assertFalse(
            LazilyResolvedTestCommand::$resolved,
            'Expected an unrelated, lazily-registered command to remain unresolved.'
        );
    }

    public function testItHandlesExceptionsThrownFromTheInvokedCommandsConstructor(): void
    {
        $this->setupExceptionReporting();
        $streams = $this->fakeEventsStreams();
        ThrowingConstructorTestCommand::$constructionAttempts = 0;

        // Register lazily, mirroring how a real app's commands are loaded,
        // so the command isn't constructed until Artisan actually looks it
        // up by name during dispatch.
        $kernel = $this->app->make(\Illuminate\Contracts\Console\Kernel::class);
        $artisan = $this->getArtisan();
        $artisan->resolveCommands([ThrowingConstructorTestCommand::class]);
        $artisan->setContainerCommandLoader();

        $_SERVER['argv'] = ['artisan', 'throwing-constructor-command'];

        // Note: we can't exercise this via Kernel::handle() in this test
        // environment, because Orchestra\Testbench\Console\Kernel overrides
        // reportException() to just `throw $e;` instead of calling report()
        // — deliberately, so command failures during tests surface as
        // PHPUnit failures rather than being silently logged. A real app's
        // Kernel::handle() catches the dispatch failure and calls report()
        // on it directly, which is what we simulate here.
        try {
            $kernel->handle(
                new \Symfony\Component\Console\Input\StringInput('throwing-constructor-command'),
                new \Symfony\Component\Console\Output\NullOutput
            );

            $this->fail('Expected the command construction to throw.');
        } catch (Throwable $e) {
            report($e);
        }

        // Our own console-class lookup re-resolves the command by name
        // while reporting the exception it threw, which unavoidably
        // attempts construction a second time (Symfony's own
        // Application::has() resolves the command as a side effect of
        // checking whether it exists — there's no cheaper check). What
        // matters is that the second failure doesn't swallow the report.
        $this->assertSame(2, ThrowingConstructorTestCommand::$constructionAttempts);

        $this->assertCount(1, $streams);
        $streams[0]->assertWrittenJson(function (array $payload) {
            // The container wraps the constructor failure in an
            // EntryNotFoundException; the original exception is preserved
            // as the "previous" exception in the chain.
            $this->assertSame(\Illuminate\Container\EntryNotFoundException::class, $payload['class']);
            $this->assertSame('Boom from the constructor', $payload['previous'][0]['message']);
            $this->assertNull($payload['execution_context']['class']);

            return true;
        });
    }

    public function testItCapturesScheduledTaskExecutionContext(): void
    {
        $this->markTestIncomplete('TODO');
    }

    public function testItCapturesFatalErrors(): void
    {
        $this->freezeTime();
        $_SERVER['REQUEST_TIME_FLOAT'] = (float) now()->subMinute()->format('U.u');
        $this->setupExceptionReporting();
        $streams = $this->fakeEventsStreams();

        $line = null;
        Route::get('/test', function () use (&$line) {
            $this->setRunningInConsole(false);
            report(new FatalError(
                message: 'Out of memory',
                code: 0,
                error: ['file' => __FILE__, 'line' => $line = __LINE__],
                traceOffset: 0,
                traceArgs: true,
                trace: null,
            ));
        });

        $this->get('/test', ['Cloud-Request-ID' => '465ebb4e-2f86-434f-8e1b-cc364f317cef'])->assertOk();

        $this->assertCount(1, $streams);
        $streams[0]->assertWrittenJson([
            '_cloud_event' => 'exception',
            'timestamp' => now()->format('Y-m-d H:i:s.u'),
            'exception_context' => [],
            'laravel_context' => [],
            'trace_id' => '465ebb4e-2f86-434f-8e1b-cc364f317cef',
            'execution_type' => 'request',
            'execution_context' => [
                'timestamp' => now()->subMinute()->format('Y-m-d H:i:s.u'),
                'headers' => [
                    'host' => ['localhost'],
                    'user-agent' => ['Symfony'],
                    'accept' => ['text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8'],
                    'accept-language' => ['en-us,en;q=0.5'],
                    'accept-charset' => ['ISO-8859-1,utf-8;q=0.7,*;q=0.7'],
                    'cloud-request-id' => [
                        '465ebb4e-2f86-434f-8e1b-cc364f317cef',
                    ],
                ],
                'method' => 'GET',
                'url' => 'http://localhost/test',
                'ip' => '127.0.0.1',
                'route' => [
                    'name' => null,
                    'methods' => [
                        0 => 'GET',
                        1 => 'HEAD',
                    ],
                    'domain' => null,
                    'path' => '/test',
                    'action' => 'Closure',
                ],
                'payload' => null,
                'files' => null,
            ],
            'user_id' => null,
            'handled' => false,
            'class' => "Symfony\Component\ErrorHandler\Error\FatalError",
            'code' => '0',
            'message' => 'Out of memory',
            'trace' => [
                [
                    'file' => 'tests/Foundation/Cloud/ExceptionReportingTest.php',
                    'line' => $line,
                ],
            ],
            'previous' => [],
        ]);
    }

    public function testItSkipsInternalFramesFromHandleExceptionsForFatalErrors(): void
    {
        $this->markTestIncomplete('TODO');
    }

    public function testItAppendsTheExceptionFileAndLineAsTheFirstFrameInTheTrace()
    {
        $this->setupExceptionReporting();
        $streams = $this->fakeEventsStreams();

        $line = __LINE__ + 1;
        $e = new RuntimeException('Whoops!');
        report($e);

        $this->assertCount(1, $streams);
        $streams[0]->assertWrittenJson(function (array $payload) use ($line) {
            $this->assertSame([
                'file' => 'tests/Foundation/Cloud/ExceptionReportingTest.php',
                'line' => $line,
            ], $payload['trace'][0]);

            $this->assertSame('Illuminate\Tests\Foundation\Cloud\ExceptionReportingTest', $payload['trace'][1]['class']);
            $this->assertSame('->', $payload['trace'][1]['type']);
            $this->assertSame('testItAppendsTheExceptionFileAndLineAsTheFirstFrameInTheTrace', $payload['trace'][1]['function']);
            $this->assertStringStartsWith('vendor/', $payload['trace'][1]['file']);

            return true;
        });
    }

    public function testItFormatsTraces(): void
    {
        $this->setupExceptionReporting();
        $streams = $this->fakeEventsStreams();
        $closedResource = fopen('php://memory', 'r');
        fclose($closedResource);

        try {
            (function () use ($closedResource) {
                (function ($one, $two, ...$rest) {
                    throw new RuntimeException('Whoops!');
                })(null, false, ...[
                    'nullArg' => null,
                    'boolArg' => false,
                    'intArg' => 1,
                    'floatArg' => 1.0,
                    'arrayArg' => [],
                    'objectArg' => new stdClass,
                    'stringArg' => 'string',
                    'resourceArg' => fopen('php://memory', 'r'),
                    'closedResourceArg' => $closedResource,
                    'closureArg' => (fn () => null),
                ]);
            })(null, true, 1, 1.0, [], new stdClass, 'string', fopen('php://memory', 'r'), $closedResource, 'foo', fn () => null);
        } catch (Throwable $e) {
            report($e);
        }

        $this->assertCount(1, $streams);
        $streams[0]->assertWrittenJson(function ($json) {
            $this->assertSame([
                'null',
                'bool',
                ['nullArg', 'null'],
                ['boolArg', 'bool'],
                ['intArg', 'int'],
                ['floatArg', 'float'],
                ['arrayArg', 'array'],
                ['objectArg', 'stdClass'],
                ['stringArg', 'string'],
                ['resourceArg', 'resource'],
                ['closedResourceArg', 'resource(closed)'],
                ['closureArg', 'Closure'],
            ], $json['trace'][1]['args']['types']);

            $this->assertSame([
                'null', 'bool', 'int', 'float', 'array', 'stdClass', 'string', 'resource', 'resource(closed)', 'string', 'Closure',
            ], $json['trace'][2]['args']['types']);

            return true;
        });
    }

    public function testItCapturesTheRawClassNameForAnonymousClassTraceArgs(): void
    {
        $this->setupExceptionReporting();
        $streams = $this->fakeEventsStreams();

        (function ($anonymousArgOne, $anonymousArgTwo) {
            report(new RuntimeException('Whoops!'));
        })(new class
        {
            //
        }, new class extends stdClass
        {
            //
        }, new class extends Arr
        {
            //
        });

        $this->assertCount(1, $streams);
        $streams[0]->assertWrittenJson(function ($json) {
            $this->assertStringStartsWith("class@anonymous\0/", $json['trace'][1]['args']['types'][0]);
            $this->assertStringStartsWith("stdClass@anonymous\0/", $json['trace'][1]['args']['types'][1]);
            $this->assertStringStartsWith("Illuminate\Support\Arr@anonymous\0/", $json['trace'][1]['args']['types'][2]);

            return true;
        });
    }

    public function testItCapturesTheRawClassNameForAnonymousExceptions(): void
    {
        $this->setupExceptionReporting();
        $streams = $this->fakeEventsStreams();

        report(new class('Whoops!') extends RuntimeException
        {
            //
        });

        $this->assertCount(1, $streams);
        $streams[0]->assertWrittenJson(function ($json) {
            $this->assertStringStartsWith("RuntimeException@anonymous\0/", $json['class']);

            return true;
        });
    }

    public function testItSerializesToJsonCorrectly(): void
    {
        $this->setupExceptionReporting();
        $streams = $this->fakeEventsStreams();

        Exceptions::buildContextUsing(fn () => [
            'unicode' => '😎',
            'float' => 1.0,
            'slashes' => 'https://example.com',
            'invalid-utf8' => "\xB1\x31",
        ]);

        report(new RuntimeException('Whoops!'));

        $this->assertCount(1, $streams);
        $streams[0]->assertWritten(function ($json) {
            $this->assertStringContainsString('"unicode":"😎"', $json);
            $this->assertStringContainsString('"float":1.0', $json);
            $this->assertStringContainsString('"slashes":"https://example.com"', $json);
            $this->assertStringContainsString('"invalid-utf8":"�1"', $json);

            return true;
        });
    }

    #[DataProvider('stopDataProvider')]
    public function testItCanConfigureStoppingExceptionReporting(bool $stopConfig, bool $expectToHaveCalled): void
    {
        $this->setupExceptionReporting(['stop' => $stopConfig]);
        $streams = $this->fakeEventsStreams();
        $reportableCalled = false;
        Exceptions::reportable(function (Throwable $e) use (&$reportableCalled) {
            $reportableCalled = true;
        });

        report(new RuntimeException('Whoops!'));

        $this->assertCount(1, $streams);
        $streams[0]->assertWrittenJsonContains([
            'message' => 'Whoops!',
        ]);
        $this->assertSame($expectToHaveCalled, $reportableCalled);
    }

    public static function stopDataProvider(): iterable
    {
        yield 'stop reporting' => [true, false];
        yield  'continue reporting' => [false, true];
    }

    public function testItDoesBubbleOnWriteFailureWhenConfiguredNotToBubble(): void
    {
        $this->setupExceptionReporting(['stop' => true]);
        $streams = $this->fakeEventsStreams();
        Events::$socketFactory = function () {
            return false;
        };
        $reportableCalled = false;
        Exceptions::reportable(function (Throwable $e) use (&$reportableCalled) {
            $reportableCalled = true;
        });

        report(new RuntimeException('Whoops!'));

        $this->assertCount(0, $streams);
        $this->assertTrue($reportableCalled);
    }

    public function testItDoesNotCaptureSyncJobContext(): void
    {
        $this->setupExceptionReporting();
        $streams = $this->fakeEventsStreams();

        dispatch(function () {
            report(new RuntimeException('Whoops!'));
        })->onQueue('sync');
        dispatch_sync(function () {
            report(new RuntimeException('Whoops!'));
        });

        $this->assertCount(1, $streams);
        $streams[0]->assertWrittenJsonContains([
            'execution_type' => 'command',
        ], write: 0);
        $streams[0]->assertWrittenJsonContains([
            'execution_type' => 'command',
        ], write: 1);
    }

    public function testItCapturesExceptionsOnUnknownRoutes(): void
    {
        $this->freezeTime();
        $_SERVER['REQUEST_TIME_FLOAT'] = (float) now()->subMinute()->format('U.u');
        $this->setupExceptionReporting();
        $streams = $this->fakeEventsStreams();

        $this->app->make(\Illuminate\Contracts\Http\Kernel::class)->pushMiddleware(function ($request, $next) {
            $this->setRunningInConsole(false);
            throw new RuntimeException('Whoops!', 54);
        });
        $this->get('/test', ['Cloud-Request-ID' => '465ebb4e-2f86-434f-8e1b-cc364f317cef'])->assertServerError();

        $this->assertCount(1, $streams);
        $streams[0]->assertWrittenJson(function (array $payload) {
            unset($payload['trace']); // we will test this in isolation

            $this->assertSame([
                '_cloud_event' => 'exception',
                'timestamp' => now()->format('Y-m-d H:i:s.u'),
                'exception_context' => [],
                'laravel_context' => [],
                'trace_id' => '465ebb4e-2f86-434f-8e1b-cc364f317cef',
                'execution_type' => 'request',
                'execution_context' => [
                    'timestamp' => now()->subMinute()->format('Y-m-d H:i:s.u'),
                    'headers' => [
                        'host' => ['localhost'],
                        'user-agent' => ['Symfony'],
                        'accept' => ['text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8'],
                        'accept-language' => ['en-us,en;q=0.5'],
                        'accept-charset' => ['ISO-8859-1,utf-8;q=0.7,*;q=0.7'],
                        'cloud-request-id' => ['465ebb4e-2f86-434f-8e1b-cc364f317cef'],
                    ],
                    'method' => 'GET',
                    'url' => 'http://localhost/test',
                    'ip' => '127.0.0.1',
                    'route' => null,
                    'payload' => null,
                    'files' => null,
                ],
                'user_id' => null,
                'handled' => false,
                'class' => 'RuntimeException',
                'code' => '54',
                'message' => 'Whoops!',
                'previous' => [],
            ], $payload);

            return true;
        });
    }

    protected function fakeEventsStreams()
    {
        Events::$socketFactory = function (
            string $address,
            ?int &$error_code = null,
            ?string &$error_message = null,
            ?float $timeout = null,
            int $flags = STREAM_CLIENT_CONNECT,
            $context = null
        ) {
            return fopen($address, 'r+');
        };

        try {
            stream_wrapper_register('unix', FakeStream::class);
        } catch (Throwable $e) {
            if (! in_array('unix', stream_get_wrappers())) {
                throw $e;
            }
        }

        FakeStream::flush();

        return FakeStream::instances();
    }

    protected function setupExceptionReporting(array $config = []): void
    {
        $_SERVER['LARAVEL_CLOUD'] = '1';
        $_SERVER['LARAVEL_CLOUD_EXCEPTIONS'] = json_encode($config);
        Cloud::registerEvents($this->app);
        Cloud::registerExceptionReporting($this->app);
    }

    protected function setRunningInConsole(bool $runningInConsole): void
    {
        (function () use ($runningInConsole) {
            $this->isRunningInConsole = $runningInConsole;
        })->call($this->app);
    }

    protected function getArtisan(): \Illuminate\Console\Application
    {
        return (function () {
            return $this->getArtisan();
        })->call($this->app->make(\Illuminate\Contracts\Console\Kernel::class));
    }
}

class FakeStream
{
    private static ?Collection $instances = null;

    public $context;

    public string $stream = '';

    public static function instances(): Collection
    {
        return self::$instances ??= new Collection;
    }

    /**
     * @param  array<mixed>  $arguments
     */
    public function __call(string $name, array $arguments): mixed
    {
        $handler = match ($name) {
            'stream_open' => function (string $path, string $mode, int $options, ?string &$openedPath): bool {
                self::instances()->push($this);

                return true;
            },
            'stream_set_option' => fn (int $option, int $arg1, int $arg2): bool => true,
            'stream_write' => function (string $chunk): int {
                $this->stream .= $chunk;

                return strlen($chunk);
            },
            'stream_read' => fn (int $length): string|false => '2:OK',
            'stream_eof' => fn (): bool => false,
            'stream_flush' => fn (): bool => true,
            'stream_close' => function (): void {
                //
            },
            'stream_seek' => fn () => 0,
            'url_stat' => fn (string $path, int $flags): array|false => false,
            default => throw new RuntimeException("FakeTcpStream method not implemented [{$name}]"),
        };

        return call_user_func_array($handler, $arguments);
    }

    public function assertWrittenJsonContains(array $expected, $write = 0): self
    {
        $stream = explode("\n", $this->stream)[$write];
        Assert::assertJson($stream);

        $json = json_decode($stream, associative: true, flags: JSON_THROW_ON_ERROR);

        foreach ($expected as $key => $value) {
            Assert::assertArrayHasKey($key, $json);
            Assert::assertSame($value, $json[$key], "Expected key [{$key}] to have value [".json_encode($value).'] but got ['.json_encode($json[$key]).']');
        }

        return $this;
    }

    public function assertWrittenJson(Closure|array $stream): self
    {
        Assert::assertJson($this->stream);

        $json = json_decode($this->stream, associative: true, flags: JSON_THROW_ON_ERROR);

        if (is_array($stream)) {
            Assert::assertSame($json, $stream);
        } else {
            Assert::assertTrue($stream($json));
        }

        return $this;
    }

    public function assertWritten(string|callable $stream): self
    {
        if (is_string($stream)) {
            Assert::assertSame($stream, $this->stream);
        } else {
            Assert::assertTrue($stream($this->stream));
        }

        return $this;
    }

    public static function flush(): void
    {
        self::$instances = null;
    }
}

class ExceptionHandlerWithoutContextForException implements ExceptionHandler
{
    protected array $reportUsingCallbacks = [];

    public function reportable(callable $reportUsing): static
    {
        $this->reportUsingCallbacks[] = $reportUsing;

        return $this;
    }

    public function report(Throwable $e)
    {
        foreach ($this->reportUsingCallbacks as $reportUsing) {
            $reportUsing($e);
        }
    }

    public function shouldReport(Throwable $e)
    {
        return true;
    }

    public function render($request, Throwable $e)
    {
        //
    }

    public function renderForConsole($output, Throwable $e)
    {
        //
    }
}

class ExceptionHandlerThatThrowsFromContextForException implements ExceptionHandler
{
    protected array $reportUsingCallbacks = [];

    public function reportable(callable $reportUsing): static
    {
        $this->reportUsingCallbacks[] = $reportUsing;

        return $this;
    }

    public function report(Throwable $e)
    {
        foreach ($this->reportUsingCallbacks as $reportUsing) {
            $reportUsing($e);
        }
    }

    public function shouldReport(Throwable $e)
    {
        return true;
    }

    public function render($request, Throwable $e)
    {
        //
    }

    public function renderForConsole($output, Throwable $e)
    {
        //
    }

    public function contextForException(Throwable $e)
    {
        throw new RuntimeException('Context error!');
    }
}

class UserThatThrowsWhenItsAuthIdentifierIsRetrieved implements \Illuminate\Contracts\Auth\Authenticatable
{
    public function getAuthIdentifierName()
    {
        return 'id';
    }

    public function getAuthIdentifier()
    {
        throw new RuntimeException('Boom while retrieving the auth identifier!');
    }

    public function getAuthPasswordName()
    {
        return 'password';
    }

    public function getAuthPassword()
    {
        return '';
    }

    public function getRememberToken()
    {
        return null;
    }

    public function setRememberToken($value)
    {
        //
    }

    public function getRememberTokenName()
    {
        return 'remember_token';
    }
}

class ExceptionWithContext extends Exception
{
    public function __construct(protected array $context, string $message = '', int $code = 0, Throwable|null $previous = null)
    {
        return parent::__construct($message, $code, $previous);
    }

    public function context(): array
    {
        return $this->context;
    }
}

class ExceptionReportingJobThatReportsException implements ShouldQueue
{
    use Queueable;

    protected $callback;

    public function __construct(?Closure $callback = null)
    {
        if ($callback) {
            $this->callback = new SerializableClosure($callback);
        }
    }

    public function handle()
    {
        call_user_func($this->callback);

        report(new RuntimeException('Whoops!'));
    }
}

class ExceptionReportingTestCommand extends \Illuminate\Console\Command
{
    protected $signature = 'test-class-command {--flag=}';

    public function handle()
    {
        report(new RuntimeException('Whoops!'));
    }
}

#[\Symfony\Component\Console\Attribute\AsCommand(name: 'lazily-resolved-test-command')]
class LazilyResolvedTestCommand extends \Illuminate\Console\Command
{
    public static bool $resolved = false;

    public function __construct()
    {
        parent::__construct();

        static::$resolved = true;
    }

    public function handle()
    {
        //
    }
}

#[\Symfony\Component\Console\Attribute\AsCommand(name: 'throwing-constructor-command')]
class ThrowingConstructorTestCommand extends \Illuminate\Console\Command
{
    public static int $constructionAttempts = 0;

    public function __construct()
    {
        parent::__construct();

        static::$constructionAttempts++;

        throw new RuntimeException('Boom from the constructor');
    }

    public function handle()
    {
        //
    }
}
