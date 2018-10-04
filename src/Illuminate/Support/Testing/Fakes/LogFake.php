<?php

namespace Illuminate\Support\Testing\Fakes;

use Psr\Log\LoggerInterface;
use PHPUnit\Framework\Assert as PHPUnit;

class LogFake implements LoggerInterface
{
    /**
     * All of the created logs.
     *
     * @var array
     */
    protected $logs = [];

    /**
     * The channel being logged to.
     */
    protected $currentChannel;

    /**
     * Assert if a log was created based on a truth-test callback.
     *
     * @param  string  $level
     * @param  callable|int|null  $callback
     * @return void
     */
    public function assertLogged($level, $callback = null)
    {
        if (is_numeric($callback)) {
            return $this->assertLoggedTimes($level, $callback);
        }

        PHPUnit::assertTrue(
            $this->logged($level, $callback)->count() > 0,
            "The expected log with level [{$level}] was not logged."
        );
    }

    /**
     * Assert if a log was created a number of times.
     *
     * @param  string  $level
     * @param  int  $times
     * @return void
     */
    public function assertLoggedTimes($level, $times = 1)
    {
        PHPUnit::assertTrue(
            ($count = $this->logged($level)->count()) === $times,
            "The expected log with level [{$level}] was logged {$count} times instead of {$times} times."
        );
    }

    /**
     * Determine if a log was not created based on a truth-test callback.
     *
     * @param  string  $level
     * @param  callable|null  $callback
     * @return void
     */
    public function assertNotLogged($level, $callback = null)
    {
        PHPUnit::assertTrue(
            $this->logged($level, $callback)->count() === 0,
            "The unexpected log with level [{$level}] was logged."
        );
    }

    /**
     * Assert that no logs were created.
     *
     * @return void
     */
    public function assertNothingLogged()
    {
        PHPUnit::assertEmpty($this->logs, 'Logs were created.');
    }

    /**
     * Get all of the logs matching a truth-test callback.
     *
     * @param  string  $level
     * @param  callable|null  $callback
     * @return \Illuminate\Support\Collection
     */
    public function logged($level, $callback = null)
    {
        $callback = $callback ?: function () {
            return true;
        };

        return $this->logsOf($level)->filter(function ($log) use ($callback) {
            return $callback($log['message'], $log['context']);
        });
    }

    /**
     * Determine if the given log level has been created.
     *
     * @param  string  $level
     * @return bool
     */
    public function hasLogged($level)
    {
        return $this->logsOf($level)->isNotEmpty();
    }

    /**
     * Determine if the given log level has not been created.
     *
     * @param  string  $level
     * @return bool
     */
    public function hasNotLogged($level)
    {
        return ! $this->hasLogged($level);
    }

    /**
     * Get all of the created logs for a given level.
     *
     * @param  string  $type
     * @return \Illuminate\Support\Collection
     */
    protected function logsOf($level)
    {
        return collect($this->logs)->filter(function ($log) use ($level) {
            return $log['level'] === $level && $log['channel'] === $this->getCurrentChannel();
        });
    }

    /**
     * Log an emergency message to the logs.
     *
     * @param  string  $message
     * @param  array  $context
     * @return void
     */
    public function emergency($message, array $context = [])
    {
        $this->writeLog(__FUNCTION__, $message, $context);
    }

    /**
     * Log an alert message to the logs.
     *
     * @param  string  $message
     * @param  array  $context
     * @return void
     */
    public function alert($message, array $context = [])
    {
        $this->writeLog(__FUNCTION__, $message, $context);
    }

    /**
     * Log a critical message to the logs.
     *
     * @param  string  $message
     * @param  array  $context
     * @return void
     */
    public function critical($message, array $context = [])
    {
        $this->writeLog(__FUNCTION__, $message, $context);
    }

    /**
     * Log an error message to the logs.
     *
     * @param  string  $message
     * @param  array  $context
     * @return void
     */
    public function error($message, array $context = [])
    {
        $this->writeLog(__FUNCTION__, $message, $context);
    }

