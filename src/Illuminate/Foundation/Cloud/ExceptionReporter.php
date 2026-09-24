<?php

namespace Illuminate\Foundation\Cloud;

use Closure;
use DateTimeZone;
use Illuminate\Console\Application as ConsoleApplication;
use Illuminate\Console\Scheduling\CallbackEvent as CallbackTask;
use Illuminate\Console\Scheduling\Event as ScheduledTask;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Foundation\Bootstrap\HandleExceptions;
use Illuminate\Foundation\Exceptions\Renderer\Mappers\BladeMapper;
use Illuminate\Log\Context\Repository as ContextRepository;
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
use ReflectionClass;
use ReflectionFunction;
use RuntimeException;
use Spatie\LaravelIgnition\Exceptions\ViewException as IgnitionViewException;
use Symfony\Component\Console\Exception\CommandNotFoundException;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\Input as ConsoleInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\ErrorHandler\Error\FatalError;
use Symfony\Component\HttpFoundation\HeaderBag;
use Throwable;
use WeakMap;

// TODO Octane
// TODO Livewire
// TODO prepare for 14.x passing context here.
// TODO reserve memory and free memory fatal exceptions?
class ExceptionReporter
{
    /**
     * Indicates the currently reporting exception is a view exception.
     */
    protected bool $reportingViewException = false;

    /**
     * The captured identifiers, keyed by exception.
     *
     * @var \WeakMap<\Throwable, string>
     */
    protected WeakMap $exceptionIds;

    /**
     * The name of the currently running Artisan command.
     */
    protected ?string $currentlyRunningCommandName = null;

    /**
     * The trace ID for the current artisan command execution.
     */
    protected ?string $artisanCommandTraceId = null;

    /**
     * The console input of the currently running Artisan command.
     */
    protected ?ConsoleInput $currentConsoleInput = null;

    /**
     * The console input built from the process arguments. Fallback when console input is not yet set.
     */
    protected ?ArgvInput $currentFallbackArgvInput = null;

    /**
     * The currently running scheduled task.
     */
    protected ?ScheduledTask $currentlyRunningScheduledTask = null;

    /**
     * Indicates the currently running scheduled task has finished with its failure yet to be reported.
     */
    protected bool $scheduledTaskAwaitingFailureReport = false;

    /**
     * The currently executing job on the queue.
     */
    protected ?Job $currentlyProcessingJob = null;

    /**
     * The currently executing job attempt ID.
     */
    protected ?string $currentlyProcessingJobAttemptId = null;

    /**
     * The cached normalized queue names, keyed by "connection:queue".
     *
     * @var array<string, string>
     */
    protected array $normalizedQueues = [];

    /**
     * The cached queue configuration.
     *
     * @var array<string, mixed>|null
     */
    protected ?array $connectionConfig = null;

    /**
     * The user that logged out.
     */
    protected ?Authenticatable $rememberedUser = null;

    /**
     * Proactively captured execution context, keyed by execution type.
     *
     * @var array{
     *    job: array<string, mixed>,
     *    scheduled_task: array<string, mixed>,
     * }
     */
    protected array $executionContext = [
        'job' => [],
        'scheduled_task' => [],
    ];

    /**
     * Create a new Exception Reporter instance.
     *
     * @param  array{
     *    stop: bool,
     *    capture_request_payload: bool,
     *    redact_request_payload_fields: list<string>,
     *    redact_headers: list<string>,
     *    redact_command_input_fields: list<string>,
     * }  $config
     */
    public function __construct(
        protected Events $events,
        protected BladeMapper $bladeMapper,
        protected string $basePath,
        protected array $config,
    ) {
        $this->exceptionIds = new WeakMap;
    }

