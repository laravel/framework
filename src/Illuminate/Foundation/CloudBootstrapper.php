<?php

namespace Illuminate\Foundation;

use Illuminate\Auth\Events\Logout;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Console\Events\ScheduledTaskFinished;
use Illuminate\Console\Events\ScheduledTaskSkipped;
use Illuminate\Console\Events\ScheduledTaskStarting;
use Illuminate\Contracts\Debug\ExceptionHandler as ExceptionHandlerContract;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Foundation\Bootstrap\BootProviders;
use Illuminate\Foundation\Bootstrap\HandleExceptions;
use Illuminate\Foundation\Bootstrap\LoadConfiguration;
use Illuminate\Foundation\Cloud\Events;
use Illuminate\Foundation\Cloud\ExceptionReporter;
use Illuminate\Foundation\Cloud\FailedJobProvider;
use Illuminate\Foundation\Cloud\QueueConnector;
use Illuminate\Foundation\Exceptions\Renderer\Mappers\BladeMapper;
use Illuminate\Log\Context\Events\ContextDehydrating;
use Illuminate\Queue\Connectors\SqsConnector;
use Illuminate\Queue\Events\JobPopped;
use Illuminate\Queue\Events\Looping;
use Illuminate\Queue\Events\WorkerStopping;
use Monolog\Handler\SocketHandler;
use PDO;
use Throwable;

class CloudBootstrapper
{
    /**
     * Handle a bootstrapper that is bootstrapping.
     */
    public static function bootstrapping(Application $app, string $bootstrapper): void
    {
        (match ($bootstrapper) {
            BootProviders::class => function () use ($app) {
                static::bootManagedQueues($app);
            },
            default => fn () => true,
        })();
    }

    /**
     * Handle a bootstrapper that has bootstrapped.
     */
    public static function bootstrapped(Application $app, string $bootstrapper): void
    {
        (match ($bootstrapper) {
            LoadConfiguration::class => function () use ($app) {
                static::configureDisks($app);
                static::configureUnpooledPostgresConnection($app);
                static::ensureMigrationsUseUnpooledConnection($app);
                static::configureManagedQueues($app);
            },
            HandleExceptions::class => function () use ($app) {
                static::configureCloudLogging($app);
                static::registerEvents($app);
                static::registerExceptionReporting($app);
            },
            default => fn () => true,
        })();
    }

    /**
     * Configure the Laravel Cloud disks if applicable.
     */
    public static function configureDisks(Application $app): void
    {
        if (! isset($_SERVER['LARAVEL_CLOUD_DISK_CONFIG'])) {
            return;
        }

        $disks = json_decode($_SERVER['LARAVEL_CLOUD_DISK_CONFIG'], true);

        foreach ($disks as $disk) {
            $app['config']->set('filesystems.disks.'.$disk['disk'], [
                'driver' => 's3',
                'key' => $disk['access_key_id'],
                'secret' => $disk['access_key_secret'],
                'bucket' => $disk['bucket'],
                'url' => $disk['url'],
                'endpoint' => $disk['endpoint'],
                'region' => $disk['region'] ?? 'auto',
                'credentials' => $disk['credentials'] ?? null,
                'use_path_style_endpoint' => false,
                'throw' => false,
                'report' => false,
            ]);

            if ($disk['is_default'] ?? false) {
                $app['config']->set('filesystems.default', $disk['disk']);
            }
        }
    }

    /**
     * Configure the unpooled Laravel Postgres connection if applicable.
     */
    public static function configureUnpooledPostgresConnection(Application $app): void
    {
        $host = $app['config']->get('database.connections.pgsql.host', '');

        if (str_contains($host, 'pg.laravel.cloud') &&
            str_contains($host, '-pooler')) {
            $app['config']->set(
                'database.connections.pgsql-unpooled',
                array_merge($app['config']->get('database.connections.pgsql'), [
                    'host' => str_replace('-pooler', '', $host),
                ])
            );

            $app['config']->set(
                'database.connections.pgsql.options',
                array_merge(
                    $app['config']->get('database.connections.pgsql.options', []),
                    [PDO::ATTR_EMULATE_PREPARES => true],
                ),
            );
        }
    }

    /**
     * Ensure that migrations use the unpooled Postgres connection if applicable.
     */
    public static function ensureMigrationsUseUnpooledConnection(Application $app): void
    {
        if (! is_array($app['config']->get('database.connections.pgsql-unpooled'))) {
            return;
        }

        Migrator::resolveConnectionsUsing(function ($resolver, $connection) use ($app) {
            $connection = $connection ?? $app['config']->get('database.default');

            return $resolver->connection(
                $connection === 'pgsql' ? 'pgsql-unpooled' : $connection
            );
        });
    }

    /**
     * Configure managed queues if applicable.
     */
    public static function configureManagedQueues(Application $app): void
    {
        if (! isset($_SERVER['LARAVEL_CLOUD_MANAGED_QUEUES_CONFIG'])) {
            return;
        }

        $config = json_decode($_SERVER['LARAVEL_CLOUD_MANAGED_QUEUES_CONFIG'], associative: true, flags: JSON_THROW_ON_ERROR);

        $config['connection']['after_commit'] ??= env('CLOUD_QUEUE_AFTER_COMMIT', false);

        $config['connection']['overflow'] ??= [
            'enabled' => env('CLOUD_QUEUE_OVERFLOW_ENABLED', false),
            'store' => env('CLOUD_QUEUE_OVERFLOW_STORE'),
            'always' => env('CLOUD_QUEUE_OVERFLOW_ALWAYS', false),
            'delete_after_processing' => env('CLOUD_QUEUE_OVERFLOW_DELETE_AFTER_PROCESSING', true),
        ];

        $app['config']->set('queue.connections.cloud', $config);
    }

