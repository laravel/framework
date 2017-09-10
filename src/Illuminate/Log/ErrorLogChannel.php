<?php

namespace Illuminate\Log;

use Monolog\Handler\ErrorLogHandler;

class ErrorLogChannel extends Channel
{
    /**
     * The default options for the channel.
     *
     * @var array
     */
    protected $defaultOptions = [
        'message_type' => ErrorLogHandler::OPERATING_SYSTEM,
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

        $this->writer->pushHandler(new ErrorLogHandler($options['message_type']));
    }
}
