<?php

namespace Illuminate\Log;

use InvalidArgumentException;
use Monolog\Logger as Monolog;

trait ParsesLogConfiguration
{
    /**
     * The Log levels.
     *
     * @var array
     */
    protected $levels = [
        'debug' => Monolog::DEBUG,
        'info' => Monolog::INFO,
        'notice' => Monolog::NOTICE,
        'warning' => Monolog::WARNING,
        'error' => Monolog::ERROR,
        'critical' => Monolog::CRITICAL,
        'alert' => Monolog::ALERT,
        'emergency' => Monolog::EMERGENCY,
    ];

    /**
     * Get fallback log channel name.
     *
     * @return string
     */
    abstract protected function getFallbackChannelName();

    /**
     * Parse the string level into a Monolog constant.
     *
     * @param  array  $config
     * @return int
     *
     * @throws \InvalidArgumentException
     */
    protected function level(array $config)
    {
        $level = $config['level'] ?? 'debug';

        if (isset($this->levels[$level])) {
            return $this->levels[$level];
        }

        throw new InvalidArgumentException('Invalid log level.');
    }

    /**
     * Parse the action level from the given configuration.
     *
     * @param  array  $config
     * @return int
     */
    protected function actionLevel(array $config)
    {
        $level = $config['action_level'] ?? 'debug';

        if (isset($this->levels[$level])) {
            return $this->levels[$level];
        }

        throw new InvalidArgumentException('Invalid log action level.');
    }

    /**
     * Extract the log channel from the given configuration.
     *
     * @param  array  $config
     * @return string
     */
    protected function parseChannel(array $config)
    {
        return $config['name'] ?? $this->getFallbackChannelName();
    }
}
