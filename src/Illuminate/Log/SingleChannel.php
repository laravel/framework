<?php

namespace Illuminate\Log;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Illuminate\Contracts\Container\Container;

class SingleChannel extends Channel
{
    /**
     * The default options for the channel.
     *
     * @var array
     */
    protected $defaultOptions = [
        'file' => 'laravel.log',
    ];

    /**
     * Create a single file channel.
     *
     * @param  \Illuminate\Contracts\Container\Container  $app
     * @param  \Monolog\Logger  $writer
     * @return void
     */
    public function __construct(Container $app, Logger $writer)
    {
        parent::__construct($app, $writer);
        $this->defaultOptions['file'] = $this->app->storagePath().'/logs/'.$this->defaultOptions['file'];
    }

    /**
     * Setup a new handler for a single file.
     *
     * @param  array  $options
     * @return void
     */
    public function prepare(array $options = [])
    {
        $options = array_merge($this->defaultOptions, $options);

        $this->writer->pushHandler(new StreamHandler($options['file']));
    }
}
