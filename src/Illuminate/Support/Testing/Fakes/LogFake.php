<?php

namespace Illuminate\Support\Testing\Fakes;

use Psr\Log\LoggerInterface;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Assert as PHPUnit;

class LogFake implements LoggerInterface
{
    /**
     * All messages that have been written.
     *
     * @var array $messages
     */
    protected $messages = [];

    /**
     * Assert, if the message was written.
     *
     * @param string $message
     * @return void
     */
    public function assertWritten($message)
    {
        PHPUnit::assertTrue(
            $this->hasEntry($message),
            "The expected [{$message}] message was not written."
        );
    }

    /**
     * Assert, if the message was not written.
     *
     * @param string $message
     * @return void
     */
    public function assertNotWritten($message)
    {
        PHPUnit::assertFalse(
            $this->hasEntry($message),
            "The expected [{$message}] message was written."
        );
    }

    /**
     * Assert, if there were no written messages.
     *
     * @return void
     */
    public function assertNothingWritten()
    {
        PHPUnit::assertTrue(
            count($this->messages) === 0,
            'The written messages were found.'
        );
    }

    /**
     * Assert, if the message was written a number of times.
     *
     * @param string $message
     * @param int $times
     * @return void
     */
    public function assertWrittenTimes($message, $times)
    {
        PHPUnit::assertCount(
            $times,
            $entries = $this->getEntriesByMessage($message),
            "The expected message was written {$entries->count()} times instead of {$times} times."
        );
    }

    /**
     * Determine, if a message has been written.
     *
     * @param string $message
     * @return bool
     */
    protected function hasEntry($message)
    {
        return collect($this->messages)->contains($message);
    }

    /**
     * Get filtered entities by message.
     *
     * @param string $message
     * @return Collection
     */
    protected function getEntriesByMessage($message)
    {
        return collect($this->messages)->filter(function ($entry) use ($message) {
            return $entry === $message;
        });
    }

    /**
     * System is unusable.
     *
     * @param string $message
     * @param array $context
     *
     * @return void
     */
    public function emergency($message, array $context = array())
    {
        $this->writeLog($message);
    }

    /**
     * Action must be taken immediately.
     *
     * Example: Entire website down, database unavailable, etc. This should
     * trigger the SMS alerts and wake you up.
     *
     * @param string $message
     * @param array $context
     *
     * @return void
     */
    public function alert($message, array $context = array())
    {
        $this->writeLog($message);
    }

    /**
     * Critical conditions.
     *
     * Example: Application component unavailable, unexpected exception.
     *
     * @param string $message
     * @param array $context
     *
     * @return void
     */
    public function critical($message, array $context = array())
    {
        $this->writeLog($message);
    }

    /**
     * Runtime errors that do not require immediate action but should typically
     * be logged and monitored.
     *
     * @param string $message
     * @param array $context
     *
     * @return void
     */
    public function error($message, array $context = array())
    {
        $this->writeLog($message);
    }

    /**
     * Exceptional occurrences that are not errors.
     *
     * Example: Use of deprecated APIs, poor use of an API, undesirable things
     * that are not necessarily wrong.
     *
     * @param string $message
     * @param array $context
     *
     * @return void
     */
    public function warning($message, array $context = array())
    {
        $this->writeLog($message);
    }

    /**
     * Normal but significant events.
     *
     * @param string $message
     * @param array $context
     *
     * @return void
     */
    public function notice($message, array $context = array())
    {
        $this->writeLog($message);
    }

    /**
     * Interesting events.
     *
     * Example: User logs in, SQL logs.
     *
     * @param string $message
     * @param array $context
     *
     * @return void
     */
    public function info($message, array $context = array())
    {
        $this->writeLog($message);
    }

    /**
     * Detailed debug information.
     *
     * @param string $message
     * @param array $context
     *
     * @return void
     */
    public function debug($message, array $context = array())
    {
        $this->writeLog($message);
    }

    /**
     * Logs with an arbitrary level.
     *
     * @param mixed $level
     * @param string $message
     * @param array $context
     *
     * @return void
     */
    public function log($level, $message, array $context = array())
    {
        $this->writeLog($message);
    }

    /**
     * Write a message to stack of log messages.
     *
     * @param  string  $message
     * @return void
     */
    protected function writeLog($message)
    {
        $this->messages[] = $message;
    }
}