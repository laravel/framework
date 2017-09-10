<?php

namespace Illuminate\Log;

use Monolog\Handler\SyslogHandler;

class SyslogChannel extends Channel
{
    /**
     * The default options for the channel.
     *
     * @var array
     */
    protected $defaultOptions = [
        'name' => 'laravel',
        'facility' => LOG_USER,
    ];

    /**
     * Prepare the record for daily logging.
     *
     * @param  array  $options
     * @return void
     */
    public function prepare(array $options = [])
    {
        $options = array_merge($this->defaultOptions, $options);

        $this->writer->pushHandler(new SyslogHandler($options['name'], $options['facility']));
    }
}