    /**
     * Log a warning message to the logs.
     *
     * @param  string  $message
     * @param  array  $context
     * @return void
     */
    public function warning($message, array $context = [])
    {
        $this->writeLog(__FUNCTION__, $message, $context);
    }

    /**
     * Log a notice to the logs.
     *
     * @param  string  $message
     * @param  array  $context
     * @return void
     */
    public function notice($message, array $context = [])
    {
        $this->writeLog(__FUNCTION__, $message, $context);
    }

    /**
     * Log an informational message to the logs.
     *
     * @param  string  $message
     * @param  array  $context
     * @return void
     */
    public function info($message, array $context = [])
    {
        $this->writeLog(__FUNCTION__, $message, $context);
    }

    /**
     * Log a debug message to the logs.
     *
     * @param  string  $message
     * @param  array  $context
     * @return void
     */
    public function debug($message, array $context = [])
    {
        $this->writeLog(__FUNCTION__, $message, $context);
    }

    /**
     * Log a message to the logs.
     *
     * @param  string  $level
     * @param  string  $message
     * @param  array  $context
     * @return void
     */
    public function log($level, $message, array $context = [])
    {
        $this->writeLog($level, $message, $context);
    }

    /**
     * Dynamically pass log calls into the writer.
     *
     * @param  string  $level
     * @param  string  $message
     * @param  array  $context
     * @return void
     */
    public function write($level, $message, array $context = [])
    {
        $this->writeLog($level, $message, $context);
    }

    /**
     * Write a message to the log.
     *
     * @param  string  $level
     * @param  string  $message
     * @param  array  $context
     * @return void
     */
    protected function writeLog($level, $message, $context)
    {
        $this->logs[] = [
            'level' => $level,
            'message' => $message,
            'context' => $context,
            'channel' => $this->getCurrentChannel(),
        ];
    }

    /**
     * Get the underlying logger implementation.
     *
     * @return $this
     */
    public function getLogger()
    {
        return $this;
    }

    /**
     * Get a log channel instance.
     *
     * @param  string|null  $channel
     * @return \Illuminate\Support\Testing\Fakes\LogChannelFake
     */
    public function channel($channel = null)
    {
        return $this->driver($channel);
    }

    /**
     * Get a log driver instance.
     *
     * @param  string|null  $driver
     * @return \Illuminate\Support\Testing\Fakes\LogChannelFake
     */
    public function driver($driver = null)
    {
        return new LogChannelFake($this, $driver);
    }

    /**
     * Create a new, on-demand aggregate logger instance.
     *
     * @param  array  $channels
     * @param  string|null  $channel
     * @return \Illuminate\Support\Testing\Fakes\LogChannelFake
     */
    public function stack(array $channels, $channel = null)
    {
        return $this->driver('Stack:'.$this->createStackChannelName($channels, $channel));
    }

    /**
     * Create a stack based channel name.
     *
     * @param  array  $channels
     * @param  string|null  $channel
     * @return string
     */
    protected function createStackChannelName($channels, $channel)
    {
        return collect($channels)->sort()->prepend($channel ?? 'default_testing_stack_channel')->implode('.');
    }

    /**
     * Set the current channel being logged to.
     *
     * @param  string  $name
     * @return void
     */
    public function setCurrentChannel($name)
    {
        $this->currentChannel = $name;
    }

    /**
     * Get the current channel being logged to.
     *
     * @return string
     */
    protected function getCurrentChannel()
    {
        return $this->currentChannel ?? $this->getDefaultDriver();
    }

    /**
     * Get the default log driver name.
     *
     * @return string
     */
    public function getDefaultDriver()
    {
        return config('logging.default');
    }

     /**
     * Set the default log driver name.
     *
     * @param  string  $name
     * @return void
     */
    public function setDefaultDriver($name)
    {
        config()->set('logging.default', $name);
    }

    public function listen()
    {
        //
    }

    public function extend()
    {
        //
    }

    public function getEventDispatcher()
    {
        //
    }

    public function setEventDispatcher()
    {
        //
    }
}
