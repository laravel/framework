<?php

namespace Illuminate\Tests\Integration\Foundation\ListCommand\fixtures\app;

use Illuminate\Console\Command;

class AppCommand extends Command
{
    protected $signature = 'app-command';

    public function handle() {}
}
