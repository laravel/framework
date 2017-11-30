<?php

namespace Illuminate\Log;

use Monolog\Logger;
use Monolog\Handler\RotatingFileHandler;
use Illuminate\Contracts\Container\Container;

class DailyChannel extends Channel
{
    /**
     * The default options for the channel.
     *
     * @var array
     */
    protected $defaultOptions = [
        'file' => 'laravel.log',
        'days' => 5,
    ];

    /**
     * Create a daily file channel.
     *
     * @param  \Illuminate\Contracts\Container\Container  $app
     * @param \Monolog\Logger  $writer
     * @return void
     */
    public function __construct(Container $app, Logger $writer)
    {
        parent::__construct($app, $writer);
        $this->defaultOptions['file'] = $this->app->storagePath().'/logs/'.$this->defaultOptions['file'];
    }

    /**
     * Setup a rotating file handler for daily logging.
     *
     * @param  array  $options
     * @return void
     */
    public function prepare(array $options = [])
    {
        $options = array_merge($this->defaultOptions, $options);

        $this->writer->pushHandler(
            new RotatingFileHandler($options['file'], $options['days'])
        );
    }
}
