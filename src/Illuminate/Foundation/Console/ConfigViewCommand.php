<?php

namespace Illuminate\Foundation\Console;

use Illuminate\Console\Command;

class ConfigViewCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'config:view
        {section? : Section of your config that you want to view (e.g. "app" or "database.connections.mysql")}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'View the full configuration for this environment.';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function fire()
    {
        $section = $this->argument('section');
        if (! empty($section)) {
            if (! $this->laravel['config']->has($section)) {
                $this->error($section.' does not exist in your configuration.');

                return;
            }

            // If the requested string is a string, and not something that can be displayed in a table, then just print
            // it out in the console.
            $config = $this->laravel['config']->get($section);
            if (! is_array($config)) {
                $this->line('<comment>'.$config.'</comment>');

                return;
            }

            $configs = [
                $section => $config,
            ];
        } else {
            $configs = $this->laravel['config']->all();
            ksort($configs);
        }

        foreach ($configs as $section => $config) {
            $depth = $this->getConfigDepth($config);
            $tree = $this->generateTree($config, $depth);
            $this->line('<info>Configuration:</info> <comment>'.$section.'</comment>');
            $this->table([/* don't display any headers */], $tree, 'default');
        }
    }

    /**
     * Take a configuration array, recursively traverse and create a padded tree array to output as a table.
     *
     * @param  array  $data
     * @param  int  $total_depth
     * @param  int  $current_depth
     * @return array
     */
    private function generateTree($data, $total_depth, $current_depth = 1)
    {
        ksort($data);
        $table = [];
        foreach ($data as $key => $value) {
            $row = [];
            if ($current_depth > 1) {
                $row = array_merge($row, array_fill(0, ($current_depth - 1), ''));
            }

            $row[] = '<comment>'.$key.'</comment>';
            if (! is_array($value)) {
                $row[] = (is_bool($value)) ? (($value) ? 'true' : 'false') : $value;
            }

            // Pad the row out with empty columns equal to the total subtracting the current recursive depth.
            $empty_columns = ($total_depth - $current_depth);
            $empty_columns = array_fill(count($row) - 1, $empty_columns, '');
            $row = array_merge($row, $empty_columns);
            $table[] = $row;
            if (is_array($value)) {
                $table = array_merge($table, $this->generateTree($value, $total_depth, $current_depth + 1));
            }
        }

        return $table;
    }

    /**
     * Determine the depth of this configuration so we know how to pad the outputted table of data.
     *
     * @param  array  $config
     * @return int
     */
    private function getConfigDepth($config)
    {
        $total_depth = 1;
        foreach ($config as $data) {
            if (is_array($data)) {
                $depth = $this->getConfigDepth($data) + 1;
                if ($depth > $total_depth) {
                    $total_depth = $depth;
                }
            }
        }

        return $total_depth;
    }
}
