<?php

namespace Illuminate\Tests\Foundation\Cloud;

use Closure;
use Exception;
use Illuminate\Auth\GenericUser;
use Illuminate\Console\Events\ScheduledTaskFinished;
use Illuminate\Console\Events\ScheduledTaskStarting;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\Queue\Job as JobContract;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Events\Dispatcher;
use Illuminate\Foundation\Cloud\Events;
use Illuminate\Foundation\Cloud\ExceptionReporter;
use Illuminate\Foundation\CloudBootstrapper as Cloud;
use Illuminate\Foundation\Exceptions\Handler;
use Illuminate\Foundation\Exceptions\Renderer\Mappers\BladeMapper;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Foundation\Testing\WithConsoleEvents;
use Illuminate\Log\Context\Repository as ContextRepository;
use Illuminate\Queue\Events\JobPopped;
use Illuminate\Queue\Events\JobPopping;
use Illuminate\Queue\Jobs\Job as QueueJob;
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
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Illuminate\View\ViewException;
use Laravel\SerializableClosure\SerializableClosure;
use LogicException;
use Orchestra\Testbench\Attributes\WithMigration;
use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use Ramsey\Uuid\Uuid;
use RuntimeException;
use stdClass;
use Symfony\Component\Console\Input\RawInputInterface;
use Symfony\Component\ErrorHandler\Error\FatalError;
use Symfony\Component\HttpFoundation\FileBag;
use Symfony\Component\HttpFoundation\HeaderBag;
use Throwable;

#[WithMigration]
#[WithMigration('laravel', 'queue')]
class ExceptionReportingTest extends TestCase
{
    use LazilyRefreshDatabase, WithConsoleEvents;

    protected $serverSettingsToRestore = [];

    protected $iniSettingsToRestore = [];

    protected $reportedScheduledTaskStreams = null;

    protected $reportedScheduledTasks = 0;

    protected function setUp(): void
    {
        parent::setUp();

        foreach ([
            'LARAVEL_CLOUD',
            'LARAVEL_CLOUD_EXCEPTIONS',
            'LARAVEL_CLOUD_COMMAND_UUID',
            'REQUEST_TIME_FLOAT',
            'argv',
        ] as $key) {
            $this->serverSettingsToRestore[$key] = $_SERVER[$key] ?? '__undefined__';
        }

        $this->app->setBasePath(dirname($this->app->basePath(), levels: 4));
    }

