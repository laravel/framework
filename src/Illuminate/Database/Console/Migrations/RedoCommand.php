<?php

namespace Illuminate\Database\Console\Migrations;

use Illuminate\Console\ConfirmableTrait;
use Illuminate\Database\Migrations\Migrator;
use Symfony\Component\Console\Input\InputOption;

class RedoCommand extends BaseCommand
{
    use ConfirmableTrait;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'migrate:redo';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rollback and rerun 1 or more latest database migrations';

    /**
     * The migrator instance.
     *
     * @var \Illuminate\Database\Migrations\Migrator
     */
    protected $migrator;

    /**
     * Create a new migration rollback command instance.
     *
     * @param  \Illuminate\Database\Migrations\Migrator  $migrator
     * @return void
     */
    public function __construct(Migrator $migrator)
    {
        parent::__construct();

        $this->migrator = $migrator;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        if (! $this->confirmToProceed()) {
            return 1;
        }

        $this->call('migrate:rollback', array_filter([
            '--step' => $this->option('step') ?? 1,
        ]));

        $this->call('migrate', array_filter([
            '--step' => $this->option('step') ?? 1,
        ]));

        return 0;
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['step', null, InputOption::VALUE_OPTIONAL, 'The number of migrations to be reverted and re-applied'],
        ];
    }
}
