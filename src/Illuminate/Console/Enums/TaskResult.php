<?php

namespace Illuminate\Console\Enums;

enum TaskResult: int
{
    case Success = 1;
    case Failure = 2;
    case Skipped = 3;
}
