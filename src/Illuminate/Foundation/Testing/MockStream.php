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
    protected static $position = 0;

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
     * Return an opened resource of the captured stream.
     *
     * @return false|resource
     */
    public static function getStream()
    {
        return fopen('mock://stream', 'r+');
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
     * Retrieve information about the resource.
     *
     * @return array
     */
    public function stream_stat()
    {
        return [];
    }

    /**
     * Tests for end-of-file on the stream.
     *
     * @return bool
     */
    public function stream_eof()
    {
        return self::$position === strlen(self::$output->fetch());
    }

    /**
     * Read from the stream.
     *
     * @param $count
     * @return false|string
     */
    public function stream_read($count)
    {
        $read = substr(self::$output->fetch(), self::$position, $count);

        self::$position = self::$position + $count;

        return $read;
    }

    /**
     * Seeks to specific location in the stream.
     *
     * @param     $offset
     * @param int $whence
     * @return void
     */
    public function stream_seek($offset, $whence = SEEK_SET)
    {
        switch ($whence) {
            case SEEK_SET:
                self::$position = $offset;
                break;
            case SEEK_CUR:
                self::$position += $offset;
                break;
            case SEEK_END:
                self::$position = strlen(self::$output) + $offset;
                break;
        }
    }

    /**
     * Write to the stream.
     *
     * @param  string  $data
     * @return int
     */
    public function stream_write($data)
    {
        self::$output->doWrite($data, false);

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
