<?php

namespace Illuminate\Database\Console\Migrations;

use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Illuminate\Console\Prohibitable;
use Illuminate\Database\Migrations\Migrator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputOption;

#[AsCommand('migrate:rollback')]
class RollbackCommand extends BaseCommand
{
    use ConfirmableTrait, Prohibitable;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'migrate:rollback';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rollback the last database migration';

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
     */
    public function __construct(Migrator $migrator)
    {
        parent::__construct();

        $this->migrator = $migrator;
    }

    /**
     * Handle the execution of the rollback command.
     *
     * Displays the list of migrations that will be rolled back and asks
     * for user confirmation before proceeding, unless the --force option
     * is provided. Supports --step, --batch, and --pretend options.
     *
     * @return int
     */
    public function handle()
    {
        if ($this->isProhibited() ||
            ! $this->confirmToProceed()) {
            return Command::FAILURE;
        }

        $this->migrator->usingConnection($this->option('database'), function () {
            $migrations = $this->migrator->getRepository()->getLast();

            if ($this->option('step')) {
                $migrations = $this->migrator->getRepository()->getMigrations($this->option('step'));
            }

            if (empty($migrations)) {
                $this->info('Nothing to rollback.');
                return;
            }

            $this->line('Migrations to be rolled back:');
            foreach ($migrations as $migration) {
                $this->line(' - '.$migration->migration);
            }

            if (! $this->option('force')) {
                if (! $this->confirm('Do you really want to rollback these migrations?')) {
                    $this->info('Rollback cancelled.');
                    return;
                }
            }

            $this->migrator->setOutput($this->output)->rollback(
                $this->getMigrationPaths(), [
                    'pretend' => $this->option('pretend'),
                    'step' => (int) $this->option('step'),
                    'batch' => (int) $this->option('batch'),
                ]
            );
        });

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
            ['database', null, InputOption::VALUE_OPTIONAL, 'The database connection to use'],
            ['force', null, InputOption::VALUE_NONE, 'Force the operation to run when in production'],
            ['path', null, InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, 'The path(s) to the migrations files to be executed'],
            ['realpath', null, InputOption::VALUE_NONE, 'Indicate any provided migration file paths are pre-resolved absolute paths'],
            ['pretend', null, InputOption::VALUE_NONE, 'Dump the SQL queries that would be run'],
            ['step', null, InputOption::VALUE_OPTIONAL, 'The number of migrations to be reverted'],
            ['batch', null, InputOption::VALUE_REQUIRED, 'The batch of migrations (identified by their batch number) to be reverted'],
        ];
    }
}
