<?php

namespace Illuminate\Queue;

enum WorkerStopReason: string
{
    case CacheFailed = 'cache_failed';
    case Interrupted = 'interrupted';
    case MaxJobsExceeded = 'max_jobs';
    case MaxMemoryExceeded = 'memory';
    case MaxTimeExceeded = 'max_time';
    case QueueEmpty = 'empty';
    case ReceivedRestartSignal = 'restart_signal';
}
