<?php

namespace Illuminate\Log;

use Monolog\Logger as Monolog;
use Monolog\Formatter\JsonFormatter;
use Monolog\Formatter\LineFormatter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Log\Middleware\CheckLogLevel;

class LogServiceProvider extends ServiceProvider
{
    /**
     * The available default log types.
     *
     * @var array
     */
    protected $types = [
        'single' => SingleChannel::class,
        'daily' => DailyChannel::class,
        'syslog' => SyslogChannel::class,
        'errorlog' => ErrorLogChannel::class,
    ];

    /**
     * The available log formats.
     *
     * @var array
     */
    protected $formats = [
        'line' => LineFormatter::class,
        'json' => JsonFormatter::class,
    ];

    /**
     * The named middleware registered with the logger.
     *
     * @var array
     */
    protected $pipes = [
        'level' => CheckLogLevel::class,
    ];

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('log', function () {
            return $this->createLogger();
        });
    }

    /**
     * Create the logger.
     *
     * @return \Illuminate\Log\LogManager
     */
    public function createLogger()
    {
        $log = new LogManager($this->app['events']);

        $channels = collect($this->app['config']->get('logging.channels'))
            ->map(function ($channelConfig) {
                $channel = $this->createChannel($channelConfig['type']);
                $channel->prepare($channelConfig);
                $channel->through($this->parsePipes(
                    $this->formatPipes($channelConfig['pipes'] ?? [])
                ));
                $channel->setFormatter(new $this->formats[$channelConfig['format'] ?? 'line']);

                return $channel;
            });

        foreach ($channels as $name => $channel) {
            $log->registerChannel($name, $channel);
        }

        $log->setDefaultChannels((array) $this->app['config']->get('logging.default'));

        return $log;
    }

    /**
     * Get the name of the log "channel".
     *
     * @return string
     */
    protected function channel()
    {
        if ($this->app->bound('config') &&
            $channel = $this->app->make('config')->get('app.log_channel')) {
            return $channel;
        }

        return $this->app->bound('env') ? $this->app->environment() : 'production';
    }

    /**
     * Parse the middleware into MiddlewareClass:arg1,arg2 format.
     *
     * @param  array  $pipes
     * @return array
     */
    protected function parsePipes(array $pipes)
    {
        return collect($pipes)->map(function ($pipe) {
            list($name, $arguments) = explode(':', $pipe);

            return $this->resolvePipe($name).':'.$arguments;
        })->all();
    }

    /**
     * Format the list of middleware into an array.
     *
     * @param  string|array  $pipes
     * @return array
     */
    private function formatPipes($pipes)
    {
        if (is_array($pipes)) {
            return $pipes;
        }

        return explode('|', $pipes);
    }

    /**
     * Resolve a middleware to its FQCN.
     *
     * @param  string  $name
     * @return string
     */
    protected function resolvePipe($name)
    {
        if (class_exists($name)) {
            return $name;
        }

        return $this->pipes[$name];
    }

    /**
     * Create an instance of a channel.
     *
     * @param  string  $driver
     * @return \Illuminate\Log\Channel
     *
     * @throws \Exception
     */
    protected function createChannel($driver)
    {
        if (class_exists($driver)) {
            return new $driver($this->app, new Monolog($this->channel()));
        }

        if (! array_key_exists($driver, $this->types)) {
            throw new ChannelUndefinedException("There is no $driver log channel type configured");
        }

        return new $this->types[$driver]($this->app, new Monolog($this->channel()));
    }
}
