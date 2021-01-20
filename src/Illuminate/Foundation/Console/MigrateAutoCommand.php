<?php

namespace Illuminate\Foundation\Console;

use Doctrine\DBAL\Schema\Comparator;
use Illuminate\Console\Command;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;

class MigrateAutoCommand extends Command
{
    protected $signature = 'migrate:auto {--fresh} {--seed} {--force}';

    public function handle()
    {
        Artisan::call('migrate'.($this->option('fresh') ? ':fresh' : null).($this->option('force') ? ' --force' : null));

        $filesystem = new Filesystem;
        $dir = base_path(config('database.model_path'));

        if ($filesystem->exists($dir)) {
            $namespace = str_replace(['app', '/'], ['App', '\\'], rtrim(config('database.model_path'), '/')).'\\';

            foreach ($filesystem->allFiles($dir) as $file) {
                $class = app($namespace.str_replace(['/', '.php'], ['\\', null], $file->getRelativePathname()));

                if (method_exists($class, 'migration')) {
                    if (Schema::hasTable($class->getTable())) {
                        $tempTable = 'temp_'.$class->getTable();

                        Schema::dropIfExists($tempTable);
                        Schema::create($tempTable, function (Blueprint $table) use ($class) {
                            $class->migration($table);
                        });

                        $schemaManager = $class->getConnection()->getDoctrineSchemaManager();
                        $classTableDetails = $schemaManager->listTableDetails($class->getTable());
                        $tempTableDetails = $schemaManager->listTableDetails($tempTable);
                        $tableDiff = (new Comparator)->diffTable($classTableDetails, $tempTableDetails);

                        if ($tableDiff) {
                            $schemaManager->alterTable($tableDiff);
                        }

                        Schema::drop($tempTable);
                    } else {
                        Schema::create($class->getTable(), function (Blueprint $table) use ($class) {
                            $class->migration($table);
                        });
                    }
                }
            }
        }

        $this->info('Migration complete!');

        if ($this->option('seed')) {
            Artisan::call('db:seed'.($this->option('force') ? ' --force' : null));

            $this->info('Seeding complete!');
        }
    }
}
