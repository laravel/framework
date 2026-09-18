<?php

namespace Illuminate\Foundation\Cloud;

use Illuminate\Contracts\Queue\Job;
use Illuminate\Foundation\Bootstrap\HandleExceptions;
use Illuminate\Foundation\Exceptions\Renderer\Mappers\BladeMapper;
use Illuminate\Routing\Route;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Request;
use Illuminate\View\ViewException;
use Ramsey\Uuid\Uuid;
use Spatie\LaravelIgnition\Exceptions\ViewException as IgnitionViewException;
use Symfony\Component\ErrorHandler\Error\FatalError;
use Throwable;

class ExceptionReporter
{
    /**
     * The trace ID for the current artisan command execution.
     */
    protected ?string $artisanCommandTraceId = null;

    /**
     * Indicates the currently reporting exception is a view exception.
     */
    protected bool $reportingViewException = false;

    /**
     * The currently executing job on the queue.
     */
    protected ?Job $currentlyProcessingJob = null;

    /**
     * The currently executing job attempt ID.
     */
    protected ?string $currentlyProcessingJobAttemptId = null;

    /**
     * Proactively captured execution context.
     *
     * @var array<string, mixed>
     */
    protected array $executionContext = [];

    /**
     * @var array<string, string>
     */
    protected array $normalizedQueues = [];

    /**
     * The cached queue configuration.
     */
    protected ?array $connectionConfig = null;

    /**
     * Create a new Exception Reporter instance.
     *
     * @param  array{
     *    stop: bool,
     *    capture_request_payload: bool,
     *    redact_request_payload_fields: array,
     * }  $config
     */
    public function __construct(
        protected Events $events,
        protected BladeMapper $bladeMapper,
        protected string $basePath,
        protected array $config,
    ) {
        //
    }

    /**
     * Report the given exception.
     */
    public function __invoke(Throwable $e): ?false
    {
        // TODO prepare for 14.x passing context here.
        // TODO reserve memory and free memory fatal exceptions?

        $previousReportingViewException = $this->reportingViewException;
        $this->reportingViewException = false;

        try {
            $e = $this->unwrapViewException($e);

            $emitted = $this->events->emit($this->payload($e));

            if ($emitted === false) {
                return null;
            }

            return ! $this->config['stop'];
        } catch (Throwable $e) {
            return null;
        } finally {
            $this->reportingViewException = $previousReportingViewException;
        }
    }

    /**
     * Unwrap view exceptions and remember the original exception.
     */
    protected function unwrapViewException(Throwable $e): Throwable
    {
        while ($this->isViewException($e) && $e->getPrevious()) {
            $this->reportingViewException = true;

            $e = $e->getPrevious();
        }

        return $e;
    }

    /**
     * Create the exception payload.
     */
    protected function payload(Throwable $e): array
    {
        return [
            '_cloud_event' => 'exception',
            'timestamp' => $this->timestamp(),
            'exception_context' => $this->exceptionContext($e),
            'laravel_context' => $this->laravelContext(),
            ...$this->executionDetails($e), // up to here
            'user_id' => $this->userId(),
            'handled' => $this->handled($e),
            ...$this->parseException($e),
            'previous' => $this->previous($e),
        ];
    }

    /**
     * Retrieve the current timestamp.
     */
    protected function timestamp(): string
    {
        return Date::now('UTC')->toDateTimeString('microsecond');
    }

    /**
     * Retrieve the context for the given exception.
     */
    protected function exceptionContext(Throwable $e): object
    {
        try {
            return (object) Arr::except(Exceptions::contextForException($e), 'exception');
        } catch (Throwable) {
            return (object) [];
        }
    }

    /**
     * Retrieve the current Laravel Context.
     */
    protected function laravelContext(): object
    {
        return (object) Context::all();
    }

    /**
     * Retrieve the execution context.
     */
    protected function executionDetails(Throwable $e): array
    {
        return match (true) {
            $this->isProcessingJob() => $this->jobExecutionDetails($e),
            App::runningInConsole() => $this->consoleExecutionDetails($e),
            default => $this->requestExecutionDetails($e),
        };
    }

