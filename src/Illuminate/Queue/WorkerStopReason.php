<?php

namespace Illuminate\Queue;

enum WorkerStopReason: string
{
    case Interrupted = 'interrupted';
    case MaxJobsExceeded = 'max_jobs';
    case MaxMemoryExceededMemory = 'memory';
    case MaxTimeExceeded = 'max_time';
    case QueueEmpty = 'empty';
    case ReceivedRestartSignal = 'restart_signal';
}
