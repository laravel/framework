<?php

namespace Illuminate\Queue\Enums;

enum WorkerExitCode: int
{
    case SUCCESS = 0;
    case ERROR = 1;
    case MEMORY_LIMIT = 12;
}
