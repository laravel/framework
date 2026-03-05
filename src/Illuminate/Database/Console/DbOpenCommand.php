<?php

namespace Illuminate\Database\Console;

use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'db:open')]
class DbOpenCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:open {connection? : The database connection that should be used}
               {--read : Connect to the read connection}
               {--write : Connect to the write connection}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Open the database connection in a GUI client';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        // This is an alias for 'db --open'
        $arguments = array_filter([
            'connection' => $this->argument('connection'),
            '--read' => $this->option('read'),
            '--write' => $this->option('write'),
            '--open' => true,
        ]);

        return $this->call('db', $arguments);
    }
}