    /**
     * Boot managed queues if applicable.
     */
    public static function bootManagedQueues(Application $app): void
    {
        if ($app['config']->get('queue.connections.cloud.driver') !== 'cloud') {
            return;
        }

        $app->bind(QueueConnector::class, fn ($app) => new QueueConnector(new SqsConnector, $app));

        $app['queue']->addConnector('cloud', $app->factory(QueueConnector::class));

        $failer = $app['queue.failer'];
        unset($app['queue.failer']);

        $app->singleton('queue.failer', fn ($app) => new FailedJobProvider(
            $failer, $app[Events::class], $app['encrypter'],
            $app->bound(ExceptionReporter::class) ? $app[ExceptionReporter::class] : null,
        ));
    }

    /**
     * Configure the Laravel Cloud log channels.
     */
    public static function configureCloudLogging(Application $app): void
    {
        $app['config']->set('logging.channels.stderr.formatter_with', [
            'includeStacktraces' => true,
        ]);

        $app['config']->set('logging.channels.laravel-cloud-socket', [
            'driver' => 'monolog',
            'level' => $_ENV['LOG_LEVEL'] ?? $_SERVER['LOG_LEVEL'] ?? 'debug',
            'handler' => SocketHandler::class,
            'formatter' => LaravelCloudJsonFormatter::class,
            'formatter_with' => [
                'includeStacktraces' => true,
            ],
            'with' => [
                'connectionString' => CloudBootstrapper::socket(),
                'persistent' => true,
                'timeout' => 2.0,
            ],
        ]);
    }

    /**
     * Register the Laravel Cloud exception reporter if applicable.
     */
    public static function registerExceptionReporting(Application $app): void
    {
        try {
            if (! isset($_SERVER['LARAVEL_CLOUD_EXCEPTIONS'])) {
                return;
            }

            $config = [
                'stop' => true,
                'capture_request_payload' => false,
                'redact_request_payload_fields' => ['_token', 'password', 'password_confirmation', 'current_password'],
                'redact_headers' => ['Authorization', 'Cookie', 'Proxy-Authorization', 'X-CSRF-TOKEN', 'X-XSRF-TOKEN'],
                'redact_command_input_fields' => ['token', 'password', 'key', 'secret'],
                ...json_decode($_SERVER['LARAVEL_CLOUD_EXCEPTIONS'], associative: true, flags: JSON_THROW_ON_ERROR),
            ];

            $exceptionReporter = $app->instance(ExceptionReporter::class, new ExceptionReporter(
                $app[Events::class],
                $app->factory(BladeMapper::class),
                $app->basePath().DIRECTORY_SEPARATOR,
                $config,
            ));

            // Defer registration of the reporter until exception handler is resolved...
            $registerReporter = function ($handler) use ($exceptionReporter) {
                try {
                    $handler->reportable($exceptionReporter);
                } catch (Throwable) {
                    //
                }
            };

            $app->resolved(ExceptionHandlerContract::class)
                ? $registerReporter($app[ExceptionHandlerContract::class])
                : $app->afterResolving(ExceptionHandlerContract::class, $registerReporter);

            $app['events']->listen(fn (ContextDehydrating $event) => $exceptionReporter->rememberUserIdInContext($event->context));
            $app['events']->listen(fn (ContextDehydrating $event) => $exceptionReporter->rememberTraceIdInContext($event->context));

            if (! $app->runningInConsole()) {
                $app['events']->listen(function (Logout $event) use ($exceptionReporter) {
                    if ($event->user !== null) {
                        $exceptionReporter->rememberUser($event->user);
                    }
                });
            } else {
                $preparedForCommand = false;

                $app['events']->listen(function (CommandStarting $event) use ($exceptionReporter, &$preparedForCommand) {
                    if (! $preparedForCommand) {
                        $exceptionReporter->prepareForCommand($event->command, $event->input);

                        $preparedForCommand = true;
                    }
                });

                $app['events']->listen(function (JobPopped $event) use ($exceptionReporter) {
                    if ($event->job !== null) {
                        $exceptionReporter->prepareForJob($event->job);
                    }
                });

                $app['events']->listen(fn (Looping $event) => $exceptionReporter->flushJobContext());
                $app['events']->listen(fn (ScheduledTaskFinished $event) => $exceptionReporter->finishScheduledTask($event->task));
                $app['events']->listen(fn (ScheduledTaskSkipped $event) => $exceptionReporter->flushScheduledTaskContext());
                $app['events']->listen(fn (ScheduledTaskStarting $event) => $exceptionReporter->prepareForScheduledTask($event->task));
                $app['events']->listen(fn (WorkerStopping $event) => $exceptionReporter->flushJobContext());
            }
        } catch (Throwable) {
            return;
        }
    }

    /**
     * Register the events system for Laravel Cloud.
     */
    public static function registerEvents(Application $app): void
    {
        $app->singleton(Events::class, fn () => new Events(CloudBootstrapper::socket()));
    }

    /**
     * The cloud socket address.
     */
    protected static function socket(): string
    {
        return $_ENV['LARAVEL_CLOUD_LOG_SOCKET'] ??
            $_SERVER['LARAVEL_CLOUD_LOG_SOCKET'] ??
                'unix:///tmp/cloud-init.sock';
    }
}
