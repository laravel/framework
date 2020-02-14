<?php

namespace Illuminate\Foundation\Testing;

use ErrorException;
use Symfony\Component\Console\Output\OutputInterface;

class MockStream
{
    /**
     * The console output implementation.
     *
     * @var \Symfony\Component\Console\Output\OutputInterface
     */
    protected static $output;

    /**
     * Register a new stream wrapper using the protocol mock://.
     *
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @return void
     */
    public static function register(OutputInterface $output)
    {
        self::unregister();

        self::$output = $output;

        stream_wrapper_register('mock', self::class);
    }

    /**
     * Open the stream.
     *
     * @param  string  $path
     * @param  string  $mode
     * @param  int  $options
     * @param  string  $opened_path
     * @return bool
     */
    public function stream_open($path, $mode, $options, &$opened_path)
    {
        return true;
    }

    /**
     * Write to the stream.
     *
     * @param  string  $data
     * @return int
     */
    public function stream_write($data)
    {
        self::$output->doWrite($data, true);

        return strlen($data);
    }

    /**
     * Attempt to restore the stream wrapper to the previous state, if it existed.
     *
     * @return void
     */
    public static function restore()
    {
        self::unregister();

        try {
            stream_wrapper_restore('mock');
        } catch (ErrorException $e) {
            //
        }
    }

    /**
     * Unregister the mock:// stream wrapper.
     *
     * @return void
     */
    protected static function unregister()
    {
        if (in_array('mock', stream_get_wrappers())) {
            stream_wrapper_unregister('mock');
        }
    }
}