    /**
     * Retrieve the currently processing job's execution context.
     */
    protected function jobExecutionDetails(Throwable $e): array
    {
        return [
            'trace_id' => 'TODO',
            'execution_type' => 'job',
            'execution_context' => [
                ...$this->executionContext,
                'attempt_id' => $this->currentlyProcessingJobAttemptId(),
                'uuid' => $this->currentlyProcessingJob->uuid(),
                'name' => $this->currentlyProcessingJob->resolveName(),
                'connection' => $this->currentlyProcessingJob->getConnectionName(),
                'queue' => $this->normalizedQueue(),
            ],
        ];
    }

    /**
     * The currently processing job attempt ID.
     */
    protected function currentlyProcessingJobAttemptId(): string
    {
        return $this->currentlyProcessingJobAttemptId ??= (string) Uuid::uuid4();
    }

    /**
     * Retrieve the console execution context.
     */
    protected function consoleExecutionDetails(Throwable $e): array
    {
        return [
            'trace_id' => $this->consoleTraceId(),
            'execution_type' => 'command',
            'execution_context' => [
                'timestamp' => $this->laravelStartedAtTimestamp(),
                'name' => $name = $_SERVER['argv'][1] ?? null,
                'class' => $this->consoleCommandClass($name),
                'command' => implode(' ', array_slice($_SERVER['argv'] ?? [], 1)),
            ],
        ];
    }

