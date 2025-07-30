<?php

namespace Illuminate\Foundation\Console;

use Illuminate\Console\Command;

class StrictRelationsCommand extends Command
{
    protected $signature = 'model:strict {--enable : Enable strict mode}';
    protected $description = 'Enable or check Eloquent strict relationship mode.';

    public function handle()
    {
        $config = config_path('database.php');
        $current = config('database.connections.eloquent.strict_relationships');

        if ($this->option('enable')) {
            file_put_contents($config, str_replace(
                "'strict_relationships' => false",
                "'strict_relationships' => true",
                file_get_contents($config)
            ));
            $this->info('Eloquent strict relationship mode enabled.');
        } else {
            $this->line('Strict mode: ' . ($current ? 'ENABLED' : 'DISABLED'));
        }
    }
}
