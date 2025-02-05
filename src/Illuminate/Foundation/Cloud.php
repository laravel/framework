<?php

namespace Illuminate\Foundation;

use Illuminate\Database\Migrations\Migrator;
use Illuminate\Foundation\Bootstrap\HandleExceptions;
use Illuminate\Foundation\Bootstrap\LoadConfiguration;
use Monolog\Formatter\JsonFormatter;
use Monolog\Handler\SocketHandler;
use PDO;

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
                'connectionString' => $_ENV['LARAVEL_CLOUD_LOG_SOCKET'] ??
                                      $_SERVER['LARAVEL_CLOUD_LOG_SOCKET'] ??
                                      'unix:///tmp/cloud-init.sock',
                'persistent' => true,
            ],
        ]);
    }
}
