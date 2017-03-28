<?php

namespace Illuminate\Database\Console\Seeds;

use Illuminate\Database\Console\Migrations\BaseCommand;
use Illuminate\Console\ConfirmableTrait;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Filesystem\Filesystem;
use DB;
use Exception;

class WipeCommand extends BaseCommand
{
    use ConfirmableTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = '
        db:wipe
        { --path= : The path of migrations files to be executed. }
        { --pretend : Dump the SQL queries that would be run. }
        { --force : Force the operation to run when in production. }
        { --database= : The database connection to use. }
    ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Wipe all tables in database';

    /**
     * The migrator instance.
     *
     * @var \Illuminate\Database\Migrations\Migrator
     */
    protected $migrator;


    /**
     * The filesystem instance.
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $fs;

    /**
     * Create a new migration command instance and filesystem instance.
     *
     * @param  \Illuminate\Database\Migrations\Migrator  $migrator
     * @return void
     */
    public function __construct(Migrator $migrator)
    {
        parent::__construct();
        $this->migrator = $migrator;
        $this->fs = new Filesystem();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function fire()
    {
        if (! $this->confirmToProceed()) {
            return;
        }

        $files = $this->migrator->getMigrationFiles($this->getMigrationPaths());

        $this->wipe($files, $this->option('pretend'));
    }

    /**
     * Wipe all the tables in the database.
     *
     * @param  array $files
     * @param  bool $pretend
     * @return void
     */
    private function wipe($files, $pretend)
    {
        $db_connection = is_null($this->option('database')) ? config('database.default') : $this->option('database');
        $db_name = config('database.connections.'.$db_connection.'.database');
        if (is_null($db_name)) {
            throw new Exception('Invalid database connection.');
        }

        $this->line('<comment>Database connection: </comment>'.$db_connection);
        $this->line('<comment>Database name: </comment>'.$db_name);


        $tables[] = 'migrations';
        foreach ($files as $file) {
            $content = $this->fs->get($file);
            preg_match('/create\(\'(\w+)\'/', $content, $matches);
            if (isset($matches[1])) {
                $tables[] = $matches[1];
            }
        }

        if (! count($tables)) {
            $this->error('No tables found in database.');

            return;
        }

        if ($pretend) {
            foreach ($tables as $table) {
                $this->info($this->generateSql($table));
            }

            return;
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        foreach ($tables as $table) {
            DB::statement($this->generateSql($table));
            $this->line('<info>Dropped table: </info>'.$table);
        }
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }

    /**
     * Generates the SQL Drop line.
     *
     * @param  string $table_name
     * @return string
     */
    private function generateSql($table_name)
    {
        $sql = 'DROP TABLE IF EXISTS `:table_name`;';
        $result = str_replace(':table_name', $table_name, $sql);

        return $result;
    }
}
