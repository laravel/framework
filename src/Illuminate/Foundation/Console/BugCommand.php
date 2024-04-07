<?php

namespace Illuminate\Foundation\Console;

use Closure;
use Illuminate\Console\Command;
use Illuminate\Support\Composer;
use Illuminate\Support\Str;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'bug')]
class BugCommand extends Command
{
    /**
     * The console command signature.
     *
     * @var string
     */
    protected $signature = 'bug {--title= : The title of bug}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Start to create a bug report';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {

        return 0;
    }
}
