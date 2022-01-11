<?php

namespace Illuminate\Log;

use Illuminate\Support\Collection;
use Psr\Log\LoggerInterface;

class MultiChannelLogger implements LoggerInterface
{
    protected Collection $drivers;
    protected LogManager $logManager;

    public function __construct(array $drivers, LogManager $logManager)
    {
        $this->drivers = collect($drivers);
        $this->logManager = $logManager;
    }

    public function emergency($message, array $context = [])
    {
        $this->drivers->each(fn () => $this->logManager->emergency($message, $context));
    }

    public function alert($message, array $context = [])
    {
        $this->drivers->each(fn () => $this->logManager->alert($message, $context));
    }

    public function critical($message, array $context = [])
    {
        $this->drivers->each(fn () => $this->logManager->critical($message, $context));
    }

    public function error($message, array $context = [])
    {
        $this->drivers->each(fn () => $this->logManager->error($message, $context));
    }

    public function warning($message, array $context = [])
    {
        $this->drivers->each(fn () => $this->logManager->warning($message, $context));
    }

    public function notice($message, array $context = [])
    {
        $this->drivers->each(fn () => $this->logManager->notice($message, $context));
    }

    public function info($message, array $context = [])
    {
        $this->drivers->each(fn () => $this->logManager->info($message, $context));
    }

    public function debug($message, array $context = [])
    {
        $this->drivers->each(fn () => $this->logManager->debug($message, $context));
    }

    public function log($level, $message, array $context = [])
    {
        $this->drivers->each(fn () => $this->logManager->log($message, $context));
    }
}
