<?php

namespace Illuminate\Foundation\Testing;

use ErrorException;
use Symfony\Component\Console\Output\OutputInterface;

class MockStream
{
    /**
     * @var \Symfony\Component\Console\Output\OutputInterface
     */
    protected static $output;

    /**
     * @var bool
     */
    protected static $existed = false;

    /**
     * Register a new Stream wrapper using the protocol mock://
     *
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @return void
     */
    public static function register(OutputInterface $output)
    {
        if (in_array('mock', stream_get_wrappers())) {
            stream_wrapper_unregister('mock');
            self::$existed = true;
        }

        self::$output = $output;

        stream_wrapper_register('mock', self::class);
    }

    /**
     * Attempt to restore the stream wrapper to the previous state, if it existed
     *
     * @return void
     */
    public static function deregister()
    {
        try {
            stream_wrapper_restore('mock');
        } catch (ErrorException $e) {
            //
        }
    }

    public function stream_open($path, $mode, $options, &$opened_path)
    {
        return true;
    }

    public function stream_write($data)
    {
        self::$output->doWrite($data, true);

        return strlen($data);
    }
}