    /**
     * Retrieve the class name of the currently running Artisan command.
     */
    protected function consoleCommandClass(?string $name): ?string
    {
        if ($name === null) {
            return null;
        }

        try {
            $command = Artisan::findCommand($name);

            return $command !== null
                ? $command::class
                : null;
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Retrieve the request execution context.
     */
    protected function requestExecutionDetails(Throwable $e): array
    {
        // TODO Octane
        // TODO Livewire
        return [
            'trace_id' => Request::header('Cloud-Request-ID'),
            'execution_type' => 'request',
            'execution_context' => [
                'timestamp' => $this->laravelStartedAtTimestamp(),
                // TODO redact headers
                'headers' => Request::header(),
                'method' => Request::method(),
                'url' => Request::fullUrl(),
                'ip' => Request::ip(),
                'route' => $this->requestRouteExecutionDetails(),
                'payload' => $this->requestPayload($e),
                'files' => $this->requestFiles($e),
            ],
        ];
    }

    /**
     * Retrieve the request payload.
     */
    protected function requestPayload(Throwable $e): ?array
    {
        if (! $this->config['capture_request_payload'] || $e instanceof FatalError) {
            return null;
        }

        try {
            return $this->redactRequestPayload(request()->request->all());
        } catch (Throwable $e) {
            return [
                '_laravel_cloud_error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Retrieve the parsed uploaded files.
     */
    protected function requestFiles(Throwable $e): ?array
    {
        if (! $this->config['capture_request_payload'] || $e instanceof FatalError) {
            return null;
        }

        try {
            return $this->parseRequestFiles(Request::allFiles());
        } catch (Throwable $e) {
            return [
                '_laravel_cloud_error' => $e->getMessage(),
            ];
        }
    }

    protected function redactRequestPayload(array $payload): array
    {
        return Arr::map($payload, function ($value, $key) {
            if (is_array($value)) {
                return $this->redactRequestPayload($value);
            }

            return $this->shouldRedactRequestPayloadField($key, $value)
                ? '['.strlen((string) $value).' bytes redacted]'
                : $value;
        });
    }

    protected function parseRequestFiles(array $files): array
    {
        return array_map(function ($file) {
            if (is_array($file)) {
                return $this->parseRequestFiles($file);
            }

            return [
                'originalName' => $file->getClientOriginalName(),
                'size' => $file->getSize(),
                'error' => $file->getError(),
            ];
        }, $files);
    }

    protected function shouldRedactRequestPayloadField(string $field, mixed $value): bool
    {
        return in_array($field, $this->config['redact_request_payload_fields']) && is_string($value); // TODO what about sensitive numbers?
    }

    /**
     * Retrieve the route specific request execution context.
     */
    protected function requestRouteExecutionDetails(): ?array
    {
        $route = Request::route();

        if (! ($route instanceof Route)) {
            return null;
        }

        return [
            'name' => $route->getName(),
            'methods' => collect($route->methods())
                ->sort()
                ->values()
                ->all(),
            'domain' => $route->domain(),
            'path' => match ($route->uri()) {
                '/' => '/',
                default => "/{$route->uri()}",
            },
            'action' => $route->getActionName(),
        ];
    }

    /**
     * Retrieve the current user ID.
     */
    protected function userId(): ?string
    {
        // TODO ensure we don't hit recursion.
        // TODO jobs
        try {
            $identifier = Auth::user()?->getAuthIdentifier();

            return $identifier === null ? null : (string) $identifier;
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Retrieve the console execution trace ID.
     */
    protected function consoleTraceId(): string
    {
        // TODO jobs should inherit this from the queue worker
        // TODO scheduled tasks
        // TODO tied to command ID in Cloud?
        return $this->artisanCommandTraceId ??= (string) Uuid::uuid4();
    }

    /**
     * Determine if the exception was handled.
     */
    protected function handled(Throwable $e): bool
    {
        if ($e instanceof FatalError) {
            return false;
        }

        foreach (debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, limit: 20) as $frame) { // PR to framework
            if ($frame['function'] === 'report' && ! isset($frame['type'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Parse the given exception.
     */
    protected function parseException(Throwable $e): array
    {
        return [
            'class' => $e::class,
            'code' => (string) $e->getCode(),
            'message' => $e->getMessage(),
            'trace' => $this->trace($e),
        ];
    }

    /**
     * Parse the previous exceptions.
     */
    protected function previous(Throwable $e): array
    {
        $previous = [];

        while ($e = $e->getPrevious()) {
            $previous[] = $this->parseException($e);
        }

        return $previous;
    }

    /**
     * Parse the stack trace for the given exception.
     */
    protected function trace(Throwable $e): array
    {
        // Insert the exception location as the first frame...
        $trace = [
            $this->normalizeFrameFileAndLine([
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]),
        ];

        foreach ($e->getTrace() as $i => $frame) {
            // Skip internal frames when a PHP error has been converted to an ErrorException
            if ($i < 2 && ($frame['class'] ?? null) === HandleExceptions::class) {
                continue;
            }

            $frame = Arr::only($frame, ['class', 'type', 'function', 'file', 'line', 'args']);

            $frame = $this->normalizeFrameFileAndLine($frame);

            // TODO: add setting to capture values. Max depth. Only iterate once. Sensitive arguments / property values?
            if (is_array($args = $frame['args'] ?? null)) {
                // Should this be 'args' => [[TYPE, VALUE], [TYPE, VALUE]
                // Should this be 'args' => [[TYPE], [TYPE]
                $frame['args'] = [
                    'types' => array_map($this->argType(...), $args, array_keys($args)),
                ];
            }

            $trace[] = $frame;
        }

        return $trace;
    }

    /**
     * Determine the type information of the given argument.
     */
    protected function argType(mixed $argument, string|int $name): string|array
    {
        $type = match (gettype($argument)) {
            'NULL' => 'null',
            'string' => 'string',
            'boolean' => 'bool',
            'integer' => 'int',
            'double' => 'float',
            'array' => 'array',
            'object' => $argument::class,
            'resource' => 'resource',
            'resource (closed)' => 'resource(closed)',
            default => '[unknown]',
        };

        return is_int($name) ? $type : [$name, $type];
    }

    /**
     * Normalize the file and line number.
     */
    protected function normalizeFrameFileAndLine(array $frame): array
    {
        if (! is_string($frame['file'] ?? null)) {
            return $frame;
        }

        if ($this->reportingViewException) {
            $frame = $this->mapCompiledViewFrame($frame);

            if ($frame['compiled_view'] ?? null) {
                $frame['compiled_view'] = $this->normalizeBasePath($frame['compiled_view']);
            }
        }

        $frame['file'] = $this->normalizeBasePath($frame['file']);

        return $frame;
    }

    /**
     * Map compiled views to their original view file paths.
     */
    protected function mapCompiledViewFrame(array $frame): array
    {
        $normalizedView = $this->bladeMapper->findCompiledView($frame['file']);

        if (! $normalizedView) {
            return $frame;
        }

        $frame['compiled_view'] = $frame['file'];
        $frame['file'] = $normalizedView;
        $frame['line'] = $this->bladeMapper->detectLineNumber($normalizedView, $frame['line']);

        return $frame;
    }

    /**
     * Normalize the file path's base path.
     */
    protected function normalizeBasePath(string $path): string
    {
        if (! str_starts_with($path, $this->basePath)) {
            return $path;
        }

        return substr($path, strlen($this->basePath));
    }

    /**
     * Determine if the given exception is a view exception.
     */
    protected function isViewException(Throwable $e): bool
    {
        return $e instanceof ViewException || $e instanceof IgnitionViewException;
    }

    /**
     * Retrieve the time Laravel started.
     */
    protected function laravelStartedAtTimestamp(): string
    {
        // TODO Octane
        $microtime = defined('LARAVEL_START')
            ? LARAVEL_START
            : $_SERVER['REQUEST_TIME_FLOAT'];

        return Date::createFromTimestampUTC($microtime)->toDateTimeString('microsecond');
    }

    /**
     * Determine if a queue worker is running.
     */
    protected function isProcessingJob(): bool
    {
        return $this->currentlyProcessingJob !== null;
    }

    /**
     * Prepare to process the given job.
     */
    public function prepareForJob(Job $job): void
    {
        $this->currentlyProcessingJob = $job;

        $this->executionContext = [
            'timestamp' => $this->timestamp(),
            // Beanstalkd throws an exception when attempting to retrieve the job
            // after it has been processed. Instead of capturing this value when
            // an exception occurs, we need to proactively capture the value
            // before the job has been processed.
            'attempt' => $job->attempts(),
        ];
    }

    /**
     * Flush currently processing job context.
     */
    public function flushJobContext(): void
    {
        $this->executionContext = [];
        $this->currentlyProcessingJob = null;
        $this->currentlyProcessingJobAttemptId = null;
    }

    /**
     * Normalize the given queue name.
     */
    protected function normalizedQueue(): string
    {
        [$connection, $queue] = [
            $this->currentlyProcessingJob->getConnectionName(),
            $this->currentlyProcessingJob->getQueue(),
        ];

        $key = "{$connection}:{$queue}";

        if (isset($this->normalizedQueues[$key])) {
            return $this->normalizedQueues[$key];
        }

        // trim .fifo

        $this->connectionConfig ??= Config::get("queue.connections.{$connection}") ?? [];

        if (($this->connectionConfig['driver'] ?? null) === 'cloud') {
            $this->connectionConfig = $this->connectionConfig['connection'];
        }

        if (($this->connectionConfig['driver'] ?? null) !== 'sqs') {
            return $this->normalizedQueues[$key] = $queue;
        }

        if ($this->connectionConfig['prefix'] ?? null) {
            $prefix = preg_quote($this->connectionConfig['prefix'], '#');

            $queue = preg_replace("#^{$prefix}/#", '', $queue) ?? $queue;
        }

        if ($this->connectionConfig['suffix'] ?? null) {
            $suffix = preg_quote($this->connectionConfig['suffix'], '#');

            $queue = preg_replace("#{$suffix}$#", '', $queue) ?? $queue;
        }

        return $this->normalizedQueues[$key] = $queue;
    }
}
