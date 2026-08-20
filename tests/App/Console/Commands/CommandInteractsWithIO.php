<?php

namespace Illuminate\Tests\App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Console\Concerns\InteractsWithIO;

class CommandInteractsWithIO extends Command
{
    use InteractsWithIO;
}
