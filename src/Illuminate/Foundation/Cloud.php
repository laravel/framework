<?php

namespace Illuminate\Foundation;

use Illuminate\Auth\Events\Logout;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Console\Events\ScheduledTaskFinished;
use Illuminate\Console\Events\ScheduledTaskSkipped;
use Illuminate\Console\Events\ScheduledTaskStarting;
use Illuminate\Contracts\Debug\ExceptionHandler as ExceptionHandlerContract;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Foundation\Bootstrap\HandleExceptions;
use Illuminate\Foundation\Bootstrap\LoadConfiguration;
use Illuminate\Foundation\Cloud\Events;
use Illuminate\Foundation\Cloud\ExceptionReporter;
use Illuminate\Foundation\Http\Events\RequestHandled;
use Illuminate\Queue\Events\JobPopped;
use Illuminate\Queue\Events\Looping;
use Illuminate\Queue\Events\WorkerStopping;
use Illuminate\Queue\Queue;
use Monolog\Formatter\JsonFormatter;
use Monolog\Handler\SocketHandler;
use PDO;
use Throwable;

class Cloud
{
    /**
     * Handle a bootstrapper that is bootstrapping.
     */
    public static function bootstrapperBootstrapping(Application $app, string $bootstrapper): void
    {
        //
    }

    /**
     * Handle a bootstrapper that has bootstrapped.
     */
    public static function bootstrapperBootstrapped(Application $app, string $bootstrapper): void
    {
        (match ($bootstrapper) {
            LoadConfiguration::class => function () use ($app) {
                static::configureDisks($app);
                static::configureUnpooledPostgresConnection($app);
                static::ensureMigrationsUseUnpooledConnection($app);
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
                'region' => 'auto',
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
     * Configure the Laravel Cloud log channels.
     */
    public static function configureCloudLogging(Application $app): void
    {
        $app['config']->set('logging.channels.stderr.formatter_with', [
            'includeStacktraces' => true,
        ]);

        $app['config']->set('logging.channels.laravel-cloud-socket', [
            'driver' => 'monolog',
            'handler' => SocketHandler::class,
            'formatter' => JsonFormatter::class,
            'formatter_with' => [
                'includeStacktraces' => true,
            ],
            'with' => [
                'connectionString' => Cloud::socket(),
                'persistent' => true,
                'timeout' => 2.0,
            ],
        ]);
    }

    /**
     * Register the events system for Laravel Cloud.
     */
    public static function registerEvents(Application $app): void
    {
        $app->singleton(Events::class, fn () => new Events(Cloud::socket()));
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
                ...json_decode($_SERVER['LARAVEL_CLOUD_EXCEPTIONS'], associative: true, flags: JSON_THROW_ON_ERROR),
            ];

            $exceptionReporter = $app->instance(ExceptionReporter::class, new ExceptionReporter(
                $app[Events::class],
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

            Queue::createPayloadUsing(fn () => $exceptionReporter->jobPayload());

            if (! $app->runningInConsole()) {
                $app['events']->listen(function (Logout $event) use ($exceptionReporter) {
                    if ($event->user !== null) {
                        $exceptionReporter->rememberUser($event->user);
                    }
                });

                $app['events']->listen(fn (RequestHandled $event) => $exceptionReporter->forgetLoggedOutUser());
            } else {
                $preparedForCommand = false;

                $app['events']->listen(function (CommandStarting $event) use ($exceptionReporter, &$preparedForCommand) {
                    if (! $preparedForCommand) {
                        $exceptionReporter->prepareForCommand($event->command);

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
     * The cloud socket address.
     */
    protected static function socket(): string
    {
        return $_ENV['LARAVEL_CLOUD_LOG_SOCKET'] ??
            $_SERVER['LARAVEL_CLOUD_LOG_SOCKET'] ??
                'unix:///tmp/cloud-init.sock';
    }
}
