<?php

namespace Illuminate\Queue;

enum WorkerStopReason: string
{
    case Interrupted = 'interrupted';
    case LostConnection = 'lost_connection';
    case MaxJobsExceeded = 'max_jobs';
    case MaxMemoryExceeded = 'memory';
    case MaxTimeExceeded = 'max_time';
    case QueueEmpty = 'empty';
    case QueueEmptyFor = 'empty_for';
    case ReceivedRestartSignal = 'restart_signal';
    case TimedOut = 'timed_out';

    public function description(): string
    {
        return match ($this) {
            self::Interrupted => 'Interrupted',
            self::LostConnection => 'Lost connection',
            self::MaxJobsExceeded => 'Maximum jobs exceeded',
            self::MaxMemoryExceeded => 'Memory limit exceeded',
            self::MaxTimeExceeded => 'Maximum run time exceeded',
            self::QueueEmpty => 'Queue empty',
            self::QueueEmptyFor => 'Queue empty for the configured duration',
            self::ReceivedRestartSignal => 'Received restart signal',
            self::TimedOut => 'Job timed out',
        };
    }
}
