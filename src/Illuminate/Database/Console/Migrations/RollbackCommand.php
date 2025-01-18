<?php

namespace Illuminate\Database\Console\Migrations;

use Illuminate\Console\ConfirmableTrait;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputOption;

use function Laravel\Prompts\multiselect;

#[AsCommand('migrate:rollback')]
class RollbackCommand extends BaseCommand
{
    use ConfirmableTrait;

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

        $migrations = [];
        if ($this->option('select')) {
            $migrations = $this->getMigrationsForRollbacks();
        }

        $this->migrator->usingConnection($this->option('database'), function () use ($migrations) {
            $this->migrator->setOutput($this->output)->rollback(
                $this->getMigrationPaths(),
                [
                    'pretend' => $this->option('pretend'),
                    'step' => (int) $this->option('step'),
                    'batch' => (int) $this->option('batch'),
                    'selected' => $migrations ?: [],
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
            ['select', null, InputOption::VALUE_NONE, 'Select the migrations to rollback'],
        ];
    }

    /**
     * Get the list of migrations selected by the user for rollback.
     *
     * This method presents an interactive prompt allowing users to select
     * which migrations they want to rollback from the existing migrations
     * in the database.
     *
     * @return array<int, object> Returns an array of migration records, or empty array if no migrations exist
     */

    private function getMigrationsForRollbacks()
    {
        $migrationsInstance = DB::table('migrations');

        if ($migrationsInstance->count() > 0) {
            $options = multiselect(
                label: 'Which migrations would you like to rollback (leave blank to default behaviour)',
                options: $migrationsInstance->pluck('migration')->toArray(),
                hint: 'Use the space bar to select options.',
                scroll: 10,
                required: true,
            );
            return $migrationsInstance->whereIn('migration', $options)->get()->toArray();
        }
        return [];
    }
}
