<?php

namespace Illuminate\Database\Console\Migrations;

use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use DB;
use Exception;

class WipeCommand extends Command
{
    use ConfirmableTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = '
        migrate:wipe
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
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        if (! $this->confirmToProceed()) {
            return;
        }

        $this->wipe($this->option('pretend'));
    }

    /**
     * Wipe all the tables in the database.
     *
     * @param  bool $pretend
     * @return void
     */
    private function wipe($pretend)
    {
        $sql = " SELECT concat('DROP TABLE IF EXISTS `', table_name, '`;') AS drop_query ";
        $sql .= ' FROM information_schema.tables ';
        $sql .= ' WHERE table_schema = :table_schema ';

        $db_connection = is_null($this->option('database')) ? config('database.default') : $this->option('database');
        $db_name = config('database.connections.'.$db_connection.'.database');

        if (is_null($db_name)) {
            throw new Exception('Invalid database connection.');
        }

        $this->line('<comment>Database connection: </comment>'.$db_connection);
        $this->line('<comment>Database name: </comment>'.$db_name);

        $tables = DB::select($sql, ['table_schema' => $db_name]);

        if (! count($tables)) {
            $this->error('No tables found in database.');

            return;
        }

        if ($pretend) {
            foreach ($tables as $table) {
                $this->info($table->drop_query);
            }

            return;
        }

        foreach ($tables as $table) {
            $sql = $table->drop_query;
            preg_match('/\`(.*?)\`/', $sql, $matches);
            if (! isset($matches[1])) {
                throw new Exception('Unable to get table name from Database.');
            }
            $table_name = $matches[1];
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            DB::statement($sql);
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
            $this->line('<info>Dropped table: </info>'.$table_name);
        }
    }
}
