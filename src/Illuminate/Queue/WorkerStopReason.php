<?php

namespace Illuminate\Queue;

enum WorkerStopReason: string
{
    case INTERRUPTED = 'interrupted';
    case MEMORY = 'memory';
    case RESTART_SIGNAL = 'restart_signal';
    case EMPTY = 'empty';
    case MAX_JOBS = 'max_jobs';
    case MAX_TIME = 'max_time';
}