    protected function tearDown(): void
    {
        foreach ($this->iniSettingsToRestore as $key => $value) {
            ini_set($key, $value);
        }

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

    public function testItRegistersExceptionReportingBeforeTheFacadesHaveTheApplication(): void
    {
        $streams = $this->fakeEventsStreams();

        // Exception reporting is registered while the "HandleExceptions"
        // bootstrapper is bootstrapped, which is before the "RegisterFacades"
        // bootstrapper has given the application to the facades.
        Facade::clearResolvedInstances();
        Facade::setFacadeApplication(null);

        try {
            $this->setupExceptionReporting();
        } finally {
            Facade::setFacadeApplication($this->app);
        }

        $this->app->make(Schedule::class)
            ->call(fn () => throw new RuntimeException('Whoops!'))
            ->name('test-scheduled-task')
            ->everyMinute();

        $this->runArtisanCommand(['artisan', 'schedule:run']);

        // The listeners are registered after the reporter itself, so a failure
        // to register them is not seen in the reported exception, only in the
        // execution it is attributed to.
        $this->assertCount(1, $streams);
        $streams[0]->assertWrittenJsonContains([
            'message' => 'Whoops!',
            'execution_type' => 'scheduled_task',
        ]);
    }

    public function testItDoesNotResolveTheExceptionHandlerWhileRegisteringExceptionReporting(): void
    {
        $streams = $this->fakeEventsStreams();

        // Mirror a freshly bootstrapping application, where nothing has resolved
        // the handler yet and its "withExceptions" callback may use facades.
        unset($this->app[ExceptionHandler::class]);
        $this->app->singleton(ExceptionHandler::class, Handler::class);
        $this->app->afterResolving(Handler::class, function (Handler $handler) {
            if (Config::get('app.debug') !== null) {
                $handler->dontReport(LogicException::class);
            }
        });

        Facade::clearResolvedInstances();
        Facade::setFacadeApplication(null);

        try {
            $this->setupExceptionReporting();

            $this->assertFalse($this->app->resolved(ExceptionHandler::class));
        } finally {
            Facade::setFacadeApplication($this->app);
        }

        report(new LogicException('Ignored!'));
        report(new RuntimeException('Whoops!'));

        $this->assertCount(1, $streams);
        $streams[0]->assertWrittenJsonContains([
            'message' => 'Whoops!',
        ]);
    }

    public function testItIgnoresExceptionHandlersThatCannotRegisterReportables(): void
    {
        unset($this->app[ExceptionHandler::class]);
        $this->app->singleton(ExceptionHandler::class, ExceptionHandlerWithoutReportable::class);

        $this->setupExceptionReporting();

        $this->assertInstanceOf(ExceptionHandlerWithoutReportable::class, $this->app[ExceptionHandler::class]);
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

            $this->assertTrue(Str::isUuid($payload['id']));
            unset($payload['id']);

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

    public function testItCapturesTheExceptionId(): void
    {
        $this->setupExceptionReporting();
        $streams = $this->fakeEventsStreams();

        report($exception = new RuntimeException('Whoops!'));

        // The identifier is the one the failed job provider reports, so that
        // a failed job can be tied back to the exception that failed it.
        $this->assertCount(1, $streams);
        $streams[0]->assertWrittenJsonContains([
            'id' => $this->app[ExceptionReporter::class]->exceptionId($exception),
            'message' => 'Whoops!',
        ]);
    }

    public function testItCapturesTheSameExceptionIdForAViewExceptionAndTheExceptionItWraps(): void
    {
        $this->setupExceptionReporting();
        $streams = $this->fakeEventsStreams();

        report($exception = new ViewException('Whoops!', previous: new RuntimeException('The original!')));

        // The view exception is unwrapped before it is reported, while other
        // callers, e.g. the failed job provider, have the exception as it was
        // thrown. Both identify the same exception.
        $this->assertCount(1, $streams);
        $streams[0]->assertWrittenJsonContains([
            'id' => $this->app[ExceptionReporter::class]->exceptionId($exception),
            'message' => 'The original!',
        ]);
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

    public static function redactedRequestPayloadValueProvider(): array
    {
        return [
            'string' => ['super-secret', '[12 bytes redacted]'],
            // The ConvertEmptyStringsToNull middleware rewrites the value
            // before it ever reaches the reporter.
            'empty string' => ['', null],
            'multi-byte string' => ['One 😎', '[8 bytes redacted]'],
            'numeric string' => ['4821', '[4 bytes redacted]'],
            'integer' => [4821, '[4 bytes redacted]'],
            'zero integer' => [0, '[1 byte redacted]'],
            'negative integer' => [-7, '[2 bytes redacted]'],
            'large integer' => [PHP_INT_MAX, '[19 bytes redacted]'],
            'float' => [1.5, '[3 bytes redacted]'],
            'negative float' => [-12.75, '[6 bytes redacted]'],
            // A float with a zero fraction casts to a string without it.
            'float with a zero fraction' => [1.0, '[1 byte redacted]'],
            // "false" is cast to a zero rather than an empty string, so that
            // both booleans redact to the same length and the value is not
            // revealed by the byte count.
            'true' => [true, '[1 byte redacted]'],
            'false' => [false, '[1 byte redacted]'],
            // `null` is not a scalar, so it is passed through as-is. It does
            // not reveal the value, only that the field was present.
            'null' => [null, null],
        ];
    }

    #[DataProvider('redactedRequestPayloadValueProvider')]
    public function testItRedactsScalarRequestPayloadValues(mixed $value, mixed $expected): void
    {
        $this->setupExceptionReporting(['capture_request_payload' => true]);
        $streams = $this->fakeEventsStreams();

        Route::post('/test', function () {
            $this->setRunningInConsole(false);
            report(new RuntimeException('Whoops!'));
        });
        // The payload is sent as JSON so the value retains its type. A form
        // request casts every value to a string before it reaches the app.
        $this->postJson('/test', ['password' => $value])->assertOk();

        $this->assertCount(1, $streams);
        $streams[0]->assertWrittenJson(function (array $payload) use ($expected) {
            $this->assertSame([
                'password' => $expected,
            ], $payload['execution_context']['payload']);

            return true;
        });
    }

    public function testItRedactsScalarValuesNestedInTheRequestPayload(): void
    {
        $this->setupExceptionReporting(['capture_request_payload' => true]);
        $streams = $this->fakeEventsStreams();

        Route::post('/test', function () {
            $this->setRunningInConsole(false);
            report(new RuntimeException('Whoops!'));
        });
        $this->postJson('/test', [
            'user' => [
                'username' => 'taylor',
                'password' => 4821,
            ],
        ])->assertOk();

        $this->assertCount(1, $streams);
        $streams[0]->assertWrittenJson(function (array $payload) {
            $this->assertSame([
                'user' => [
                    'username' => 'taylor',
                    'password' => '[4 bytes redacted]',
                ],
            ], $payload['execution_context']['payload']);

            return true;
        });
    }

    public function testItRecursesIntoArrayRequestPayloadValuesRatherThanRedactingThem(): void
    {
        $this->setupExceptionReporting(['capture_request_payload' => true]);
        $streams = $this->fakeEventsStreams();

        Route::post('/test', function () {
            $this->setRunningInConsole(false);
            report(new RuntimeException('Whoops!'));
        });
        $this->postJson('/test', [
            'password' => ['first', 'second', ['password' => 4821]],
        ])->assertOk();

        $this->assertCount(1, $streams);
        $streams[0]->assertWrittenJson(function (array $payload) {
            $this->assertSame([
                'password' => [
                    'first',
                    'second',
                    ['password' => '[4 bytes redacted]'],
                ],
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

    public function testItDoesNotCaptureTheRequestPayloadByDefault(): void
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

    public function testItReportsNoExceptionContextWhenTheHandlerDoesNotSupportContextForException(): void
    {
        $this->app->instance(ExceptionHandler::class, new ExceptionHandlerWithoutContextForException);

        $this->setupExceptionReporting();
        $streams = $this->fakeEventsStreams();

        report(new RuntimeException('Whoops!'));

        $this->assertCount(1, $streams);
        $streams[0]->assertWrittenJsonContains([
            'exception_context' => null,
            'message' => 'Whoops!',
        ]);
    }

    public function testItReportsNoExceptionContextWhenContextForExceptionThrows(): void
    {
        $this->app->instance(ExceptionHandler::class, new ExceptionHandlerThatThrowsFromContextForException);

        $this->setupExceptionReporting();
        $streams = $this->fakeEventsStreams();

        report(new RuntimeException('Whoops!'));

        $this->assertCount(1, $streams);
        $streams[0]->assertWrittenJsonContains([
            'exception_context' => null,
            'message' => 'Whoops!',
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

    public function testItReportsNoLaravelContextWhenRetrievingItThrows(): void
    {
        $this->app->instance(ContextRepository::class, new ContextRepositoryThatThrows);
        Context::clearResolvedInstances();

        $this->setupExceptionReporting();
        $streams = $this->fakeEventsStreams();

        report(new RuntimeException('Whoops!'));

        $this->assertCount(1, $streams);
        $streams[0]->assertWrittenJsonContains([
            'laravel_context' => null,
            'message' => 'Whoops!',
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

    public function testItResolvesTheBladeMapperWhenAViewExceptionIsReported(): void
    {
        $this->setupExceptionReporting();
        $streams = $this->fakeEventsStreams();
        $resolved = 0;

        // The mapper is given as a closure, as the bootstrapper cannot build
        // lazy proxies on every supported version of PHP.
        $reporter = new ExceptionReporter(
            $this->app[Events::class],
            function () use (&$resolved) {
                $resolved++;

                return $this->app[BladeMapper::class];
            },
            $this->app->basePath().DIRECTORY_SEPARATOR,
            ['stop' => true],
        );

        $reporter(new RuntimeException('Whoops!'));
        $this->assertSame(0, $resolved);

        $reporter(new ViewException('Whoops!', previous: new RuntimeException('The original!')));
        $this->assertSame(1, $resolved);

        $this->assertCount(1, $streams);
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

        $compiledComponent = $this->reportedCompiledViewPath(__DIR__.'/components/profile.blade.php');
        $compiledView = $this->reportedCompiledViewPath(__DIR__.'/foo.blade.php');

        $this->assertCount(1, $streams);
        $streams[0]->assertWritten(function ($stream) use ($compiledComponent, $compiledView) {
            $writes = explode("\n", $stream, 2);
            $this->assertCount(2, $writes);

            [
                $reportedInView,
                $thrownInView,
            ] = array_map(fn ($payload) => json_decode($payload, associative: true, flags: JSON_THROW_ON_ERROR), $writes);

            $this->assertSame([
                'file' => $compiledComponent,
                'line' => 4,
            ], $reportedInView['trace'][0]);
            $this->assertSame([
                'file' => 'tests/Foundation/Cloud/components/profile.blade.php',
                'line' => 3,
                'compiled_view' => $compiledComponent,
            ], $thrownInView['trace'][0]);

            $this->assertSame([
                'file' => $compiledView,
                'line' => 12,
            ], Arr::except($reportedInView['trace'][9], ['function', 'class', 'type', 'args']));
            $this->assertSame([
                'file' => 'tests/Foundation/Cloud/foo.blade.php',
                'line' => 3,
                'compiled_view' => $compiledView,
            ], Arr::except($thrownInView['trace'][9], ['function', 'class', 'type', 'args']));

            unset($reportedInView['trace'][0], $thrownInView['trace'][0], $thrownInView['trace'][9], $reportedInView['trace'][9]);

            $this->assertSame($reportedInView['trace'], $thrownInView['trace']);

            return true;
        });
    }

    public function testItUnwrapsNestedViewExceptions(): void
    {
        $this->setupExceptionReporting();
        $streams = $this->fakeEventsStreams();

        // A component that throws while rendering within a view is wrapped
        // once for the component and again for the view.
        report(new ViewException('Whoops! (View: layout.blade.php)', previous: new ViewException(
            'Whoops! (View: profile.blade.php)', previous: new RuntimeException('The original!'),
        )));

        $this->assertCount(1, $streams);
        $streams[0]->assertWrittenJsonContains([
            'class' => RuntimeException::class,
            'message' => 'The original!',
        ]);
    }

    public function testItReportsViewExceptionsThatWrapNothing(): void
    {
        $this->setupExceptionReporting();
        $streams = $this->fakeEventsStreams();

        report(new ViewException('Whoops!'));

        // There is nothing to unwrap, so the view exception is reported as it
        // was given.
        $this->assertCount(1, $streams);
        $streams[0]->assertWrittenJsonContains([
            'class' => ViewException::class,
            'message' => 'Whoops!',
        ]);
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
            '--memory' => 1024,
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

    public function testItNormalizesSqsFifoQueueNames(): void
    {
        $this->freezeTime();
        $this->setupExceptionReporting();
        $streams = $this->fakeEventsStreams();
        Config::set('queue.default', 'database');

        // The suffix of a FIFO queue sits before the ".fifo" extension, as
        // that is where SQS requires it.
        ExceptionReportingJobThatReportsException::dispatch(fn () => Config::set('queue.connections.database', [
            'driver' => 'sqs',
            'prefix' => 'https://sqs.us-east-1.amazonaws.com/your-account-id',
            'queue' => 'queue-name.fifo',
            'suffix' => '-production',
        ]))->onQueue('https://sqs.us-east-1.amazonaws.com/your-account-id/queue-name-production.fifo');

        Artisan::call('queue:work', [
            '--queue' => 'https://sqs.us-east-1.amazonaws.com/your-account-id/queue-name-production.fifo',
            '--max-jobs' => 1,
            '--memory' => 1024,
            '--sleep' => 0,
            '--stop-when-empty' => true,
            '--tries' => 1,
        ]);

        $this->assertCount(1, $streams);
        $streams[0]->assertWrittenJson(function (array $json) {
            $this->assertSame('queue-name.fifo', $json['execution_context']['queue']);

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
            '--memory' => 1024,
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

    public function testItDoesNotResolveTheAuthGuardsWhenCapturingTheUserId(): void
    {
        $this->setupExceptionReporting();
        $streams = $this->fakeEventsStreams();

        // Resolving a guard may hit the session or the database, and may
        // itself fail, which should not happen as a side effect of reporting
        // an exception.
        Auth::extend('exploding', fn () => throw new RuntimeException('The guard was resolved!'));
        Config::set('auth.guards.web.driver', 'exploding');

        report(new RuntimeException('Whoops!'));

        $this->assertCount(1, $streams);
        $streams[0]->assertWrittenJsonContains(['user_id' => null]);
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

    public function testItReportsNoUserIdWhenTheUserIdentifierCannotBeRetrieved(): void
    {
        $this->setupExceptionReporting();
        $streams = $this->fakeEventsStreams();

        Auth::setUser(new UserThatThrowsWhenItsAuthIdentifierIsRetrieved);

        report(new RuntimeException('Whoops!'));

        $this->assertCount(1, $streams);
        $streams[0]->assertWrittenJsonContains([
            'user_id' => null,
            'message' => 'Whoops!',
        ]);
    }

    public function testItCapturesUserIdInRequestsAfterLogout(): void
    {
        $this->setRunningInConsole(false);
        $this->setupExceptionReporting();
        $streams = $this->fakeEventsStreams();

        Route::get('/test', function () {
            $this->setRunningInConsole(false);

            Auth::logout();

            throw new RuntimeException('Whoops!');
        });

        $this->actingAs(new GenericUser(['id' => 'abc123', 'remember_token' => '']))
            ->get('/test')
            ->assertServerError();

        // The user is no longer authenticated, but the exception is still
        // theirs, so the user they were is reported.
        $this->assertCount(1, $streams);
        $streams[0]->assertWrittenJsonContains(['user_id' => 'abc123', 'message' => 'Whoops!']);
    }

    public function testItDoesNotRememberTheUserThatLoggedOutInLaterRequests(): void
    {
        $this->setRunningInConsole(false);
        $this->setupExceptionReporting();
        $streams = $this->fakeEventsStreams();

        Route::get('/logout', function () {
            Auth::logout();

            return 'ok';
        });
        Route::get('/test', function () {
            Auth::check();

            throw new RuntimeException('Whoops!');
        });

        $this->actingAs(new GenericUser(['id' => 'abc123', 'remember_token' => '']))
            ->get('/logout')
            ->assertOk();

        // Octane serves many requests with the same application, flushing the
        // scoped instances and authentication state between each of them.
        $this->app->forgetScopedInstances();
        Facade::clearResolvedInstances();
        Auth::forgetGuards();

        $this->get('/test')->assertServerError();

        $this->assertCount(1, $streams);
        $streams[0]->assertWrittenJsonContains(['user_id' => null, 'message' => 'Whoops!']);
    }

    public function testItDoesNotRememberTheUserWhenTheyLogOutInAJob(): void
    {
        $this->setupExceptionReporting();
        $streams = $this->fakeEventsStreams();
        Config::set('queue.default', 'database');

        // A worker handles many jobs, so remembering the user would attribute
        // them to every job that follows. The user a job belongs to is the one
        // captured when the job was dispatched.
        ExceptionReportingJobThatReportsException::dispatch(function () {
            Auth::setUser(new GenericUser(['id' => 'abc123', 'remember_token' => '']));
            Auth::logout();
        });
        ExceptionReportingJobThatReportsException::dispatch(fn () => true);

        Artisan::call('queue:work', [
            '--max-jobs' => 2,
            '--memory' => 1024,
            '--sleep' => 0,
            '--stop-when-empty' => true,
            '--tries' => 1,
        ]);

        // The reporter writes to the socket it has already opened, so each
        // report within the worker is another write to the same stream.
        $this->assertCount(1, $streams);
        $streams[0]->assertWrittenJsonContains(['user_id' => null, 'message' => 'Whoops!'], write: 0);
        $streams[0]->assertWrittenJsonContains(['user_id' => null, 'message' => 'Whoops!'], write: 1);
    }

    public function testItDoesNotCauseRecursionWhenRetrievingUserId(): void
    {
        $this->setupExceptionReporting();
        $streams = $this->fakeEventsStreams();

        // Retrieving the user's identifier may itself throw, which must not
        // be reported, as reporting it would retrieve the identifier again.
        Auth::setUser(new UserThatThrowsWhenItsAuthIdentifierIsRetrieved);

        report(new RuntimeException('Whoops!'));

        $this->assertCount(1, $streams);
        $this->assertCount(1, array_filter(explode("\n", $streams[0]->stream)));
        $streams[0]->assertWrittenJsonContains([
            'user_id' => null,
            'message' => 'Whoops!',
        ]);
    }

    public function testItDoesNotCaptureUserIdForGuests(): void
    {
        $this->setupExceptionReporting();
        $streams = $this->fakeEventsStreams();

        Route::get('/test', fn () => throw new RuntimeException('Whoops!'));

        $this->get('/test')->assertServerError();

        $this->assertCount(1, $streams);
        $streams[0]->assertWrittenJsonContains(['user_id' => null, 'message' => 'Whoops!']);
    }

    public function testItCapturesUserIdWhenTheUserLogsInDuringARequest(): void
    {
        $this->setupExceptionReporting();
        $streams = $this->fakeEventsStreams();

        Route::get('/test', function () {
            Auth::login(new GenericUser(['id' => 'abc123', 'password' => 'secret', 'remember_token' => '']));

            throw new RuntimeException('Whoops!');
        });

        $this->get('/test')->assertServerError();

        $this->assertCount(1, $streams);
        $streams[0]->assertWrittenJsonContains(['user_id' => 'abc123', 'message' => 'Whoops!']);
    }

    public function testItCapturesTheUserResolvedByLaravelsExceptionContext(): void
    {
        $this->setupExceptionReporting();
        $streams = $this->fakeEventsStreams();

        // The reporter never retrieves the user itself, although Laravel's
        // exception context calls "Auth::id()", which retrieves them from the
        // resolved guard before the identifier is captured.
        $guard = new GuardThatCountsUserRetrievals;
        Auth::extend('counting', fn () => $guard);
        Config::set('auth.guards.web.driver', 'counting');
        Auth::guard();

        ($this->exceptionReporter())(new RuntimeException('Whoops!'));

        // The guard is hit by the exception context and again by the
        // reporter, which reads the user the guard has now retrieved.
        $this->assertSame(2, $guard->calls);
        $this->assertCount(1, $streams);
        $streams[0]->assertWrittenJsonContains(['user_id' => 'abc123', 'message' => 'Whoops!']);
    }

    public function testItFallsBackToTheUserIdInTheContextWhenNoUserIsAuthenticated(): void
    {
        $this->setupExceptionReporting();
        $streams = $this->fakeEventsStreams();

        Context::addHidden('laravel_cloud_user_id', 'abc123');

        Route::get('/test', function () {
            report(new RuntimeException('Whoops before the guards are resolved!'));

            Auth::guard();

            report(new RuntimeException('Whoops after the guards are resolved!'));

            return 'ok';
        });

        $this->get('/test')->assertOk();

        $this->assertCount(1, $streams);
        $streams[0]->assertWrittenJsonContains([
            'user_id' => 'abc123',
            'message' => 'Whoops before the guards are resolved!',
        ], write: 0);
        $streams[0]->assertWrittenJsonContains([
            'user_id' => 'abc123',
            'message' => 'Whoops after the guards are resolved!',
        ], write: 1);
    }

    public function testItAddsTheUserIdToTheContextWhenDispatchingJobsDuringRequests(): void
    {
        $this->setupExceptionReporting();
        $this->fakeEventsStreams();
        Config::set('queue.default', 'database');

        Route::get('/test', function () {
            ExceptionReportingJobThatReportsException::dispatch(fn () => true);

            return 'ok';
        });

        $this->actingAs(new GenericUser(['id' => 'abc123']))->get('/test')->assertOk();

        $this->assertSame('abc123', $this->userIdInJobPayload(DB::table('jobs')->soleValue('payload')));
    }

    public function testItDoesNotAddTheUserIdToTheContextForJobsDispatchedBeforeTheUserLogsIn(): void
    {
        $this->setupExceptionReporting();
        $this->fakeEventsStreams();
        Config::set('queue.default', 'database');

        Route::get('/test', function () {
            ExceptionReportingJobThatReportsException::dispatch(fn () => true);

            Auth::login(new GenericUser(['id' => 'abc123', 'password' => 'secret', 'remember_token' => '']));

            ExceptionReportingJobThatReportsException::dispatch(fn () => true);

            return 'ok';
        });

        $this->get('/test')->assertOk();

        $payloads = DB::table('jobs')->orderBy('id')->pluck('payload');
        $this->assertCount(2, $payloads);
        $this->assertNull($this->userIdInJobPayload($payloads[0]));
        $this->assertSame('abc123', $this->userIdInJobPayload($payloads[1]));
    }

    public function testItAddsTheUserIdToTheContextWhenDispatchingJobsAfterTheUserLogsOut(): void
    {
        $this->setRunningInConsole(false);
        $this->setupExceptionReporting();
        $this->fakeEventsStreams();
        Config::set('queue.default', 'database');

        Route::get('/test', function () {
            Auth::logout();

            ExceptionReportingJobThatReportsException::dispatch(fn () => true);

            return 'ok';
        });

        $this->actingAs(new GenericUser(['id' => 'abc123', 'password' => 'secret', 'remember_token' => '']))
            ->get('/test')
            ->assertOk();

        // The request was performed by the user, so the jobs it dispatched
        // belong to them as well.
        $this->assertSame('abc123', $this->userIdInJobPayload(DB::table('jobs')->soleValue('payload')));
    }

    public function testItPreservesTheUserIdInTheContextWhenJobsDispatchJobs(): void
    {
        $this->setupExceptionReporting();
        $this->fakeEventsStreams();
        Config::set('queue.default', 'database');

        Context::addHidden('laravel_cloud_user_id', 'abc123');
        ExceptionReportingJobThatReportsException::dispatch(
            fn () => ExceptionReportingJobThatReportsException::dispatch(fn () => true)
        );

        Artisan::call('queue:work', [
            '--max-jobs' => 1,
            '--memory' => 1024,
            '--sleep' => 0,
            '--stop-when-empty' => true,
            '--tries' => 1,
        ]);

        // The job the worker dispatched belongs to the user the original job
        // was dispatched by.
        $this->assertSame('abc123', $this->userIdInJobPayload(DB::table('jobs')->soleValue('payload')));
    }

    public function testItCapturesTheDispatchingUserIdWhenAUserLogsOutDuringAJob(): void
    {
        $this->setupExceptionReporting();
        $streams = $this->fakeEventsStreams();
        Config::set('queue.default', 'database');

        // A job belongs to the user that dispatched it, rather than to any
        // user authenticated while handling it.
        Context::addHidden('laravel_cloud_user_id', 'dispatcher');
        ExceptionReportingJobThatReportsException::dispatch(function () {
            Auth::setUser(new GenericUser(['id' => 'impersonated', 'remember_token' => '']));
            Auth::logout();
        });

        Artisan::call('queue:work', [
            '--max-jobs' => 1,
            '--memory' => 1024,
            '--sleep' => 0,
            '--stop-when-empty' => true,
            '--tries' => 1,
        ]);

        $streams[0]->assertWrittenJsonContains(['user_id' => 'dispatcher', 'message' => 'Whoops!']);
    }

    public function testItCapturesUserIdInJobsWhenTheUserLogsInDuringTheJob(): void
    {
        $this->setupExceptionReporting();
        $streams = $this->fakeEventsStreams();
        Config::set('queue.default', 'database');

        ExceptionReportingJobThatReportsException::dispatch(function () {
            Auth::login(new GenericUser(['id' => 'abc123', 'password' => 'secret', 'remember_token' => '']));
        });

        // The worker is a separate process, so the guards have not been
        // resolved while the job is processed.
        Auth::forgetGuards();

        Artisan::call('queue:work', [
            '--max-jobs' => 1,
            '--memory' => 1024,
            '--sleep' => 0,
            '--stop-when-empty' => true,
            '--tries' => 1,
        ]);

        $this->assertCount(1, $streams);
        $streams[0]->assertWrittenJsonContains(['user_id' => 'abc123', 'message' => 'Whoops!']);
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
        $this->setupExceptionReporting();
        $streams = $this->fakeEventsStreams();
        Config::set('queue.default', 'database');

        Context::addHidden('laravel_cloud_user_id', 'abc123');
        ExceptionReportingJobThatReportsException::dispatch(fn () => true);

        Artisan::call('queue:work', [
            '--max-jobs' => 1,
            '--memory' => 1024,
            '--sleep' => 0,
            '--stop-when-empty' => true,
            '--tries' => 1,
        ]);

        $this->assertCount(1, $streams);
        $streams[0]->assertWrittenJsonContains(['user_id' => 'abc123', 'message' => 'Whoops!']);
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
        $this->setupExceptionReporting();
        $streams = $this->fakeEventsStreams();
        Config::set('queue.default', 'database');
        $_SERVER['LARAVEL_CLOUD_COMMAND_UUID'] = 'comm-465ebb4e-2f86-434f-8e1b-cc364f317cef';

        Artisan::command('test-command', function () {
            ExceptionReportingJobThatReportsException::dispatch(fn () => true);
        });
        $this->artisan('test-command')->assertOk();

        Artisan::call('queue:work', [
            '--max-jobs' => 1,
            '--memory' => 1024,
            '--sleep' => 0,
            '--stop-when-empty' => true,
            '--tries' => 1,
        ]);

        // The job is part of the execution that dispatched it, so it is
        // reported under the trace of the command.
        $this->assertCount(1, $streams);
        $streams[0]->assertWrittenJsonContains([
            'trace_id' => '465ebb4e-2f86-434f-8e1b-cc364f317cef',
            'execution_type' => 'job',
            'message' => 'Whoops!',
        ]);
    }

    public function testItReportsTheUrlAsItWasRequested(): void
    {
        $this->setupExceptionReporting();
        $streams = $this->fakeEventsStreams();

        Route::get('/users/{user}', function () {
            $this->setRunningInConsole(false);

            throw new RuntimeException('Whoops!');
        });

        // The parameters are out of order, one is given twice, one holds a
        // character that would be encoded, one is an array, and one is given
        // without a value.
        $this->get('http://localhost/users/123?b=2&a=1&a=3&filter=a b&x[]=1&x[]=2&flag')->assertServerError();

        $this->assertCount(1, $streams);
        $streams[0]->assertWrittenJson(function (array $payload) {
            $this->assertSame(
                'http://localhost/users/123?b=2&a=1&a=3&filter=a b&x[]=1&x[]=2&flag',
                $payload['execution_context']['url'],
            );

            return true;
        });
    }

    public function testItReportsTheRequestedPathWithoutTrimmingIt(): void
    {
        $this->setupExceptionReporting();
        $streams = $this->fakeEventsStreams();

        Route::get('/users/{user}', function () {
            $this->setRunningInConsole(false);

            throw new RuntimeException('Whoops!');
        });

        // The request is handled directly, as the test helpers trim trailing
        // slashes from the URL before the application sees them.
        $this->app->make(\Illuminate\Contracts\Http\Kernel::class)->handle(
            \Illuminate\Http\Request::create('http://localhost/users/123/')
        );

        $this->assertCount(1, $streams);
        $streams[0]->assertWrittenJson(function (array $payload) {
            // The trailing slash is retained, rather than being trimmed.
            $this->assertSame(
                'http://localhost/users/123/',
                $payload['execution_context']['url'],
            );

            return true;
        });
    }

    public function testItReportsTheRootPathAsItWasRequested(): void
    {
        $this->setupExceptionReporting();
        $streams = $this->fakeEventsStreams();

        Route::get('/', function () {
            $this->setRunningInConsole(false);

            throw new RuntimeException('Whoops!');
        });

        $this->get('http://localhost/')->assertServerError();

        $this->assertCount(1, $streams);
        $streams[0]->assertWrittenJson(function (array $payload) {
            // The root path is reported, rather than the host alone.
            $this->assertSame(
                'http://localhost/',
                $payload['execution_context']['url'],
            );

            return true;
        });
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
        $this->setupExceptionReporting();
        $streams = $this->fakeEventsStreams();

        Route::get('/test', function () {
            $this->setRunningInConsole(false);

            throw new RuntimeException('Whoops!');
        });
        $this->withBasicAuth('taylor', '$f4c4d3')
            ->withHeader('Proxy-Authorization', 'Bearer secret-token')
            ->withHeader('Cookie', 'laravel_session=abc123; XSRF-TOKEN=1234')
            ->withHeader('X-CSRF-TOKEN', 'csrf-token')
            ->withHeader('X-XSRF-TOKEN', 'secret')
            ->get('/test')
            ->assertServerError();

        $this->assertCount(1, $streams);
        $streams[0]->assertWrittenJson(function (array $payload) {
            $headers = $payload['execution_context']['headers'];

            $this->assertSame(['Basic [20 bytes redacted]'], $headers['authorization']);
            $this->assertSame(['Bearer [12 bytes redacted]'], $headers['proxy-authorization']);
            $this->assertSame(['laravel_session=[6 bytes redacted]; XSRF-TOKEN=[4 bytes redacted]'], $headers['cookie']);
            $this->assertSame(['[10 bytes redacted]'], $headers['x-csrf-token']);
            $this->assertSame(['[6 bytes redacted]'], $headers['x-xsrf-token']);

            // These are derived from the Authorization header by PHP, rather
            // than sent by the client, and are not redacted.
            $this->assertArrayNotHasKey('php-auth-user', $headers);
            $this->assertArrayNotHasKey('php-auth-pw', $headers);

            return true;
        });
    }

    public function testItRedactsConfiguredHeaders(): void
    {
        $this->setupExceptionReporting(['redact_headers' => ['Custom']]);
        $streams = $this->fakeEventsStreams();

        Route::get('/test', function () {
            $this->setRunningInConsole(false);

            throw new RuntimeException('Whoops!');
        });
        $this->withHeader('Authorization', 'Bearer secret-token')
            ->withHeader('Custom', 'secret')
            ->get('/test')
            ->assertServerError();

        $this->assertCount(1, $streams);
        $streams[0]->assertWrittenJson(function (array $payload) {
            $headers = $payload['execution_context']['headers'];

            $this->assertSame(['[6 bytes redacted]'], $headers['custom']);
            $this->assertSame(['Bearer secret-token'], $headers['authorization']);

            return true;
        });
    }

    public function testItCanDisableHeaderRedaction(): void
    {
        $this->setupExceptionReporting(['redact_headers' => []]);
        $streams = $this->fakeEventsStreams();

        Route::get('/test', function () {
            $this->setRunningInConsole(false);

            throw new RuntimeException('Whoops!');
        });
        $this->withBasicAuth('taylor', '$f4c4d3')
            ->withHeader('Proxy-Authorization', 'Bearer secret-token')
            ->withHeader('Cookie', 'laravel_session=abc123; XSRF-TOKEN=1234')
            ->get('/test')
            ->assertServerError();

        $this->assertCount(1, $streams);
        $streams[0]->assertWrittenJson(function (array $payload) {
            $headers = $payload['execution_context']['headers'];

            $this->assertSame(['Basic dGF5bG9yOiRmNGM0ZDM='], $headers['authorization']);
            $this->assertSame(['Bearer secret-token'], $headers['proxy-authorization']);
            $this->assertSame(['laravel_session=abc123; XSRF-TOKEN=1234'], $headers['cookie']);

            return true;
        });
    }

    public function testItRedactsUnconventionalSensitiveHeaders(): void
    {
        $this->setupExceptionReporting();
        $streams = $this->fakeEventsStreams();

        Route::get('/test', function () {
            $this->setRunningInConsole(false);

            throw new RuntimeException('Whoops!');
        });
        $this->withHeader('Authorization', 'secret-token')
            ->withHeader('Proxy-Authorization', 'secret-scheme secret-token')
            ->withHeader('Cookie', 'secret')
            ->get('/test')
            ->assertServerError();

        $this->assertCount(1, $streams);
        $streams[0]->assertWrittenJson(function (array $payload) {
            $headers = $payload['execution_context']['headers'];

            // Values we cannot confidently parse are redacted in their entirety.
            $this->assertSame(['[12 bytes redacted]'], $headers['authorization']);
            $this->assertSame(['[26 bytes redacted]'], $headers['proxy-authorization']);
            $this->assertSame(['[6 bytes redacted]'], $headers['cookie']);

            return true;
        });
    }

    public function testItRedactsEachValueOfRepeatedSensitiveHeaders(): void
    {
        $this->setupExceptionReporting(['redact_headers' => ['Custom']]);
        $streams = $this->fakeEventsStreams();

        Route::get('/test', function () {
            $this->setRunningInConsole(false);

            throw new RuntimeException('Whoops!');
        });
        $this->get('/test', ['Custom' => ['first', 'second-value']])
            ->assertServerError();

        $this->assertCount(1, $streams);
        $streams[0]->assertWrittenJson(function (array $payload) {
            $this->assertSame([
                '[5 bytes redacted]',
                '[12 bytes redacted]',
            ], $payload['execution_context']['headers']['custom']);

            return true;
        });
    }

    public function testItDoesNotMutateTheRequestHeadersWhenRedacting(): void
    {
        $this->setupExceptionReporting();
        $streams = $this->fakeEventsStreams();

        $authorization = null;
        Route::get('/test', function () use (&$authorization) {
            $this->setRunningInConsole(false);

            report(new RuntimeException('Whoops!'));

            $authorization = request()->header('Authorization');
        });
        $this->withHeader('Authorization', 'Bearer secret-token')
            ->get('/test')
            ->assertOk();

        $this->assertCount(1, $streams);
        $streams[0]->assertWrittenJson(function (array $payload) {
            $this->assertSame(['Bearer [12 bytes redacted]'], $payload['execution_context']['headers']['authorization']);

            return true;
        });
        $this->assertSame('Bearer secret-token', $authorization);
    }

    public function testItReportsNoHeadersWhenTheyCannotBeRetrieved(): void
    {
        $this->setupExceptionReporting();
        $streams = $this->fakeEventsStreams();

        Route::get('/test', function () {
            $this->setRunningInConsole(false);

            request()->headers = new HeaderBagThatThrows;

            throw new RuntimeException('Whoops!');
        });
        $this->get('/test')->assertServerError();

        $this->assertCount(1, $streams);
        $streams[0]->assertWrittenJson(function (array $payload) {
            $this->assertNull($payload['execution_context']['headers']);
            $this->assertSame('Whoops!', $payload['message']);

            return true;
        });
    }

    public function testItReportsNoRouteWhenItCannotBeRetrieved(): void
    {
        $this->setupExceptionReporting();
        $streams = $this->fakeEventsStreams();

        Route::get('/test', function () {
            $this->setRunningInConsole(false);

            request()->setRouteResolver(fn () => new RouteThatThrows('GET', '/test', []));

            throw new RuntimeException('Whoops!');
        });
        $this->get('/test')->assertServerError();

        $this->assertCount(1, $streams);
        $streams[0]->assertWrittenJson(function (array $payload) {
            $this->assertNull($payload['execution_context']['route']);
            $this->assertSame('Whoops!', $payload['message']);

            return true;
        });
    }

    public function testItReportsNoRequestPayloadWhenItCannotBeRetrieved(): void
    {
        // The redacted fields are given by the platform as JSON, so they
        // may not be the list the reporter expects.
        $this->setupExceptionReporting([
            'capture_request_payload' => true,
            'redact_request_payload_fields' => 'password',
        ]);
        $streams = $this->fakeEventsStreams();

        Route::post('/test', function () {
            $this->setRunningInConsole(false);

            throw new RuntimeException('Whoops!');
        });
        $this->post('/test', ['key' => 'value'])->assertServerError();

        $this->assertCount(1, $streams);
        $streams[0]->assertWrittenJson(function (array $payload) {
            $this->assertNull($payload['execution_context']['payload']);
            $this->assertSame('Whoops!', $payload['message']);

            return true;
        });
    }

    public function testItReportsNoFilesWhenTheyCannotBeRetrieved(): void
    {
        $this->setupExceptionReporting(['capture_request_payload' => true]);
        $streams = $this->fakeEventsStreams();

        Route::post('/test', function () {
            $this->setRunningInConsole(false);

            request()->files = new FileBagThatThrows;

            throw new RuntimeException('Whoops!');
        });
        $this->post('/test', ['key' => 'value'])->assertServerError();

        $this->assertCount(1, $streams);
        $streams[0]->assertWrittenJson(function (array $payload) {
            $this->assertNull($payload['execution_context']['files']);
            $this->assertSame('Whoops!', $payload['message']);

            return true;
        });
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
            '--memory' => 1024,
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

    public function testItReportsNoExecutionDetailsWhenTheyCannotBeRetrieved(): void
    {
        $this->setupExceptionReporting();
        $streams = $this->fakeEventsStreams();

        // Some queue drivers, e.g. Beanstalkd, throw when the job is
        // interacted with after it has been processed.
        Event::dispatch(new JobPopped('database', new JobThatThrowsWhenInteractedWith));

        report(new RuntimeException('Whoops!'));

        $this->assertCount(1, $streams);
        $streams[0]->assertWrittenJson(function (array $payload) {
            $this->assertArrayNotHasKey('trace_id', $payload);
            $this->assertArrayNotHasKey('execution_type', $payload);
            $this->assertArrayNotHasKey('execution_context', $payload);

            $this->assertSame('Whoops!', $payload['message']);
            $this->assertSame('RuntimeException', $payload['class']);

            return true;
        });
    }

    public function testItCapturesTheRawSerializableClosureStreamInTracesForQueuedClosures(): void
    {
        $this->captureTraceArguments();
        $this->setupExceptionReporting();
        $streams = $this->fakeEventsStreams();
        Config::set('queue.default', 'database');

        dispatch(function ($foo = null) {
            throw new RuntimeException('Whoops from a queued closure!');
        });

        Artisan::call('queue:work', [
            '--max-jobs' => 1,
            '--memory' => 1024,
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
                // PHP only names the file and line of a closure from 8.4.
                'function' => PHP_VERSION_ID < 80400
                    ? '{closure}'
                    : "{closure:laravel-serializable-closure://function (\$foo = null) {\n            throw new \\RuntimeException('Whoops from a queued closure!');\n        }:2}",
                'class' => 'Illuminate\Tests\Foundation\Cloud\ExceptionReportingTest',
                'type' => '::',
                'args' => ['types' => ['null', 'Illuminate\Queue\CallQueuedClosure']],
            ], $json['trace'][1]);

            return true;
        });
    }

    public function testExceptionsBetweenJobsAreAttributedToTheQueueWorkCommand(): void
    {
        $this->freezeTime();

        // The database is migrated on the first query, which runs a command
        // of its own. It is triggered here, before the reporter is listening,
        // so that the worker is the first command the reporter sees.
        DB::select('select 1');

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
            '--memory' => 1024,
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
                'command' => $this->reportedCommandLine('queue:work --max-jobs=2 --memory=1024 --sleep=0 --stop-when-empty --tries=1'),
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
                'command' => $this->reportedCommandLine('test-command'),
            ],
        ]);
    }

    public function testItCapturesTheClassAndFullCommandLineForClassBasedCommands(): void
    {
        $this->setupExceptionReporting();
        $streams = $this->fakeEventsStreams();
        Artisan::registerCommand(new ExceptionReportingTestCommand);

        $this->runArtisanCommand(['artisan', 'test-class-command', '--flag', 'value']);

        $this->assertCount(1, $streams);
        $streams[0]->assertWrittenJson(function (array $payload) {
            $this->assertSame(ExceptionReportingTestCommand::class, $payload['execution_context']['class']);
            $this->assertSame('test-class-command', $payload['execution_context']['name']);
            $this->assertSame($this->reportedCommandLine('test-class-command --flag=value'), $payload['execution_context']['command']);

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

        // The command never starts, so nothing is captured for it and the
        // reporter resolves it by name to report its class and its command
        // line. Each of those attempts construction again, as Symfony's own
        // Application::has() resolves the command as a side effect of
        // checking whether it exists, and a failed resolution leaves nothing
        // for it to remember. What matters is that the failures do not
        // swallow the report.
        $this->assertSame(3, ThrowingConstructorTestCommand::$constructionAttempts);

        $this->assertCount(1, $streams);
        $streams[0]->assertWrittenJson(function (array $payload) {
            // The container wraps the constructor failure in an
            // EntryNotFoundException; the original exception is preserved
            // as the "previous" exception in the chain.
            $this->assertSame(\Illuminate\Container\EntryNotFoundException::class, $payload['class']);
            $this->assertSame('Boom from the constructor', $payload['previous'][0]['message']);
            // The command cannot be resolved while reporting, as doing so
            // constructs it, which is what threw in the first place. The name
            // falls back to the console input, while the values that need the
            // command itself are unavailable.
            $this->assertSame('throwing-constructor-command', $payload['execution_context']['name']);
            $this->assertNull($payload['execution_context']['class']);
            $this->assertNull($payload['execution_context']['command']);

            return true;
        });
    }

    public function testItRedactsSensitiveCommandArgumentsAndOptions(): void
    {
        $this->setupExceptionReporting();
        $streams = $this->fakeEventsStreams();
        Artisan::registerCommand(new ExceptionReportingSensitiveInputTestCommand);

        $this->runArtisanCommand(['artisan', 'test-sensitive-command', 'taylor', 'hunter2', '--secret=shh', '--keep=this']);

        $this->assertCount(1, $streams);
        $streams[0]->assertWrittenJson(function (array $payload) {
            $this->assertSame(
                $this->reportedCommandLine('test-sensitive-command taylor [7 bytes redacted] --secret=[3 bytes redacted] --keep=this'),
                $payload['execution_context']['command'],
            );

            return true;
        });
    }

    public function testItRedactsSensitiveCommandOptionsGivenAsSeparateTokens(): void
    {
        $this->setupExceptionReporting();
        $streams = $this->fakeEventsStreams();
        Artisan::registerCommand(new ExceptionReportingSensitiveInputTestCommand);

        $this->runArtisanCommand(['artisan', 'test-sensitive-command', 'taylor', 'hunter2', '--secret', 'shh']);

        $this->assertCount(1, $streams);
        $streams[0]->assertWrittenJson(function (array $payload) {
            // The command line is rebuilt from the parsed input, so an option
            // given as separate tokens is reported in the "=" form.
            $this->assertSame(
                $this->reportedCommandLine('test-sensitive-command taylor [7 bytes redacted] --secret=[3 bytes redacted]'),
                $payload['execution_context']['command'],
            );

            return true;
        });
    }

    public function testItRedactsSensitiveCommandOptionsGivenByTheirShortcut(): void
    {
        $this->setupExceptionReporting(['redact_command_input_fields' => ['proxy']]);
        $streams = $this->fakeEventsStreams();
        Artisan::registerCommand(new ExceptionReportingSensitiveInputTestCommand);

        $this->runArtisanCommand(['artisan', 'test-sensitive-command', 'taylor', 'hunter2', '-p', 'shh']);

        $this->assertCount(1, $streams);
        $streams[0]->assertWrittenJson(function (array $payload) {
            // The shortcut is reported by the option's name, and the password
            // argument is not redacted, as the configured fields have been
            // replaced.
            $this->assertSame(
                $this->reportedCommandLine('test-sensitive-command taylor hunter2 --proxy=[3 bytes redacted]'),
                $payload['execution_context']['command'],
            );

            return true;
        });
    }

    public function testItRedactsEachValueOfSensitiveArrayCommandOptions(): void
    {
        $this->setupExceptionReporting(['redact_command_input_fields' => ['token']]);
        $streams = $this->fakeEventsStreams();
        Artisan::registerCommand(new ExceptionReportingSensitiveInputTestCommand);

        $this->runArtisanCommand(['artisan', 'test-sensitive-command', 'taylor', 'hunter2', '--token=first', '--token=second']);

        $this->assertCount(1, $streams);
        $streams[0]->assertWrittenJson(function (array $payload) {
            $this->assertSame(
                $this->reportedCommandLine('test-sensitive-command taylor hunter2 --token=[5 bytes redacted] --token=[6 bytes redacted]'),
                $payload['execution_context']['command'],
            );

            return true;
        });
    }

    public function testItReportsNegatedBooleanCommandOptions(): void
    {
        $this->setupExceptionReporting();
        $streams = $this->fakeEventsStreams();
        Artisan::registerCommand(new ExceptionReportingSensitiveInputTestCommand);

        $this->runArtisanCommand(['artisan', 'test-sensitive-command', 'taylor', 'hunter2', '--no-ansi']);

        $this->assertCount(1, $streams);
        $streams[0]->assertWrittenJson(function (array $payload) {
            $this->assertSame(
                $this->reportedCommandLine('test-sensitive-command taylor [7 bytes redacted] --no-ansi'),
                $payload['execution_context']['command'],
            );

            return true;
        });
    }

    public function testItReportsGivenBooleanCommandOptions(): void
    {
        $this->setupExceptionReporting();
        $streams = $this->fakeEventsStreams();
        Artisan::registerCommand(new ExceptionReportingSensitiveInputTestCommand);

        $this->runArtisanCommand(['artisan', 'test-sensitive-command', 'taylor', 'hunter2', '--ansi']);

        $this->assertCount(1, $streams);
        $streams[0]->assertWrittenJson(function (array $payload) {
            $this->assertSame(
                $this->reportedCommandLine('test-sensitive-command taylor [7 bytes redacted] --ansi'),
                $payload['execution_context']['command'],
            );

            return true;
        });
    }

    public function testItDoesNotRedactBooleanCommandOptions(): void
    {
        $this->setupExceptionReporting(['redact_command_input_fields' => ['force', 'ansi']]);
        $streams = $this->fakeEventsStreams();
        Artisan::registerCommand(new ExceptionReportingSensitiveInputTestCommand);

        $this->runArtisanCommand(['artisan', 'test-sensitive-command', 'taylor', 'hunter2', '--force', '--no-ansi']);

        $this->assertCount(1, $streams);
        $streams[0]->assertWrittenJson(function (array $payload) {
            // Only string values are redacted, and a boolean's value is
            // the presence of the flag, which redacting would not hide.
            $this->assertSame(
                $this->reportedCommandLine('test-sensitive-command taylor hunter2 --force --no-ansi'),
                $payload['execution_context']['command'],
            );

            return true;
        });
    }

    public function testItReportsCommandInputGivenAnEmptyValue(): void
    {
        $this->setupExceptionReporting();
        $streams = $this->fakeEventsStreams();
        Artisan::registerCommand(new ExceptionReportingSensitiveInputTestCommand);

        // An unset shell variable, such as "--secret=$TOKEN", arrives as an
        // empty value rather than as an option given without one.
        $this->runArtisanCommand(['artisan', 'test-sensitive-command', '', 'hunter2', '--secret=', '--keep=']);

        $this->assertCount(1, $streams);
        $streams[0]->assertWrittenJson(function (array $payload) {
            $this->assertSame(
                $this->reportedCommandLine('test-sensitive-command '.escapeshellarg('').' [7 bytes redacted] --secret=[0 bytes redacted] --keep='.escapeshellarg('')),
                $payload['execution_context']['command'],
            );

            return true;
        });
    }

    public function testItReportsArrayCommandOptionsGivenWithoutAValue(): void
    {
        $this->setupExceptionReporting();
        $streams = $this->fakeEventsStreams();
        Artisan::registerCommand(new ExceptionReportingSensitiveInputTestCommand);

        $this->runArtisanCommand(['artisan', 'test-sensitive-command', 'taylor', 'hunter2', '--token=first', '--token']);

        $this->assertCount(1, $streams);
        $streams[0]->assertWrittenJson(function (array $payload) {
            // The second occurrence has no value to redact.
            $this->assertSame(
                $this->reportedCommandLine('test-sensitive-command taylor [7 bytes redacted] --token=[5 bytes redacted] --token'),
                $payload['execution_context']['command'],
            );

            return true;
        });
    }

    public function testItReportsEachValueOfVariadicCommandArguments(): void
    {
        // The files argument is not sensitive under this configuration.
        $this->setupExceptionReporting(['redact_command_input_fields' => ['password']]);
        $streams = $this->fakeEventsStreams();
        Artisan::registerCommand(new ExceptionReportingSensitiveInputTestCommand);

        $this->runArtisanCommand(['artisan', 'test-sensitive-command', 'taylor', 'hunter2', 'first', 'second file']);

        $this->assertCount(1, $streams);
        $streams[0]->assertWrittenJson(function (array $payload) {
            $this->assertSame(
                $this->reportedCommandLine('test-sensitive-command taylor [7 bytes redacted] first '.escapeshellarg('second file')),
                $payload['execution_context']['command'],
            );

            return true;
        });
    }

    public function testItRedactsEachValueOfSensitiveVariadicCommandArguments(): void
    {
        $this->setupExceptionReporting(['redact_command_input_fields' => ['files']]);
        $streams = $this->fakeEventsStreams();
        Artisan::registerCommand(new ExceptionReportingSensitiveInputTestCommand);

        $this->runArtisanCommand(['artisan', 'test-sensitive-command', 'taylor', 'hunter2', 'first', 'second']);

        $this->assertCount(1, $streams);
        $streams[0]->assertWrittenJson(function (array $payload) {
            $this->assertSame(
                $this->reportedCommandLine('test-sensitive-command taylor hunter2 [5 bytes redacted] [6 bytes redacted]'),
                $payload['execution_context']['command'],
            );

            return true;
        });
    }

    public function testItReportsEachValueOfArrayCommandOptions(): void
    {
        // The token option is not sensitive under this configuration.
        $this->setupExceptionReporting(['redact_command_input_fields' => ['password']]);
        $streams = $this->fakeEventsStreams();
        Artisan::registerCommand(new ExceptionReportingSensitiveInputTestCommand);

        $this->runArtisanCommand(['artisan', 'test-sensitive-command', 'taylor', 'hunter2', '--token=first', '--token=second value']);

        $this->assertCount(1, $streams);
        $streams[0]->assertWrittenJson(function (array $payload) {
            $this->assertSame(
                $this->reportedCommandLine('test-sensitive-command taylor [7 bytes redacted] --token=first --token='.escapeshellarg('second value')),
                $payload['execution_context']['command'],
            );

            return true;
        });
    }

    public function testItReportsNamespacedCommandNamesUnescaped(): void
    {
        $this->setupExceptionReporting();
        $streams = $this->fakeEventsStreams();

        Artisan::command('test:namespaced-command {user}', function () {
            $_SERVER['argv'] = ['artisan', 'test:namespaced-command', 'taylor'];
            report(new RuntimeException('Whoops!'));
        });

        $this->artisan('test:namespaced-command', ['user' => 'taylor'])->assertOk();

        $this->assertCount(1, $streams);
        $streams[0]->assertWrittenJson(function (array $payload) {
            $this->assertSame(
                $this->reportedCommandLine('test:namespaced-command taylor'),
                $payload['execution_context']['command'],
            );

            return true;
        });
    }

    public function testItCapturesTheCommandNameAsItWasEntered(): void
    {
        $this->setupExceptionReporting();
        $streams = $this->fakeEventsStreams();

        Artisan::command('test:canonical-command', fn () => report(new RuntimeException('Whoops!')));

        $this->runArtisanCommand(['artisan', 'test:canonical-command']);

        $this->assertCount(1, $streams);
        $streams[0]->assertWrittenJson(function (array $payload) {
            $this->assertSame('test:canonical-command', $payload['execution_context']['name']);
            $this->assertSame($this->reportedCommandLine('test:canonical-command'), $payload['execution_context']['command']);

            return true;
        });
    }

    public function testItCapturesTheCanonicalCommandNameWhenAbbreviated(): void
    {
        $this->setupExceptionReporting();
        $streams = $this->fakeEventsStreams();

        Artisan::command('test:canonical-command', fn () => report(new RuntimeException('Whoops!')));

        // Artisan resolves the abbreviation to the command above, so the
        // canonical name is reported rather than the abbreviation.
        $this->runArtisanCommand(['artisan', 'test:can']);

        $this->assertCount(1, $streams);
        $streams[0]->assertWrittenJson(function (array $payload) {
            $this->assertSame('test:canonical-command', $payload['execution_context']['name']);
            $this->assertSame($this->reportedCommandLine('test:can'), $payload['execution_context']['command']);

            return true;
        });
    }

    public function testItAttributesExceptionsToTheCommandTheProcessWasGiven(): void
    {
        $this->setupExceptionReporting();
        $streams = $this->fakeEventsStreams();

        Artisan::command('test:inner-command', fn () => report(new RuntimeException('Whoops!')));
        Artisan::command('test:outer-command {user}', fn () => Artisan::call('test:inner-command'));

        $this->runArtisanCommand(['artisan', 'test:outer-command', 'taylor']);

        $this->assertCount(1, $streams);
        $streams[0]->assertWrittenJson(function (array $payload) {
            // The inner command starts while the outer one is still running.
            // The outer one is what the process was given, so it is reported,
            // and the inner one is not recorded anywhere.
            $this->assertSame('test:outer-command', $payload['execution_context']['name']);
            $this->assertSame($this->reportedCommandLine('test:outer-command taylor'), $payload['execution_context']['command']);
            $this->assertSame('Whoops!', $payload['message']);

            return true;
        });
    }

    public function testItReportsSensitiveCommandOptionsGivenWithoutAValue(): void
    {
        $this->setupExceptionReporting();
        $streams = $this->fakeEventsStreams();
        Artisan::registerCommand(new ExceptionReportingSensitiveInputTestCommand);

        $this->runArtisanCommand(['artisan', 'test-sensitive-command', 'taylor', 'hunter2', '--secret']);

        $this->assertCount(1, $streams);
        $streams[0]->assertWrittenJson(function (array $payload) {
            // There is no value to redact, and the option carries nothing
            // beyond the fact that it was given.
            $this->assertSame(
                $this->reportedCommandLine('test-sensitive-command taylor [7 bytes redacted] --secret'),
                $payload['execution_context']['command'],
            );

            return true;
        });
    }

    public function testItReportsTheCommandLineAsItWasGiven(): void
    {
        $this->setupExceptionReporting();
        $streams = $this->fakeEventsStreams();
        Artisan::registerCommand(new ExceptionReportingSensitiveInputTestCommand);

        $this->runArtisanCommand(['artisan', 'test-sensitive-command', 'taylor', 'hunter2', '--force']);

        $this->assertCount(1, $streams);
        $streams[0]->assertWrittenJson(function (array $payload) {
            // Only the given tokens are reported, so the options carrying
            // their default values are left out.
            $this->assertSame(
                $this->reportedCommandLine('test-sensitive-command taylor [7 bytes redacted] --force'),
                $payload['execution_context']['command'],
            );

            return true;
        });
    }

    public function testItReportsNoCommandLineWhenTheCommandInputCannotBeParsed(): void
    {
        $this->setupExceptionReporting();
        $streams = $this->fakeEventsStreams();
        Artisan::registerCommand(new ExceptionReportingSensitiveInputTestCommand);

        // The option is not defined on the command, so the command never
        // starts and the reporter parses the console input itself, where
        // binding the input against the definition throws.
        $_SERVER['argv'] = ['artisan', 'test-sensitive-command', 'taylor', 'hunter2', '--nope=1'];

        ($this->exceptionReporter())(new RuntimeException('Whoops!'));

        $this->assertCount(1, $streams);
        $streams[0]->assertWrittenJson(function (array $payload) {
            // The command itself resolves, so only the command line is
            // unavailable. The rest of the context is still reported.
            $this->assertSame('test-sensitive-command', $payload['execution_context']['name']);
            $this->assertNull($payload['execution_context']['command']);
            $this->assertSame('Whoops!', $payload['message']);

            return true;
        });
    }

    public function testItReportsNoCommandLineWhenTheCommandArgumentsAreInvalid(): void
    {
        $this->setupExceptionReporting();
        $streams = $this->fakeEventsStreams();

        Artisan::command('test-command', fn () => null);

        // The command takes no arguments, so the given values have nowhere
        // to bind when the reporter parses the input.
        $_SERVER['argv'] = ['artisan', 'test-command', 'one', 'two'];

        ($this->exceptionReporter())(new RuntimeException('Whoops!'));

        $this->assertCount(1, $streams);
        $streams[0]->assertWrittenJson(function (array $payload) {
            // The command itself resolves, so only the command line is
            // unavailable. The rest of the context is still reported.
            $this->assertSame('test-command', $payload['execution_context']['name']);
            $this->assertNull($payload['execution_context']['command']);
            $this->assertSame('Whoops!', $payload['message']);

            return true;
        });
    }

    public function testItReportsNoClassOrCommandLineWhenTheCommandCannotBeResolved(): void
    {
        $this->setupExceptionReporting();
        $streams = $this->fakeEventsStreams();

        $_SERVER['argv'] = ['artisan', 'unknown-command', 'hunter2'];

        ($this->exceptionReporter())(new RuntimeException('Whoops!'));

        $this->assertCount(1, $streams);
        $streams[0]->assertWrittenJson(function (array $payload) {
            // The name falls back to the console input, while the class and
            // the command line, which both need the command itself, are
            // simply unknown.
            $this->assertSame('command', $payload['execution_type']);

            $this->assertSame('unknown-command', $payload['execution_context']['name']);
            $this->assertNull($payload['execution_context']['class']);
            $this->assertNull($payload['execution_context']['command']);
            $this->assertSame('Whoops!', $payload['message']);

            return true;
        });
    }

    public function testItCapturesTheClassOfScheduledCallbacks(): void
    {
        $this->setupExceptionReporting();

        $this->assertSame(
            Closure::class,
            $this->reportedScheduledTaskClass(fn (Schedule $schedule) => $schedule->call(fn () => null)),
        );

        $this->assertSame(
            ScheduledTaskCallback::class,
            $this->reportedScheduledTaskClass(fn (Schedule $schedule) => $schedule->call(new ScheduledTaskCallback)),
        );

        $this->assertSame(
            ScheduledTaskCallback::class,
            $this->reportedScheduledTaskClass(
                fn (Schedule $schedule) => $schedule->call([new ScheduledTaskCallback, 'handle']),
            ),
        );

        $this->assertSame(
            ScheduledTaskCallback::class,
            $this->reportedScheduledTaskClass(
                fn (Schedule $schedule) => $schedule->call(ScheduledTaskCallback::class.'@handle'),
            ),
        );

        // The class is still reported when the task was given a name of its
        // own, as the name no longer identifies it.
        $this->assertSame(
            ScheduledTaskCallback::class,
            $this->reportedScheduledTaskClass(
                fn (Schedule $schedule) => $schedule->call(new ScheduledTaskCallback)->name('Prune stale records'),
            ),
        );
    }

    public function testItCapturesTheClassOfScheduledCommands(): void
    {
        $this->setupExceptionReporting();
        $streams = $this->fakeEventsStreams();
        Artisan::registerCommand(new ExceptionReportingTestCommand);

        // The class is resolved away when the task is defined, so the name
        // alone does not identify it.
        $task = $this->app->make(Schedule::class)
            ->command(ExceptionReportingTestCommand::class)
            ->everyMinute();

        Event::dispatch(new ScheduledTaskStarting($task));

        report(new RuntimeException('Whoops!'));

        $this->assertCount(1, $streams);
        $streams[0]->assertWrittenJson(function (array $payload) {
            $this->assertSame('php artisan test-class-command', $payload['execution_context']['name']);
            $this->assertSame(ExceptionReportingTestCommand::class, $payload['execution_context']['class']);

            return true;
        });
    }

    public function testItDoesNotCaptureAClassForScheduledTasksThatAreNotCommands(): void
    {
        $this->setupExceptionReporting();
        $streams = $this->fakeEventsStreams();

        $task = $this->app->make(Schedule::class)
            ->exec('rsync -a /a /b')
            ->everyMinute();

        Event::dispatch(new ScheduledTaskStarting($task));

        report(new RuntimeException('Whoops!'));

        $this->assertCount(1, $streams);
        $streams[0]->assertWrittenJson(function (array $payload) {
            $this->assertSame('rsync -a /a /b', $payload['execution_context']['name']);
            $this->assertNull($payload['execution_context']['class']);

            return true;
        });
    }

    public function testItCapturesANullTimezoneForScheduledTasksWithoutOne(): void
    {
        $this->freezeTime();
        $this->setupExceptionReporting();
        $streams = $this->fakeEventsStreams();

        // A task is given no timezone of its own, and the schedule's default
        // may be null, e.g. when the application has no configured timezone.
        $task = $this->app->make(Schedule::class)
            ->call(fn () => report(new RuntimeException('Whoops!')))
            ->name('test-scheduled-task')
            ->everyMinute();

        $task->timezone = null;

        $this->runArtisanCommand(['artisan', 'schedule:run']);

        $this->assertCount(1, $streams);
        $streams[0]->assertWrittenJsonContains([
            'execution_type' => 'scheduled_task',
            'execution_context' => [
                'timestamp' => now()->format('Y-m-d H:i:s.u'),
                'name' => 'test-scheduled-task',
                'class' => Closure::class,
                'cron' => '* * * * *',
                'timezone' => null,
                'repeat_seconds' => null,
                'without_overlapping' => false,
                'on_one_server' => false,
                'run_in_background' => false,
                'even_in_maintenance_mode' => false,
            ],
        ]);
    }

    public function testItCapturesScheduledTaskExecutionContext(): void
    {
        $this->freezeTime();
        $this->setupExceptionReporting();
        $streams = $this->fakeEventsStreams();

        $this->app->make(Schedule::class)
            ->call(fn () => report(new RuntimeException('Whoops!')))
            ->name('test-scheduled-task')
            ->everyMinute()
            ->timezone('Australia/Melbourne');

        $this->runArtisanCommand(['artisan', 'schedule:run']);

        $this->assertCount(1, $streams);
        $streams[0]->assertWrittenJsonContains([
            'execution_type' => 'scheduled_task',
            'execution_context' => [
                'timestamp' => now()->format('Y-m-d H:i:s.u'),
                'name' => 'test-scheduled-task',
                'class' => Closure::class,
                'cron' => '* * * * *',
                'timezone' => 'Australia/Melbourne',
                'repeat_seconds' => null,
                'without_overlapping' => false,
                'on_one_server' => false,
                'run_in_background' => false,
                'even_in_maintenance_mode' => false,
            ],
        ]);
    }

    public function testItCapturesTheRepeatSecondsOfSubMinuteScheduledTasks(): void
    {
        $this->setupExceptionReporting();
        $streams = $this->fakeEventsStreams();

        // The task is started directly, as the scheduler runs a sub-minute
        // task repeatedly until the minute is over.
        $task = $this->app->make(Schedule::class)
            ->call(fn () => null)
            ->name('test-scheduled-task')
            ->everyTenSeconds();

        Event::dispatch(new ScheduledTaskStarting($task));

        report(new RuntimeException('Whoops!'));

        $this->assertCount(1, $streams);
        $streams[0]->assertWrittenJson(function (array $payload) {
            $this->assertSame(10, $payload['execution_context']['repeat_seconds']);
            $this->assertSame('* * * * *', $payload['execution_context']['cron']);

            return true;
        });
    }

    public function testItAttributesExceptionsThrownWithinScheduledTasksToTheTask(): void
    {
        $this->setupExceptionReporting();
        $streams = $this->fakeEventsStreams();

        // The task's own exception is reported by the scheduler once the task
        // has run, rather than by the task itself.
        $this->app->make(Schedule::class)
            ->call(fn () => throw new RuntimeException('Whoops!'))
            ->name('test-scheduled-task')
            ->everyMinute();

        $this->runArtisanCommand(['artisan', 'schedule:run']);

        $this->assertCount(1, $streams);
        $streams[0]->assertWrittenJsonContains([
            'execution_type' => 'scheduled_task',
            'message' => 'Whoops!',
        ]);
    }

    public function testItAttributesFailingScheduledCommandsToTheTask(): void
    {
        $this->setupExceptionReporting();
        $streams = $this->fakeEventsStreams();

        $task = $this->app->make(Schedule::class)
            ->exec('exit 1')
            ->name('test-scheduled-task')
            ->everyMinute();

        // A scheduled command that exits non-zero dispatches the "finished"
        // event and is only then reported as a failure by the scheduler, so
        // the events are dispatched here in that order.
        Event::dispatch(new ScheduledTaskStarting($task));
        $task->exitCode = 1;
        Event::dispatch(new ScheduledTaskFinished($task, 0.0));

        report(new RuntimeException('Scheduled command [exit 1] failed with exit code [1].'));

        $this->assertCount(1, $streams);
        $streams[0]->assertWrittenJson(function (array $payload) {
            $this->assertSame('scheduled_task', $payload['execution_type']);
            $this->assertSame('exit 1', $payload['execution_context']['name']);

            return true;
        });
    }

    public function testItReportsTheCommandAsTheScheduledTaskName(): void
    {
        $this->setupExceptionReporting();

        $this->assertSame(
            'php artisan inspire',
            $this->reportedScheduledTaskName(fn (Schedule $schedule) => $schedule->command('inspire')),
        );

        // The description is not used, as it may be changed without the task
        // itself changing.
        $this->assertSame(
            'php artisan inspire',
            $this->reportedScheduledTaskName(
                fn (Schedule $schedule) => $schedule->command('inspire')->name('Inspire people'),
            ),
        );
    }

    public function testItReportsTheShellCommandAsTheScheduledTaskName(): void
    {
        $this->setupExceptionReporting();

        $this->assertSame(
            'rsync -a /a /b',
            $this->reportedScheduledTaskName(fn (Schedule $schedule) => $schedule->exec('rsync -a /a /b')),
        );
    }

    public function testItReportsTheClosureLocationAsTheScheduledTaskName(): void
    {
        $this->setupExceptionReporting();

        $line = __LINE__ + 3;

        $name = $this->reportedScheduledTaskName(
            fn (Schedule $schedule) => $schedule->call(fn () => null),
        );

        // A task given neither a description nor a name is identified by the
        // closure it runs, as every one of them is otherwise a "Callback".
        $this->assertSame(
            'Closure at: tests/Foundation/Cloud/ExceptionReportingTest.php:'.$line,
            $name,
        );
    }

    public function testItReportsTheDescriptionAsTheScheduledCallbackTaskName(): void
    {
        $this->setupExceptionReporting();

        // There is no command to fall back on, so the description is used.
        $this->assertSame(
            'Prune stale records',
            $this->reportedScheduledTaskName(
                fn (Schedule $schedule) => $schedule->call(fn () => null)->name('Prune stale records'),
            ),
        );
    }

    public function testItReportsTheCallbackClassAsTheScheduledTaskName(): void
    {
        $this->setupExceptionReporting();

        $this->assertSame(
            'App\Tasks\PruneRecords',
            $this->reportedScheduledTaskName(
                fn (Schedule $schedule) => $schedule->call('App\Tasks\PruneRecords'),
            ),
        );

        $this->assertSame(
            ScheduledTaskCallback::class,
            $this->reportedScheduledTaskName(
                fn (Schedule $schedule) => $schedule->call(new ScheduledTaskCallback),
            ),
        );

        $this->assertSame(
            ScheduledTaskCallback::class,
            $this->reportedScheduledTaskName(
                fn (Schedule $schedule) => $schedule->call([new ScheduledTaskCallback, 'handle']),
            ),
        );
    }

    public function testItStopsAttributingExceptionsToAFailedScheduledCommandOnceReported(): void
    {
        $this->setupExceptionReporting();
        $streams = $this->fakeEventsStreams();

        $task = $this->app->make(Schedule::class)
            ->exec('exit 1')
            ->name('test-scheduled-task')
            ->everyMinute();

        Event::dispatch(new ScheduledTaskStarting($task));
        $task->exitCode = 1;
        Event::dispatch(new ScheduledTaskFinished($task, 0.0));

        // The scheduler reports the failure once the task has finished...
        report(new RuntimeException('Scheduled command [exit 1] failed with exit code [1].'));

        // ...and anything reported after it belongs to the command again.
        report(new RuntimeException('Whoops!'));

        $this->assertCount(1, $streams);
        $streams[0]->assertWritten(function (string $stream) {
            [$failure, $next] = array_map(
                fn ($write) => json_decode($write, associative: true, flags: JSON_THROW_ON_ERROR),
                array_slice(explode("\n", $stream), 0, 2),
            );

            $this->assertSame('scheduled_task', $failure['execution_type']);
            $this->assertSame('command', $next['execution_type']);

            return true;
        });
    }

    public function testItAttributesExceptionsOutsideOfScheduledTasksToTheCommand(): void
    {
        $this->setupExceptionReporting();
        $streams = $this->fakeEventsStreams();

        $this->app->make(Schedule::class)
            ->call(fn () => null)
            ->name('test-scheduled-task')
            ->everyMinute();

        // Registered after the reporter, so the task context has been flushed
        // by the time this runs.
        Event::listen(fn (ScheduledTaskFinished $event) => report(new RuntimeException('Whoops!')));

        $this->runArtisanCommand(['artisan', 'schedule:run']);

        $this->assertCount(1, $streams);
        $streams[0]->assertWrittenJson(function (array $payload) {
            $this->assertSame('command', $payload['execution_type']);
            $this->assertSame('schedule:run', $payload['execution_context']['name']);
            $this->assertSame(
                \Illuminate\Console\Scheduling\ScheduleRunCommand::class,
                $payload['execution_context']['class'],
            );
            $this->assertSame($this->reportedCommandLine('schedule:run'), $payload['execution_context']['command']);

            return true;
        });
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
        $streams[0]->assertWrittenJson(function (array $payload) use ($line) {
            $this->assertTrue(Str::isUuid($payload['id']));
            unset($payload['id']);

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
            ], $payload);

            return true;
        });
    }

    public function testItAppendsTheExceptionFileAndLineAsTheFirstFrameInTheTrace(): void
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

    public function testItReportsWindowsPathsWithForwardSlashes(): void
    {
        $this->setupExceptionReporting();
        $streams = $this->fakeEventsStreams();

        // Paths are reported the same way whichever platform the application
        // runs on, so that a file is the same file everywhere.
        $reporter = new ExceptionReporter(
            $this->app[Events::class],
            $this->app[BladeMapper::class],
            'D:\\a\\framework\\framework\\',
            ['stop' => true],
        );

        $reporter(new ExceptionThrownOnWindows);

        $this->assertCount(1, $streams);
        $streams[0]->assertWrittenJson(function (array $payload) {
            $this->assertSame('tests/Foundation/Cloud/Whoops.php', $payload['trace'][0]['file']);

            return true;
        });
    }

    public function testItFormatsTraces(): void
    {
        $this->captureTraceArguments();
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

    public function testItReportsNoTraceArgumentsWhenPhpDoesNotRetainThem(): void
    {
        $this->captureTraceArguments(false);
        $this->setupExceptionReporting();
        $streams = $this->fakeEventsStreams();

        (function ($one, $two) {
            report(new RuntimeException('Whoops!'));
        })('a', 'b');

        $this->assertCount(1, $streams);
        $streams[0]->assertWrittenJson(function ($json) {
            $this->assertArrayNotHasKey('args', $json['trace'][1]);
            $this->assertSame('Whoops!', $json['message']);

            return true;
        });
    }

    public function testItCapturesTheRawClassNameForAnonymousClassTraceArgs(): void
    {
        $this->captureTraceArguments();
        $this->setupExceptionReporting();
        $streams = $this->fakeEventsStreams();

        (function ($anonymousArgOne, $anonymousArgTwo) {
            report(new RuntimeException('Whoops!'));
        })(new class {
            //
        }, new class extends stdClass {
            //
        }, new class extends Arr {
            //
        });

        $this->assertCount(1, $streams);
        $streams[0]->assertWrittenJson(function ($json) {
            // The name carries the path of the file the class was
            // declared in, which is not the same shape on every platform.
            $this->assertStringStartsWith("class@anonymous\0", $json['trace'][1]['args']['types'][0]);
            $this->assertStringStartsWith("stdClass@anonymous\0", $json['trace'][1]['args']['types'][1]);
            $this->assertStringStartsWith("Illuminate\Support\Arr@anonymous\0", $json['trace'][1]['args']['types'][2]);

            foreach ($json['trace'][1]['args']['types'] as $type) {
                $this->assertStringContainsString('ExceptionReportingTest.php', $type);
            }

            return true;
        });
    }

    public function testItCapturesTheRawClassNameForAnonymousExceptions(): void
    {
        $this->setupExceptionReporting();
        $streams = $this->fakeEventsStreams();

        report(new class('Whoops!') extends RuntimeException {
            //
        });

        $this->assertCount(1, $streams);
        $streams[0]->assertWrittenJson(function ($json) {
            // The name carries the path of the file the class was
            // declared in, which is not the same shape on every platform.
            $this->assertStringStartsWith("RuntimeException@anonymous\0", $json['class']);
            $this->assertStringContainsString('ExceptionReportingTest.php', $json['class']);

            return true;
        });
    }

    public function testItEncodesTheEventWithoutEscapingOrLosingData(): void
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

    #[DataProvider('stopReturnValueDataProvider')]
    public function testItReturnsWhetherReportingShouldContinue(bool $stopConfig, bool $expected): void
    {
        $this->setupExceptionReporting();
        $this->fakeEventsStreams();

        $reporter = new ExceptionReporter(
            $this->app[Events::class],
            $this->app[BladeMapper::class],
            $this->app->basePath().DIRECTORY_SEPARATOR,
            [
                'stop' => $stopConfig,
                'capture_request_payload' => false,
                'redact_request_payload_fields' => [],
                'redact_headers' => [],
            ],
        );

        $this->assertSame($expected, $reporter(new RuntimeException('Whoops!')));
    }

    public static function stopReturnValueDataProvider(): iterable
    {
        yield 'stop reporting' => [true, false];
        yield 'continue reporting' => [false, true];
    }

    public function testItReturnsNullWhenTheExceptionCannotBeEmitted(): void
    {
        $this->setupExceptionReporting();
        $this->fakeEventsStreams();
        Events::$socketFactory = fn () => false;

        $reporter = new ExceptionReporter(
            $this->app[Events::class],
            $this->app[BladeMapper::class],
            $this->app->basePath().DIRECTORY_SEPARATOR,
            [
                'stop' => false,
                'capture_request_payload' => false,
                'redact_request_payload_fields' => [],
                'redact_headers' => [],
            ],
        );

        $this->assertNull($reporter(new RuntimeException('Whoops!')));
    }

    public function testItDoesNotStopReportingWhenTheEventCannotBeWritten(): void
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

        // The jobs are dispatched from a command, as the queue would be, so
        // that the reporter has a command to resolve from the console input.
        Artisan::command('test-command', function () {
            $_SERVER['argv'] = ['artisan', 'test-command'];

            dispatch(function () {
                report(new RuntimeException('Whoops!'));
            })->onQueue('sync');
            dispatch_sync(function () {
                report(new RuntimeException('Whoops!'));
            });
        });

        $this->artisan('test-command')->assertOk();

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

            $this->assertTrue(Str::isUuid($payload['id']));
            unset($payload['id']);

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

    /**
     * Retrieve the user's identifier captured in the given job payload.
     */
    protected function userIdInJobPayload(string $payload): ?string
    {
        $context = json_decode($payload, associative: true, flags: JSON_THROW_ON_ERROR)['illuminate:log:context'] ?? [];

        return isset($context['hidden']['laravel_cloud_user_id'])
            ? unserialize($context['hidden']['laravel_cloud_user_id'])
            : null;
    }

    /**
     * Create a reporter that has not seen an Artisan command start.
     *
     * Such a reporter falls back to the console input, as it would when an
     * exception is reported before the command has started, e.g. when the
     * given input cannot be bound to the command.
     */
    protected function exceptionReporter(array $config = []): ExceptionReporter
    {
        return new ExceptionReporter(
            $this->app[Events::class],
            $this->app[BladeMapper::class],
            $this->app->basePath().DIRECTORY_SEPARATOR,
            [
                'stop' => true,
                'capture_request_payload' => false,
                'redact_request_payload_fields' => [],
                'redact_headers' => [],
                'redact_command_input_fields' => ['password', 'secret'],
                ...$config,
            ],
        );
    }

    /**
     * Retrieve the class reported for the given scheduled task.
     */
    protected function reportedScheduledTaskClass(callable $define): ?string
    {
        return $this->reportedScheduledTaskContext($define)['class'];
    }

    /**
     * Retrieve the name reported for the given scheduled task.
     */
    protected function reportedScheduledTaskName(callable $define): ?string
    {
        return $this->reportedScheduledTaskContext($define)['name'];
    }

    /**
     * Retrieve the execution context reported for the given scheduled task.
     *
     * The streams are faked once per test, as the events are written to the
     * socket the reporter has already opened, so each task reported within a
     * test is another write to the same stream.
     */
    protected function reportedScheduledTaskContext(callable $define): array
    {
        $streams = $this->reportedScheduledTaskStreams ??= $this->fakeEventsStreams();

        Event::dispatch(new ScheduledTaskStarting($define($this->app->make(Schedule::class))));

        report(new RuntimeException('Whoops!'));

        $context = [];

        $streams[0]->assertWrittenJsonContains([], $this->reportedScheduledTasks++);

        $streams[0]->assertWritten(function (string $stream) use (&$context) {
            $payload = json_decode(explode("\n", $stream)[$this->reportedScheduledTasks - 1], associative: true);

            $context = $payload['execution_context'];

            return true;
        });

        return $context;
    }

    /**
     * Run the given command line, as a console process would.
     *
     * The command is given the console input it was invoked with, rather than
     * the input the "artisan" test helper builds from named parameters, so
     * that the tests may exercise the command line as it would be typed.
     */
    protected function runArtisanCommand(array $argv): int
    {
        $_SERVER['argv'] = $argv;

        return $this->app[\Illuminate\Contracts\Console\Kernel::class]->handle(
            new \Symfony\Component\Console\Input\ArgvInput($argv),
            new \Symfony\Component\Console\Output\NullOutput,
        );
    }

    protected function setupExceptionReporting(array $config = []): void
    {
        $_SERVER['LARAVEL_CLOUD'] = '1';
        $_SERVER['LARAVEL_CLOUD_EXCEPTIONS'] = json_encode($config);
        Cloud::registerEvents($this->app);
        Cloud::registerExceptionReporting($this->app);
    }

    /**
     * Capture the arguments of trace frames.
     *
     * PHP does not retain them when "zend.exception_ignore_args" is enabled,
     * which it is in the production configuration PHP ships with.
     */
    protected function captureTraceArguments(bool $capture = true): void
    {
        $this->iniSettingsToRestore['zend.exception_ignore_args'] ??= ini_get('zend.exception_ignore_args');

        ini_set('zend.exception_ignore_args', $capture ? '0' : '1');
    }

    /**
     * Retrieve the path the given view's compiled file is reported as.
     */
    protected function reportedCompiledViewPath(string $view): string
    {
        return str_replace(
            [$this->app->basePath().DIRECTORY_SEPARATOR, '\\'],
            ['', '/'],
            Blade::getCompiledPath($view),
        );
    }

    /**
     * Retrieve the command line the reporter is able to report.
     *
     * The command line is rebuilt from the raw console input, which is only
     * available from symfony/console 8.
     */
    protected function reportedCommandLine(string $command): ?string
    {
        return interface_exists(RawInputInterface::class) ? $command : null;
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

class ExceptionHandlerWithoutReportable implements ExceptionHandler
{
    public function report(Throwable $e)
    {
        //
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

class JobThatThrowsWhenInteractedWith extends QueueJob implements JobContract
{
    public function attempts()
    {
        return 1;
    }

    public function getJobId()
    {
        return 'job-id';
    }

    public function getRawBody()
    {
        return json_encode([
            'uuid' => '9d3d0e9a-7e3f-4a6d-9a1f-2c0a1f7e8b21',
            'job' => 'MissingJobClass@handle',
            'data' => [],
        ]);
    }

    public function resolveName()
    {
        throw new RuntimeException('Unable to interact with the job.');
    }
}

class ExceptionThrownOnWindows extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Whoops!');

        $this->file = 'D:\\a\\framework\\framework\\tests\\Foundation\\Cloud\\Whoops.php';
        $this->line = 1;
    }
}

class HeaderBagThatThrows extends HeaderBag
{
    public function __clone(): void
    {
        throw new RuntimeException('Unable to retrieve the headers.');
    }
}

class FileBagThatThrows extends FileBag
{
    public function all(?string $key = null): array
    {
        throw new RuntimeException('Unable to retrieve the files.');
    }
}

class RouteThatThrows extends \Illuminate\Routing\Route
{
    public function getName()
    {
        throw new RuntimeException('Unable to retrieve the route.');
    }
}

class ContextRepositoryThatThrows extends ContextRepository
{
    public function __construct()
    {
        parent::__construct(new Dispatcher);
    }

    protected bool $thrown = false;

    public function all()
    {
        // Only throw for the reporter's call. The logger also retrieves the
        // context while handling the exception, and it should not be
        // impacted by this test's simulated failure.
        if (! $this->thrown) {
            $this->thrown = true;

            throw new RuntimeException('Unable to retrieve context.');
        }

        return parent::all();
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

class GuardThatCountsUserRetrievals implements \Illuminate\Contracts\Auth\Guard
{
    use \Illuminate\Auth\GuardHelpers;

    public int $calls = 0;

    public function user()
    {
        $this->calls++;

        return $this->user ??= new GenericUser(['id' => 'abc123']);
    }

    public function validate(array $credentials = [])
    {
        return true;
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
    public function __construct(protected array $context, string $message = '', int $code = 0, ?Throwable $previous = null)
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

class ScheduledTaskCallback
{
    public function __invoke()
    {
        //
    }

    public function handle()
    {
        //
    }
}

class ExceptionReportingSensitiveInputTestCommand extends \Illuminate\Console\Command
{
    protected $signature = 'test-sensitive-command
        {user}
        {password}
        {files?*}
        {--secret=}
        {--keep=}
        {--token=*}
        {--p|proxy=}
        {--force}';

    public function handle()
    {
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