    /**
     * Report the given exception.
     */
    public function __invoke(Throwable $e): ?bool
    {
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

            if ($this->scheduledTaskAwaitingFailureReport) {
                $this->flushScheduledTaskContext();
            }
        }
    }

    /**
     * Retrieve the identifier for the given exception.
     */
    public function exceptionId(Throwable $e): string
    {
        return $this->exceptionIds[$e] ??= (string) Uuid::uuid4();
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
     * Determine if the given exception is a view exception.
     */
    protected function isViewException(Throwable $e): bool
    {
        return $e instanceof ViewException || $e instanceof IgnitionViewException;
    }

    /**
     * Create the exception payload.
     *
     * @return array<string, mixed>
     */
    protected function payload(Throwable $e): array
    {
        return [
            '_cloud_event' => 'exception',
            'id' => $this->exceptionId($e),
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
        } catch (Throwable $e) {
            return literal(
                _laravel_cloud_error: $e->getMessage(),
            );
        }
    }

    /**
     * Retrieve the current Laravel Context.
     */
    protected function laravelContext(): object
    {
        try {
            return (object) Context::all();
        } catch (Throwable $e) {
            return literal(
                _laravel_cloud_error: $e->getMessage(),
            );
        }
    }

    /**
     * Retrieve the execution context.
     *
     * @return array<string, mixed>
     */
    protected function executionDetails(Throwable $e): array
    {
        try {
            return match (true) {
                $this->isProcessingJob() => $this->jobExecutionDetails($e),
                $this->isRunningScheduledTask() => $this->scheduledTaskExecutionDetails($e),
                App::runningInConsole() => $this->consoleCommandExecutionDetails($e),
                default => $this->requestExecutionDetails($e),
            };
        } catch (Throwable $e) {
            return [
                '_laravel_cloud_error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Retrieve the request execution context.
     *
     * @return array<string, mixed>
     */
    protected function requestExecutionDetails(Throwable $e): array
    {
        return [
            'trace_id' => Request::header('Cloud-Request-ID'),
            'execution_type' => 'request',
            'execution_context' => [
                'timestamp' => $this->laravelStartedAtTimestamp(),
                'headers' => $this->requestHeaders(),
                'method' => Request::method(),
                'url' => $this->requestUrl(), // TODO redact query strings parameters
                'ip' => Request::ip(),
                'route' => $this->requestRouteExecutionDetails(),
                'payload' => $this->requestPayload($e),
                'files' => $this->requestFiles($e),
            ],
        ];
    }

    /**
     * Retrieve the request headers.
     *
     * @return array<string, list<string|null>>
     */
    protected function requestHeaders(): array
    {
        try {
            $headers = clone Request::instance()->headers;

            $this->removeSyntheticAuthorizationHeaders($headers);
            $this->redactHeaders($headers);

            return $headers->all();
        } catch (Throwable $e) {
            return [
                '_laravel_cloud_error' => [$e->getMessage()],
            ];
        }
    }

    /**
     * Remove the headers PHP derives from the Authorization header.
     */
    protected function removeSyntheticAuthorizationHeaders(HeaderBag $headers): void
    {
        // The Authorization header already contains these values and they are
        // not headers the client actually sent, so we remove them to avoid
        // leaking credentials that have not been redacted.
        $headers->remove('php-auth-user');
        $headers->remove('php-auth-pw');
        $headers->remove('php-auth-digest');
    }

    /**
     * Redact the configured sensitive headers.
     */
    protected function redactHeaders(HeaderBag $headers): void
    {
        foreach ($this->config['redact_headers'] as $key) {
            if (! $headers->has($key)) {
                continue;
            }

            $headers->set($key, array_map(fn ($value) => match (strtolower($key)) {
                'authorization', 'proxy-authorization' => $this->redactAuthorizationHeaderValue((string) $value),
                'cookie' => $this->redactCookieHeaderValue((string) $value),
                default => $this->redactValue((string) $value),
            }, $headers->all($key)));
        }
    }

    /**
     * Redact the given authorization header value, retaining the scheme.
     */
    protected function redactAuthorizationHeaderValue(string $value): string
    {
        if (! str_contains($value, ' ')) {
            return $this->redactValue($value);
        }

        [$scheme, $remainder] = explode(' ', $value, 2);

        if (in_array(strtolower($scheme), [
            'basic',
            'bearer',
            'concealed',
            'digest',
            'dpop',
            'gnap',
            'hoba',
            'mutual',
            'negotiate',
            'oauth',
            'privatetoken',
            'scram-sha-1',
            'scram-sha-256',
            'vapid',
        ])) {
            return $scheme.' '.$this->redactValue($remainder);
        }

        return $this->redactValue($value);
    }

    /**
     * Redact the given cookie header value, retaining the cookie names.
     */
    protected function redactCookieHeaderValue(string $value): string
    {
        try {
            return implode('; ', array_map(function ($cookie) {
                if (! str_contains($cookie, '=')) {
                    throw new RuntimeException('Invalid cookie format.');
                }

                [$name, $value] = explode('=', $cookie, 2);

                return trim($name).'='.$this->redactValue($value);
            }, explode(';', $value)));
        } catch (Throwable) {
            return $this->redactValue($value);
        }
    }

    /**
     * Retrieve the requested URL.
     */
    protected function requestUrl(): string
    {
        $request = Request::instance();

        $query = (string) $request->server->get('QUERY_STRING');

        return $request->getSchemeAndHttpHost()
            .$request->getBaseUrl()
            .$request->getPathInfo()
            .($query === '' ? '' : "?{$query}");
    }

    /**
     * Retrieve the route specific request execution context.
     *
     * @return array<string, mixed>|null
     */
    protected function requestRouteExecutionDetails(): ?array
    {
        try {
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
        } catch (Throwable $e) {
            return [
                '_laravel_cloud_error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Retrieve the request payload.
     *
     * @return array<array-key, mixed>|null
     */
    protected function requestPayload(Throwable $e): ?array
    {
        if (! $this->config['capture_request_payload'] || $e instanceof FatalError) {
            return null;
        }

        try {
            return $this->redactRequestPayload(Request::instance()->request->all());
        } catch (Throwable $e) {
            return [
                '_laravel_cloud_error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Redact the configured sensitive fields in the given payload.
     *
     * @param  array<array-key, mixed>  $payload
     * @return array<array-key, mixed>
     */
    protected function redactRequestPayload(array $payload): array
    {
        return Arr::map($payload, function ($value, $key) {
            if (is_array($value)) {
                return $this->redactRequestPayload($value);
            }

            return $this->shouldRedactRequestPayloadField($key, $value)
                ? $this->redactValue($value === false ? '0' : (string) $value)
                : $value;
        });
    }

    /**
     * Determine if the given payload field should be redacted.
     */
    protected function shouldRedactRequestPayloadField(string $field, mixed $value): bool
    {
        return in_array($field, $this->config['redact_request_payload_fields'])
            && is_scalar($value);
    }

    /**
     * Retrieve the parsed uploaded files.
     *
     * @return array<array-key, mixed>|null
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

    /**
     * Parse the given uploaded files into their reportable details.
     *
     * Values are uploaded files, or nested arrays of them.
     *
     * @param  array<array-key, mixed>  $files
     * @return array<array-key, mixed>
     */
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

    /**
     * Prepare to process the given command.
     */
    public function prepareForCommand(string $name, InputInterface $input): void
    {
        $this->currentlyRunningCommandName = $name !== ''
            ? $name
            : null;

        $this->currentConsoleInput = $input instanceof ConsoleInput ? $input : null;
    }

    /**
     * Retrieve the console execution context.
     *
     * @return array<string, mixed>
     */
    protected function consoleCommandExecutionDetails(Throwable $e): array
    {
        return [
            'trace_id' => $this->consoleCommandTraceId(),
            'execution_type' => 'command',
            'execution_context' => [
                'timestamp' => $this->laravelStartedAtTimestamp(),
                'name' => $this->consoleCommandName(),
                'class' => $this->consoleCommandClass(),
                'command' => $this->consoleCommandLine(),
            ],
        ];
    }

    /**
     * Retrieve the console command execution trace ID.
     */
    protected function consoleCommandTraceId(): string
    {
        // TODO jobs should inherit this from the queue worker
        if (isset($_SERVER['LARAVEL_CLOUD_COMMAND_UUID'])) {
            return $this->artisanCommandTraceId ??= str($_SERVER['LARAVEL_CLOUD_COMMAND_UUID'])->after('comm-')->toString();
        }

        return $this->artisanCommandTraceId ??= (string) Uuid::uuid4();
    }

    /**
     * Retrieve the name of the currently running Artisan command.
     */
    protected function consoleCommandName(): ?string
    {
        return $this->currentlyRunningCommandName ?? $this->currentFallbackArgvInput()->getFirstArgument();
    }

    /**
     * Retrieve the class name of the currently running Artisan command.
     */
    protected function consoleCommandClass(): ?string
    {
        try {
            $name = $this->consoleCommandName();

            if ($name === null) {
                return null;
            }

            $command = Artisan::findCommand($name);

            return $command === null
                ? null
                : $command::class;
        } catch (Throwable $e) {
            return '_laravel_cloud_error: '.$e->getMessage();
        }
    }

    /**
     * Retrieve the redacted command line.
     */
    protected function consoleCommandLine(): ?string
    {
        try {
            // If we are unable to retrieve the console input, we are unable to confidently
            // redact input values, so we return null to avoid leaking sensitive information.
            try {
                $this->currentConsoleInput();
            } catch (CommandNotFoundException $e) {
                return null;
            }

            $tokens = Arr::wrap($this->currentConsoleInput()->getFirstArgument());

            foreach ($this->currentConsoleInput()->getRawArguments() as $name => $value) {
                // Skip the initial argument, which is the command name. We have already captured that above
                // and do not want to apply any special handling to it.
                if ($name === 'command' || is_int($name)) {
                    continue;
                }

                $transformer = $this->consoleArgumentTransformer($name);

                $tokens = [
                    ...$tokens,
                    ...$this->applyTransformationToConsoleInputValue($value, $transformer),
                ];
            }

            foreach ($this->currentConsoleInput()->getRawOptions() as $name => $value) {
                $transformer = $this->consoleOptionTransformer($name);

                $tokens = [
                    ...$tokens,
                    ...$this->applyTransformationToConsoleInputValue($value, $transformer),
                ];
            }

            return implode(' ', $tokens);
        } catch (Throwable $e) {
            return '_laravel_cloud_error: '.$e->getMessage();
        }
    }

    /**
     * Retrieve a transformer for the given console argument.
     */
    protected function consoleArgumentTransformer(string $name): callable
    {
        return $this->shouldRedactConsoleInputValue($name)
            ? $this->redactValue(...)
            : $this->currentConsoleInput()->escapeToken(...);
    }

    /**
     * Retrieve a transformer for the given console option.
     */
    protected function consoleOptionTransformer(string $name): callable
    {
        return fn ($value) => match (true) {
            is_bool($value) => $value ? "--{$name}" : "--no-{$name}",
            is_null($value) => "--{$name}",
            $this->shouldRedactConsoleInputValue($name) => "--{$name}={$this->redactValue($value)}",
            default => "--{$name}={$this->currentConsoleInput()->escapeToken($value)}",
        };
    }

    /**
     * Apply the given transformation to each of the given input's values.
     *
     * @param  callable(string): string  $transformer
     * @return list<string>
     */
    protected function applyTransformationToConsoleInputValue(null|bool|string|array $value, callable $transformer): array
    {
        return array_map($transformer, $value === null ? [null] : Arr::wrap($value));
    }

    /**
     * Determine if the given command argument or option should be redacted.
     */
    protected function shouldRedactConsoleInputValue(string $name): bool
    {
        return in_array($name, $this->config['redact_command_input_fields']);
    }

    /**
     * Retrieve the console input of the currently running Artisan command.
     */
    protected function currentConsoleInput(): ConsoleInput
    {
        if ($this->currentConsoleInput !== null) {
            return $this->currentConsoleInput;
        }

        $name = $this->currentFallbackArgvInput()->getFirstArgument();

        $command = $name === null
            ? null
            : Artisan::findCommand($name);

        if ($command === null) {
            throw new CommandNotFoundException("The command [{$name}] does not exist.");
        }

        $command->mergeApplicationDefinition();

        $this->currentFallbackArgvInput()->bind($command->getDefinition());

        return $this->currentConsoleInput = $this->currentFallbackArgvInput();
    }

    /**
     * Retrieve the fallback console input built from the process arguments.
     */
    protected function currentFallbackArgvInput(): ArgvInput
    {
        return $this->currentFallbackArgvInput ??= new ArgvInput;
    }

    /**
     * Prepare to run the given scheduled task.
     */
    public function prepareForScheduledTask(ScheduledTask $task): void
    {
        $this->currentlyRunningScheduledTask = $task;
        $this->scheduledTaskAwaitingFailureReport = false;

        $this->executionContext['scheduled_task'] = [
            'timestamp' => $this->timestamp(),
        ];
    }

    /**
     * Handle the given scheduled task finishing.
     */
    public function finishScheduledTask(ScheduledTask $task): void
    {
        // The order of failed scheduled task events means when we fail,
        // we haven't yet received the exception to report. We'll set the
        // reporter into a waiting state, so that when the exception does
        // arrive, we also flush the scheduled task state.
        if ($task->command !== null && $task->exitCode !== 0 && ! $task->runInBackground) {
            $this->scheduledTaskAwaitingFailureReport = true;

            return;
        }

        $this->flushScheduledTaskContext();
    }

    /**
     * Flush the currently running scheduled task context.
     */
    public function flushScheduledTaskContext(): void
    {
        $this->currentlyRunningScheduledTask = null;
        $this->scheduledTaskAwaitingFailureReport = false;
        $this->executionContext['scheduled_task'] = [];
    }

    /**
     * Determine if a scheduled task is running.
     */
    protected function isRunningScheduledTask(): bool
    {
        return $this->currentlyRunningScheduledTask !== null;
    }

    /**
     * Retrieve the currently running scheduled task's execution context.
     *
     * @return array<string, mixed>
     */
    protected function scheduledTaskExecutionDetails(Throwable $e): array
    {
        return [
            'trace_id' => $this->consoleCommandTraceId(),
            'execution_type' => 'scheduled_task',
            'execution_context' => [
                ...$this->executionContext['scheduled_task'],
                'name' => $this->scheduledTaskName(),
                'class' => $this->scheduledTaskClass(),
                'cron' => $this->currentlyRunningScheduledTask->expression,
                'timezone' => $this->scheduledTaskTimezone(),
                'repeat_seconds' => $this->currentlyRunningScheduledTask->repeatSeconds,
                'without_overlapping' => $this->currentlyRunningScheduledTask->withoutOverlapping,
                'on_one_server' => $this->currentlyRunningScheduledTask->onOneServer,
                'run_in_background' => $this->currentlyRunningScheduledTask->runInBackground,
                'even_in_maintenance_mode' => $this->currentlyRunningScheduledTask->evenInMaintenanceMode,
            ],
        ];
    }

    /**
     * Retrieve the name of the currently running scheduled task.
     */
    protected function scheduledTaskName(): string
    {
        if ($this->currentlyRunningScheduledTask instanceof CallbackTask) {
            return $this->scheduledCallbackTaskName();
        }

        return str_replace([
            ConsoleApplication::phpBinary(),
            ConsoleApplication::artisanBinary(),
        ], [
            'php',
            preg_replace("#['\"]#", '', ConsoleApplication::artisanBinary()),
        ], $this->currentlyRunningScheduledTask->command ?? '');
    }

    /**
     * Retrieve the name of the currently running scheduled callback task.
     */
    protected function scheduledCallbackTaskName(): string
    {
        $name = $this->currentlyRunningScheduledTask->getSummaryForDisplay();

        if (! in_array($name, ['Closure', 'Callback'])) {
            return $name;
        }

        return match (true) {
            $this->scheduledTaskCallback() instanceof Closure => $this->scheduledClosureTaskName($this->scheduledTaskCallback()),
            is_string($this->scheduledTaskCallback()) => $this->scheduledTaskCallback(),
            is_array($this->scheduledTaskCallback()) => is_string($this->scheduledTaskCallback()[0])
                ? $this->scheduledTaskCallback()[0]
                : $this->scheduledTaskCallback()[0]::class,
            default => $this->scheduledTaskCallback()::class,
        };
    }

    /**
     * Retrieve the name of the given scheduled closure task.
     */
    protected function scheduledClosureTaskName(Closure $callback): string
    {
        $function = new ReflectionFunction($callback);

        return sprintf(
            'Closure at: %s:%s',
            $this->normalizeBasePath($function->getFileName() ?: ''),
            $function->getStartLine(),
        );
    }

    /**
     * Retrieve the class name of the currently running scheduled task.
     */
    protected function scheduledTaskClass(): ?string
    {
        try {
            if ($this->currentlyRunningScheduledTask instanceof CallbackTask) {
                return $this->scheduledCallbackTaskClass();
            }

            $prefix = 'php '.preg_replace("#['\"]#", '', ConsoleApplication::artisanBinary()).' ';

            if (! str_starts_with($this->scheduledTaskName(), $prefix)) {
                return null;
            }

            $command = Artisan::findCommand(
                explode(' ', substr($this->scheduledTaskName(), strlen($prefix)))[0]
            );

            return $command === null
                ? null
                : $command::class;
        } catch (Throwable $e) {
            return '_laravel_cloud_error: '.$e->getMessage();
        }
    }

    /**
     * Retrieve the class name of the currently running scheduled callback task.
     */
    protected function scheduledCallbackTaskClass(): ?string
    {
        return match (true) {
            is_object($this->scheduledTaskCallback()) => $this->scheduledTaskCallback()::class,
            is_array($this->scheduledTaskCallback()) => is_string($this->scheduledTaskCallback()[0])
                ? $this->scheduledTaskCallback()[0]
                : $this->scheduledTaskCallback()[0]::class,
            is_string($this->scheduledTaskCallback()) => class_exists($class = explode('@', $this->scheduledTaskCallback())[0])
                ? $class
                : null,
            default => null,
        };
    }

    /**
     * Retrieve the callback of the currently running scheduled task.
     */
    protected function scheduledTaskCallback(): mixed
    {
        return (new ReflectionClass($this->currentlyRunningScheduledTask))
            ->getProperty('callback')
            ->getValue($this->currentlyRunningScheduledTask);
    }

    /**
     * Retrieve the timezone of the currently running scheduled task.
     */
    protected function scheduledTaskTimezone(): ?string
    {
        return $this->currentlyRunningScheduledTask->timezone instanceof DateTimeZone
            ? $this->currentlyRunningScheduledTask->timezone->getName()
            : $this->currentlyRunningScheduledTask->timezone;
    }

    /**
     * Prepare to process the given job.
     */
    public function prepareForJob(Job $job): void
    {
        $this->currentlyProcessingJob = $job;

        $this->executionContext['job'] = [
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
        $this->executionContext['job'] = [];
        $this->currentlyProcessingJob = null;
        $this->currentlyProcessingJobAttemptId = null;
    }

    /**
     * Determine if a queue worker is running.
     */
    protected function isProcessingJob(): bool
    {
        return $this->currentlyProcessingJob !== null;
    }

    /**
     * Retrieve the currently processing job's execution context.
     *
     * @return array<string, mixed>
     */
    protected function jobExecutionDetails(Throwable $e): array
    {
        return [
            'trace_id' => 'TODO',
            'execution_type' => 'job',
            'execution_context' => [
                ...$this->executionContext['job'],
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

        // TODO trim .fifo

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

    /**
     * Remember the given user as they log out.
     */
    public function rememberUser(Authenticatable $user): void
    {
        $this->rememberedUser = $user;
    }

    /**
     * Capture the authenticated user's identifier in the given context.
     */
    public function rememberUserIdInContext(ContextRepository $context): void
    {
        try {
            $context->addHidden('laravel_cloud_user_id', $this->userId());
        } catch (Throwable) {
            //
        }
    }

    /**
     * Retrieve the current user ID.
     */
    protected function userId(): ?string
    {
        try {
            if (Auth::hasResolvedGuards()) {
                if (Auth::hasUser()) {
                    return $this->userIdentifier(Auth::user());
                }

                if ($this->rememberedUser !== null) {
                    return $this->userIdentifier($this->rememberedUser);
                }
            }

            return $this->userIdFromContext();
        } catch (Throwable $e) {
            return '_laravel_cloud_error: '.$e->getMessage();
        }
    }

    /**
     * Retrieve the authenticated user's identifier from the context.
     */
    protected function userIdFromContext(): ?string
    {
        return Context::getHidden('laravel_cloud_user_id');
    }

    /**
     * Retrieve the identifier of the given user.
     */
    protected function userIdentifier(Authenticatable $user): ?string
    {
        return $user->getAuthIdentifier() === null
            ? null
            : (string) $user->getAuthIdentifier();
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
     *
     * @return array<string, mixed>
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
     *
     * @return list<array<string, mixed>>
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
     *
     * @return list<array<string, mixed>>
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
     *
     * @return string|array{string, string}
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
     *
     * @param  array<string, mixed>  $frame
     * @return array<string, mixed>
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
     *
     * @param  array<string, mixed>  $frame
     * @return array<string, mixed>
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
     * Redact the given value.
     */
    protected function redactValue(string $value): string
    {
        $length = strlen($value);

        $bytes = $length === 1 ? 'byte' : 'bytes';

        return "[{$length} {$bytes} redacted]";
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
     * Retrieve the time Laravel started.
     */
    protected function laravelStartedAtTimestamp(): string
    {
        try {
            $microtime = defined('LARAVEL_START')
                ? LARAVEL_START
                : $_SERVER['REQUEST_TIME_FLOAT'];

            return Date::createFromTimestampUTC($microtime)->toDateTimeString('microsecond');
        } catch (Throwable $e) {
            return '_laravel_cloud_error: '.$e->getMessage();
        }
    }
}
